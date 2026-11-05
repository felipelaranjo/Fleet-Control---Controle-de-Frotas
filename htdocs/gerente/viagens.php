<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$visao_global = $perfil_atual === 'admin';
$filial_usuario = filial_do_usuario();

$titulo = 'Viagens';
$prefixo = '../';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_viagem'])) {
    $viagem_id = (int) ($_POST['viagem_id'] ?? 0);

    $sql_busca =
        "SELECT id, veiculo_id, status
         FROM viagens
         WHERE id = ?";

    if (!$visao_global) {
        $sql_busca .= " AND filial_id = " . (int) $filial_usuario;
    }

    $query = $sql->prepare($sql_busca);
    $query->bind_param('i', $viagem_id);
    $query->execute();
    $viagem = $query->get_result()->fetch_assoc();
    $query->close();

    if (!$viagem) {
        mensagem_erro('Viagem não encontrada.');
        voltar('viagens.php');
    }

    if (!in_array($viagem['status'], ['agendada'], true)) {
        mensagem_erro('Somente viagens agendadas podem ser canceladas por esta tela.');
        voltar('viagens.php');
    }

    $sql->begin_transaction();

    try {
        $query = $sql->prepare(
            "UPDATE viagens
             SET status = 'cancelada'
             WHERE id = ?"
        );
        $query->bind_param('i', $viagem_id);
        $query->execute();
        $query->close();

        $query = $sql->prepare(
            "UPDATE veiculos
             SET status = 'disponivel'
             WHERE id = ?
               AND status = 'reservado'"
        );
        $veiculo_id = (int) $viagem['veiculo_id'];
        $query->bind_param('i', $veiculo_id);
        $query->execute();
        $query->close();

        $sql->commit();

        registrar_log($sql, 'CANCELAR', 'viagens', $viagem_id, 'Viagem cancelada pelo gestor');
        mensagem_sucesso('Viagem cancelada e veículo liberado.');
    } catch (Throwable $erro) {
        $sql->rollback();
        mensagem_erro('Não foi possível cancelar a viagem.');
    }

    voltar('viagens.php');
}

$status = $_GET['status'] ?? '';
$busca = trim($_GET['busca'] ?? '');

$sql_lista =
    "SELECT v.*, ve.placa, ve.modelo,
            u.nome AS motorista,
            f.nome AS filial
     FROM viagens v
     INNER JOIN veiculos ve ON ve.id = v.veiculo_id
     INNER JOIN usuarios u ON u.id = v.motorista_id
     LEFT JOIN filiais f ON f.id = v.filial_id
     WHERE 1 = 1";

$tipos = '';
$parametros = [];

if (!$visao_global) {
    $sql_lista .= " AND v.filial_id = ?";
    $tipos .= 'i';
    $parametros[] = $filial_usuario;
}

if (in_array($status, ['agendada', 'em_andamento', 'concluida', 'cancelada'], true)) {
    $sql_lista .= " AND v.status = ?";
    $tipos .= 's';
    $parametros[] = $status;
}

if ($busca !== '') {
    $sql_lista .= " AND (v.descricao_rota LIKE ? OR ve.placa LIKE ? OR u.nome LIKE ?)";
    $termo = '%' . $busca . '%';
    $tipos .= 'sss';
    $parametros[] = $termo;
    $parametros[] = $termo;
    $parametros[] = $termo;
}

$sql_lista .= " ORDER BY v.data_partida DESC";

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
        <span class="etiqueta"><?php echo $visao_global ? 'Visão global' : 'Nível 2 - Gestão da filial'; ?></span>
        <h3>Planejamento de viagens</h3>
    </div>

    <a href="viagem-form.php" class="botao botao-principal shadow-sm">
        <i class="fa-solid fa-plus"></i>
        Nova viagem
    </a>
</div>

<div class="painel shadow-sm">
    <form method="GET" class="filtros">
        <div class="input-box filtro-busca">
            <label for="busca">Buscar</label>
            <input
                type="text"
                id="busca"
                name="busca"
                placeholder="Rota, placa ou motorista"
                value="<?php echo escapar($busca); ?>"
            >
        </div>

        <div class="input-box">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Todos</option>
                <option value="agendada" <?php echo $status === 'agendada' ? 'selected' : ''; ?>>Agendada</option>
                <option value="em_andamento" <?php echo $status === 'em_andamento' ? 'selected' : ''; ?>>Em andamento</option>
                <option value="concluida" <?php echo $status === 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                <option value="cancelada" <?php echo $status === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
            </select>
        </div>

        <div class="filtro-acoes">
            <button class="botao botao-principal" type="submit">
                <i class="fa-solid fa-magnifying-glass"></i>
                Filtrar
            </button>
            <a href="viagens.php" class="botao botao-secundario">Limpar</a>
        </div>
    </form>
</div>

<div class="painel shadow-sm">
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Rota</th>
                    <th>Veículo</th>
                    <th>Motorista</th>
                    <th>Saída</th>
                    <th>Retorno previsto</th>
                    <th>Filial</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lista->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" class="tabela-vazia">Nenhuma viagem encontrada.</td>
                    </tr>
                <?php endif; ?>

                <?php while ($viagem = $lista->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo escapar($viagem['descricao_rota']); ?></strong>
                            <small class="tabela-subtexto">Ordem #<?php echo $viagem['id']; ?></small>
                        </td>
                        <td><?php echo escapar($viagem['placa'] . ' - ' . $viagem['modelo']); ?></td>
                        <td><?php echo escapar($viagem['motorista']); ?></td>
                        <td><?php echo data_hora_br($viagem['data_partida']); ?></td>
                        <td><?php echo data_hora_br($viagem['previsao_retorno']); ?></td>
                        <td><?php echo escapar($viagem['filial'] ?? '-'); ?></td>
                        <td>
                            <span class="status <?php echo classe_status($viagem['status']); ?>">
                                <?php echo escapar(status_viagem_nome($viagem['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <div class="acoes-tabela">
                                <?php if ($viagem['status'] === 'agendada'): ?>
                                    <form method="POST" class="form-acao-inline">
                                        <input type="hidden" name="viagem_id" value="<?php echo $viagem['id']; ?>">
                                        <button
                                            class="botao botao-perigo"
                                            type="submit"
                                            name="cancelar_viagem"
                                            onclick="return confirm('Cancelar esta viagem e liberar o veículo?');"
                                        >
                                            Cancelar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="texto-ajuda">Sem ação pendente</span>
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
