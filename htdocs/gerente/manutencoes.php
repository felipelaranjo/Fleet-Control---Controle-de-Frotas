<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$visao_global = $perfil_atual === 'admin';
$filial_usuario = filial_do_usuario();

$titulo = 'Manutenções';
$prefixo = '../';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plano_id = (int) ($_POST['plano_id'] ?? 0);
    $acao = $_POST['acao'] ?? '';

    $sql_busca =
        "SELECT p.id, p.veiculo_id, p.status
         FROM planos_manutencao p
         INNER JOIN veiculos v ON v.id = p.veiculo_id
         WHERE p.id = ?";

    if (!$visao_global) {
        $sql_busca .= " AND v.filial_id = " . (int) $filial_usuario;
    }

    $query = $sql->prepare($sql_busca);
    $query->bind_param('i', $plano_id);
    $query->execute();
    $plano = $query->get_result()->fetch_assoc();
    $query->close();

    if (!$plano) {
        mensagem_erro('Plano de manutenção não encontrado.');
        voltar('manutencoes.php');
    }

    $sql->begin_transaction();

    try {
        if ($acao === 'iniciar') {
            $query = $sql->prepare(
                "UPDATE veiculos
                 SET status = 'manutencao'
                 WHERE id = ?"
            );
            $veiculo_id = (int) $plano['veiculo_id'];
            $query->bind_param('i', $veiculo_id);
            $query->execute();
            $query->close();

            registrar_log($sql, 'INICIAR', 'planos_manutencao', $plano_id, 'Veículo colocado em manutenção');
            $mensagem = 'Veículo colocado em manutenção.';
        } elseif ($acao === 'concluir') {
            $query = $sql->prepare(
                "UPDATE planos_manutencao
                 SET status = 'concluido'
                 WHERE id = ?"
            );
            $query->bind_param('i', $plano_id);
            $query->execute();
            $query->close();

            $query = $sql->prepare(
                "UPDATE veiculos
                 SET status = 'disponivel'
                 WHERE id = ?
                   AND status = 'manutencao'"
            );
            $veiculo_id = (int) $plano['veiculo_id'];
            $query->bind_param('i', $veiculo_id);
            $query->execute();
            $query->close();

            registrar_log($sql, 'CONCLUIR', 'planos_manutencao', $plano_id, 'Manutenção concluída');
            $mensagem = 'Manutenção concluída e veículo liberado.';
        } elseif ($acao === 'cancelar') {
            $query = $sql->prepare(
                "UPDATE planos_manutencao
                 SET status = 'cancelado'
                 WHERE id = ?"
            );
            $query->bind_param('i', $plano_id);
            $query->execute();
            $query->close();

            registrar_log($sql, 'CANCELAR', 'planos_manutencao', $plano_id, 'Manutenção cancelada');
            $mensagem = 'Plano de manutenção cancelado.';
        } else {
            throw new Exception('Ação inválida.');
        }

        $sql->commit();
        mensagem_sucesso($mensagem);
    } catch (Throwable $erro) {
        $sql->rollback();
        mensagem_erro('Não foi possível atualizar a manutenção.');
    }

    voltar('manutencoes.php');
}

$veiculo_filtro = (int) ($_GET['veiculo_id'] ?? 0);
$status_filtro = $_GET['status'] ?? '';

$sql_lista =
    "SELECT p.*, v.placa, v.modelo, v.odometro_atual, v.status AS status_veiculo,
            f.nome AS filial,
            (p.km_limite - v.odometro_atual) AS km_restantes
     FROM planos_manutencao p
     INNER JOIN veiculos v ON v.id = p.veiculo_id
     LEFT JOIN filiais f ON f.id = v.filial_id
     WHERE v.ativo = 1";

$tipos = '';
$parametros = [];

if (!$visao_global) {
    $sql_lista .= " AND v.filial_id = ?";
    $tipos .= 'i';
    $parametros[] = $filial_usuario;
}

if ($veiculo_filtro > 0) {
    $sql_lista .= " AND v.id = ?";
    $tipos .= 'i';
    $parametros[] = $veiculo_filtro;
}

if (in_array($status_filtro, ['pendente', 'concluido', 'cancelado'], true)) {
    $sql_lista .= " AND p.status = ?";
    $tipos .= 's';
    $parametros[] = $status_filtro;
}

$sql_lista .= " ORDER BY
    CASE p.status WHEN 'pendente' THEN 1 WHEN 'concluido' THEN 2 ELSE 3 END,
    km_restantes ASC,
    p.data_agendamento ASC";

