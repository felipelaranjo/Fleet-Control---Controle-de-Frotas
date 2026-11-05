<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$visao_global = $perfil_atual === 'admin';
$filial_usuario = filial_do_usuario();

$titulo = 'Despesas de Viagem';
$prefixo = '../';

$categoria = $_GET['categoria'] ?? '';

$sql_base_filtro = $visao_global ? '' : ' AND v.filial_id = ' . (int) $filial_usuario;

$total_geral = (float) $sql->query(
    "SELECT COALESCE(SUM(d.valor), 0) AS total
     FROM despesas_viagem d
     INNER JOIN viagens v ON v.id = d.viagem_id
     WHERE 1 = 1" . $sql_base_filtro
)->fetch_assoc()['total'];

$total_combustivel = (float) $sql->query(
    "SELECT COALESCE(SUM(d.valor), 0) AS total
     FROM despesas_viagem d
     INNER JOIN viagens v ON v.id = d.viagem_id
     WHERE d.categoria = 'combustivel'" . $sql_base_filtro
)->fetch_assoc()['total'];

$total_pedagio = (float) $sql->query(
    "SELECT COALESCE(SUM(d.valor), 0) AS total
     FROM despesas_viagem d
     INNER JOIN viagens v ON v.id = d.viagem_id
     WHERE d.categoria = 'pedagio'" . $sql_base_filtro
)->fetch_assoc()['total'];

$total_emergencia = (float) $sql->query(
    "SELECT COALESCE(SUM(d.valor), 0) AS total
     FROM despesas_viagem d
     INNER JOIN viagens v ON v.id = d.viagem_id
     WHERE d.categoria = 'manutencao_emergencia'" . $sql_base_filtro
)->fetch_assoc()['total'];

$sql_lista =
    "SELECT d.*, v.descricao_rota, ve.placa, u.nome AS motorista
     FROM despesas_viagem d
     INNER JOIN viagens v ON v.id = d.viagem_id
     INNER JOIN veiculos ve ON ve.id = v.veiculo_id
     INNER JOIN usuarios u ON u.id = v.motorista_id
     WHERE 1 = 1";

$tipos = '';
$parametros = [];

if (!$visao_global) {
    $sql_lista .= " AND v.filial_id = ?";
    $tipos .= 'i';
    $parametros[] = $filial_usuario;
}

if (in_array($categoria, ['combustivel', 'pedagio', 'manutencao_emergencia'], true)) {
    $sql_lista .= " AND d.categoria = ?";
    $tipos .= 's';
    $parametros[] = $categoria;
}

$sql_lista .= " ORDER BY d.lancado_em DESC";

$query = $sql->prepare($sql_lista);

if ($tipos !== '') {
    $query->bind_param($tipos, ...$parametros);
}

$query->execute();
$lista = $query->get_result();

include '../cabecalho.php';
?>

<div class="cards cards-quatro compactos">
    <div class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-wallet"></i></span>
        <div><span>Total</span><strong><?php echo moeda_br($total_geral); ?></strong></div>
    </div>

    <div class="card-info shadow-sm">
        <span class="card-icone verde"><i class="fa-solid fa-gas-pump"></i></span>
        <div><span>Combustível</span><strong><?php echo moeda_br($total_combustivel); ?></strong></div>
    </div>

    <div class="card-info shadow-sm">
        <span class="card-icone amarelo"><i class="fa-solid fa-road"></i></span>
        <div><span>Pedágios</span><strong><?php echo moeda_br($total_pedagio); ?></strong></div>
    </div>

    <div class="card-info shadow-sm">
        <span class="card-icone vermelho"><i class="fa-solid fa-screwdriver-wrench"></i></span>
        <div><span>Emergências</span><strong><?php echo moeda_br($total_emergencia); ?></strong></div>
    </div>
</div>

<div class="painel shadow-sm">
    <form method="GET" class="filtros">
        <div class="input-box">
            <label for="categoria">Categoria</label>
            <select id="categoria" name="categoria">
                <option value="">Todas</option>
                <option value="combustivel" <?php echo $categoria === 'combustivel' ? 'selected' : ''; ?>>Combustível</option>
                <option value="pedagio" <?php echo $categoria === 'pedagio' ? 'selected' : ''; ?>>Pedágio</option>
                <option value="manutencao_emergencia" <?php echo $categoria === 'manutencao_emergencia' ? 'selected' : ''; ?>>Manutenção de emergência</option>
            </select>
        </div>

        <div class="filtro-acoes">
            <button class="botao botao-principal" type="submit">Filtrar</button>
            <a href="despesas.php" class="botao botao-secundario">Limpar</a>
        </div>
    </form>
</div>

<div class="painel shadow-sm">
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Viagem</th>
                    <th>Motorista</th>
                    <th>Veículo</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                    <th>Odômetro</th>
                    <th>Comprovante</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lista->num_rows === 0): ?>
                    <tr><td colspan="8" class="tabela-vazia">Nenhuma despesa encontrada.</td></tr>
                <?php endif; ?>

                <?php while ($despesa = $lista->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo data_hora_br($despesa['lancado_em']); ?></td>
                        <td><?php echo escapar($despesa['descricao_rota']); ?></td>
                        <td><?php echo escapar($despesa['motorista']); ?></td>
                        <td><?php echo escapar($despesa['placa']); ?></td>
                        <td><?php echo escapar(ucfirst(str_replace('_', ' ', $despesa['categoria']))); ?></td>
                        <td><strong><?php echo moeda_br($despesa['valor']); ?></strong></td>
                        <td><?php echo $despesa['odometro_momento'] ? number_format($despesa['odometro_momento'], 0, ',', '.') . ' km' : '-'; ?></td>
                        <td><?php echo $despesa['comprovante_caminho'] ? 'Anexado' : '-'; ?></td>
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
