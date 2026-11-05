<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$visao_global = $perfil_atual === 'admin';
$filial_usuario = filial_do_usuario();

$titulo = 'Gestão da Frota';
$prefixo = '../';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inativar_veiculo'])) {
    $id = (int) ($_POST['veiculo_id'] ?? 0);

    if ($id > 0) {
        $sql_verificacao =
            "SELECT COUNT(*) AS total
             FROM viagens
             WHERE veiculo_id = ?
               AND status IN ('agendada', 'em_andamento')";

        $query = $sql->prepare($sql_verificacao);
        $query->bind_param('i', $id);
        $query->execute();
        $query->bind_result($total_viagens_ativas);
        $query->fetch();
        $query->close();

        if ((int) $total_viagens_ativas > 0) {
            mensagem_erro('O veículo possui viagem ativa e não pode ser inativado.');
        } else {
            $sql_inativar =
                "UPDATE veiculos
                 SET ativo = 0
                 WHERE id = ?";

            if (!$visao_global) {
                $sql_inativar .= " AND filial_id = " . (int) $filial_usuario;
            }

            $query = $sql->prepare($sql_inativar);
            $query->bind_param('i', $id);

            if ($query->execute() && $query->affected_rows === 1) {
                registrar_log($sql, 'INATIVAR', 'veiculos', $id, 'Veículo inativado');
                mensagem_sucesso('Veículo inativado com sucesso.');
            } else {
                mensagem_erro('Não foi possível inativar o veículo.');
            }

            $query->close();
        }
    }

    voltar('veiculos.php');
}

$busca = trim($_GET['busca'] ?? '');
$status = $_GET['status'] ?? '';
$filial_filtro = (int) ($_GET['filial_id'] ?? 0);

$sql_lista =
    "SELECT v.*, f.nome AS filial,
            (
                SELECT MIN(p.km_limite - v.odometro_atual)
                FROM planos_manutencao p
                WHERE p.veiculo_id = v.id
                  AND p.status = 'pendente'
            ) AS km_proxima_manutencao
     FROM veiculos v
     LEFT JOIN filiais f ON f.id = v.filial_id
     WHERE v.ativo = 1";

$tipos = '';
$parametros = [];

if (!$visao_global) {
    $sql_lista .= " AND v.filial_id = ?";
    $tipos .= 'i';
    $parametros[] = $filial_usuario;
} elseif ($filial_filtro > 0) {
    $sql_lista .= " AND v.filial_id = ?";
    $tipos .= 'i';
    $parametros[] = $filial_filtro;
}

if (in_array($status, ['disponivel', 'reservado', 'em_transito', 'manutencao'], true)) {
    $sql_lista .= " AND v.status = ?";
    $tipos .= 's';
    $parametros[] = $status;
}

if ($busca !== '') {
    $sql_lista .= " AND (v.placa LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ?)";
    $termo = '%' . $busca . '%';
    $tipos .= 'sss';
    $parametros[] = $termo;
    $parametros[] = $termo;
    $parametros[] = $termo;
}

$sql_lista .= " ORDER BY v.placa";

$query = $sql->prepare($sql_lista);

if ($tipos !== '') {
    $query->bind_param($tipos, ...$parametros);
}

$query->execute();
$lista = $query->get_result();

$sql_contagem =
    "SELECT status, COUNT(*) AS total
     FROM veiculos
     WHERE ativo = 1";

if (!$visao_global) {
    $sql_contagem .= " AND filial_id = " . (int) $filial_usuario;
}

$sql_contagem .= " GROUP BY status";

$contagens = [
    'disponivel' => 0,
    'reservado' => 0,
    'em_transito' => 0,
    'manutencao' => 0
];

$resultado_contagem = $sql->query($sql_contagem);

while ($linha = $resultado_contagem->fetch_assoc()) {
    $contagens[$linha['status']] = (int) $linha['total'];
}

$filiais = null;

if ($visao_global) {
    $filiais = $sql->query(
        "SELECT id, nome
         FROM filiais
         WHERE ativo = 1
         ORDER BY nome"
    );
}

include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta"><?php echo $visao_global ? 'Visão administrativa' : 'Nível 2 - Gestão da filial'; ?></span>
        <h3>Frota corporativa</h3>
    </div>

    <a href="veiculo-form.php" class="botao botao-principal shadow-sm">
        <i class="fa-solid fa-plus"></i>
        Cadastrar veículo
    </a>
</div>

<div class="cards cards-quatro compactos">
    <a href="veiculos.php?status=disponivel" class="card-info shadow-sm">
        <span class="card-icone verde"><i class="fa-solid fa-circle-check"></i></span>
        <div>
            <span>Disponíveis</span>
            <strong><?php echo $contagens['disponivel']; ?></strong>
        </div>
    </a>

    <a href="veiculos.php?status=reservado" class="card-info shadow-sm">
        <span class="card-icone amarelo"><i class="fa-solid fa-calendar-check"></i></span>
        <div>
            <span>Reservados</span>
            <strong><?php echo $contagens['reservado']; ?></strong>
        </div>
    </a>

    <a href="veiculos.php?status=em_transito" class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-road"></i></span>
        <div>
            <span>Em trânsito</span>
            <strong><?php echo $contagens['em_transito']; ?></strong>
        </div>
    </a>

    <a href="veiculos.php?status=manutencao" class="card-info shadow-sm">
        <span class="card-icone vermelho"><i class="fa-solid fa-screwdriver-wrench"></i></span>
        <div>
            <span>Manutenção</span>
            <strong><?php echo $contagens['manutencao']; ?></strong>
        </div>
    </a>