$query = $sql->prepare($sql_lista);

if ($tipos !== '') {
    $query->bind_param($tipos, ...$parametros);
}

$query->execute();
$lista = $query->get_result();

include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Manutenção da frota</span>
        <h3>Planos preventivos e corretivos</h3>
    </div>

    <a href="manutencao-form.php<?php echo $veiculo_filtro > 0 ? '?veiculo_id=' . $veiculo_filtro : ''; ?>" class="botao botao-principal shadow-sm">
        <i class="fa-solid fa-plus"></i>
        Nova manutenção
    </a>
</div>

<div class="painel shadow-sm">
    <form method="GET" class="filtros">
        <?php if ($veiculo_filtro > 0): ?>
            <input type="hidden" name="veiculo_id" value="<?php echo $veiculo_filtro; ?>">
        <?php endif; ?>

        <div class="input-box">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Todos</option>
                <option value="pendente" <?php echo $status_filtro === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                <option value="concluido" <?php echo $status_filtro === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                <option value="cancelado" <?php echo $status_filtro === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
            </select>
        </div>

        <div class="filtro-acoes">
            <button class="botao botao-principal" type="submit">Filtrar</button>
            <a href="manutencoes.php" class="botao botao-secundario">Limpar</a>
        </div>
    </form>
</div>

<div class="painel shadow-sm">
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Veículo</th>
                    <th>Serviço</th>
                    <th>KM atual</th>
                    <th>KM limite</th>
                    <th>Restante</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lista->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" class="tabela-vazia">Nenhum plano de manutenção encontrado.</td>
                    </tr>
                <?php endif; ?>

                <?php while ($manutencao = $lista->fetch_assoc()): ?>
                    <?php
                    $km_restantes = (int) $manutencao['km_restantes'];
                    $em_alerta = $manutencao['status'] === 'pendente' && $km_restantes <= 500;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo escapar($manutencao['placa']); ?></strong>
                            <small class="tabela-subtexto"><?php echo escapar($manutencao['modelo']); ?></small>
                        </td>
                        <td><?php echo escapar($manutencao['tipo_servico']); ?></td>
                        <td><?php echo number_format($manutencao['odometro_atual'], 0, ',', '.'); ?> km</td>
                        <td><?php echo number_format($manutencao['km_limite'], 0, ',', '.'); ?> km</td>
                        <td>
                            <?php if ($em_alerta): ?>
                                <span class="status <?php echo $km_restantes <= 0 ? 'cancelada' : 'reservada'; ?>">
                                    <?php echo $km_restantes <= 0 ? 'Limite atingido' : number_format($km_restantes, 0, ',', '.') . ' km'; ?>
                                </span>
                            <?php else: ?>
                                <?php echo number_format(max($km_restantes, 0), 0, ',', '.'); ?> km
                            <?php endif; ?>
                        </td>
                        <td><?php echo data_br($manutencao['data_agendamento']); ?></td>
                        <td>
                            <span class="status <?php echo classe_status($manutencao['status']); ?>">
                                <?php echo ucfirst($manutencao['status']); ?>
                            </span>

                            <?php if ($manutencao['status_veiculo'] === 'manutencao'): ?>
                                <small class="tabela-subtexto">Veículo retido</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="acoes-tabela">
                                <?php if ($manutencao['status'] === 'pendente'): ?>
                                    <?php if ($manutencao['status_veiculo'] !== 'manutencao'): ?>
                                        <form method="POST" class="form-acao-inline">
                                            <input type="hidden" name="plano_id" value="<?php echo $manutencao['id']; ?>">
                                            <input type="hidden" name="acao" value="iniciar">
                                            <button class="botao botao-secundario" type="submit">Iniciar</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" class="form-acao-inline">
                                        <input type="hidden" name="plano_id" value="<?php echo $manutencao['id']; ?>">
                                        <input type="hidden" name="acao" value="concluir">
                                        <button class="botao botao-principal" type="submit">Concluir</button>
                                    </form>

                                    <form method="POST" class="form-acao-inline">
                                        <input type="hidden" name="plano_id" value="<?php echo $manutencao['id']; ?>">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <button class="botao botao-perigo" type="submit" onclick="return confirm('Cancelar este plano de manutenção?');">Cancelar</button>
                                    </form>
                                <?php else: ?>
                                    <span class="texto-ajuda">Finalizada</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$query->close();
include '../rodape.php';
?>