</div>

<div class="painel shadow-sm">
    <form method="GET" class="filtros">
        <div class="input-box filtro-busca">
            <label for="busca">Buscar veículo</label>
            <input
                type="text"
                id="busca"
                name="busca"
                placeholder="Placa, marca ou modelo"
                value="<?php echo escapar($busca); ?>"
            >
        </div>

        <div class="input-box">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Todos</option>
                <option value="disponivel" <?php echo $status === 'disponivel' ? 'selected' : ''; ?>>Disponível</option>
                <option value="reservado" <?php echo $status === 'reservado' ? 'selected' : ''; ?>>Reservado</option>
                <option value="em_transito" <?php echo $status === 'em_transito' ? 'selected' : ''; ?>>Em trânsito</option>
                <option value="manutencao" <?php echo $status === 'manutencao' ? 'selected' : ''; ?>>Manutenção</option>
            </select>
        </div>

        <?php if ($visao_global): ?>
            <div class="input-box">
                <label for="filial_id">Filial</label>
                <select id="filial_id" name="filial_id">
                    <option value="0">Todas</option>

                    <?php while ($filial = $filiais->fetch_assoc()): ?>
                        <option
                            value="<?php echo $filial['id']; ?>"
                            <?php echo $filial_filtro === (int) $filial['id'] ? 'selected' : ''; ?>
                        >
                            <?php echo escapar($filial['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="filtro-acoes">
            <button class="botao botao-principal" type="submit">
                <i class="fa-solid fa-magnifying-glass"></i>
                Filtrar
            </button>

            <a href="veiculos.php" class="botao botao-secundario">Limpar</a>
        </div>
    </form>
</div>

<div class="painel shadow-sm">
    <div class="painel-titulo">
        <div>
            <span class="mini-etiqueta">Patrimônio</span>
            <h3>Veículos cadastrados</h3>
        </div>
    </div>

    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Veículo</th>
                    <th>Ano</th>
                    <th>Filial</th>
                    <th>KM atual</th>
                    <th>CNH</th>
                    <th>Status</th>
                    <th>Manutenção</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lista->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" class="tabela-vazia">Nenhum veículo encontrado com os filtros informados.</td>
                    </tr>
                <?php endif; ?>

                <?php while ($veiculo = $lista->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo escapar($veiculo['placa']); ?></strong>
                            <small class="tabela-subtexto"><?php echo escapar($veiculo['marca'] . ' ' . $veiculo['modelo']); ?></small>
                        </td>
                        <td><?php echo escapar($veiculo['ano']); ?></td>
                        <td><?php echo escapar($veiculo['filial'] ?? '-'); ?></td>
                        <td><?php echo number_format($veiculo['odometro_atual'], 0, ',', '.'); ?> km</td>
                        <td>Categoria <?php echo escapar($veiculo['categoria_cnh_exigida']); ?></td>
                        <td>
                            <span class="status <?php echo classe_status($veiculo['status']); ?>">
                                <?php echo escapar(status_veiculo_nome($veiculo['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($veiculo['km_proxima_manutencao'] !== null && (int) $veiculo['km_proxima_manutencao'] <= 500): ?>
                                <span class="status <?php echo (int) $veiculo['km_proxima_manutencao'] <= 0 ? 'cancelada' : 'reservada'; ?>">
                                    <?php
                                    echo (int) $veiculo['km_proxima_manutencao'] <= 0
                                        ? 'Limite atingido'
                                        : number_format($veiculo['km_proxima_manutencao'], 0, ',', '.') . ' km';
                                    ?>
                                </span>
                            <?php else: ?>
                                <span class="texto-ajuda">Sem alerta</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="acoes-tabela">
                                <a class="botao botao-secundario" href="veiculo-form.php?id=<?php echo $veiculo['id']; ?>">
                                    <i class="fa-solid fa-pen"></i>
                                    Editar
                                </a>

                                <a class="botao botao-secundario" href="manutencoes.php?veiculo_id=<?php echo $veiculo['id']; ?>">
                                    <i class="fa-solid fa-screwdriver-wrench"></i>
                                    Manutenção
                                </a>

                                <form method="POST" class="form-acao-inline">
                                    <input type="hidden" name="veiculo_id" value="<?php echo $veiculo['id']; ?>">

                                    <button
                                        class="botao botao-perigo"
                                        type="submit"
                                        name="inativar_veiculo"
                                        onclick="return confirm('Inativar este veículo?');"
                                    >
                                        Inativar
                                    </button>
                                </form>
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
