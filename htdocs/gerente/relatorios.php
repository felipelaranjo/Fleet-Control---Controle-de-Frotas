<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$visao_global = $perfil_atual === 'admin';
$filial_usuario = filial_do_usuario();

$titulo = 'Relatórios';
$prefixo = '../';

$filtro_filial = $visao_global ? '' : ' AND v.filial_id = ' . (int) $filial_usuario;

$total_despesas = (float) $sql->query(
    "SELECT COALESCE(SUM(d.valor), 0) AS total
     FROM despesas_viagem d
     INNER JOIN viagens v ON v.id = d.viagem_id
     WHERE 1 = 1" . $filtro_filial
)->fetch_assoc()['total'];

$viagens_concluidas = (int) $sql->query(
    "SELECT COUNT(*) AS total
     FROM viagens v
     WHERE v.status = 'concluida'" . $filtro_filial
)->fetch_assoc()['total'];

$km_percorridos = (int) $sql->query(
    "SELECT COALESCE(SUM(v.odometro_final - v.odometro_inicial), 0) AS total
     FROM viagens v
     WHERE v.status = 'concluida'
       AND v.odometro_final IS NOT NULL
       AND v.odometro_inicial IS NOT NULL" . $filtro_filial
)->fetch_assoc()['total'];

$veiculos_ativos = (int) $sql->query(
    "SELECT COUNT(*) AS total
     FROM veiculos v
     WHERE v.ativo = 1" . $filtro_filial
)->fetch_assoc()['total'];

$sql_categorias =
    "SELECT d.categoria, COUNT(*) AS quantidade, SUM(d.valor) AS total
     FROM despesas_viagem d
     INNER JOIN viagens v ON v.id = d.viagem_id
     WHERE 1 = 1" . $filtro_filial . "
     GROUP BY d.categoria
     ORDER BY total DESC";

$resumo_categorias = $sql->query($sql_categorias);

include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Indicadores consolidados</span>
        <h3>Resumo operacional</h3>
        <p><?php echo $visao_global ? 'Dados consolidados de todas as filiais.' : 'Dados consolidados da filial vinculada ao seu login.'; ?></p>
    </div>

    <button class="botao botao-secundario shadow-sm" type="button" onclick="window.print()">
        <i class="fa-solid fa-print"></i>
        Imprimir relatório
    </button>
</div>

<div class="cards cards-quatro">
    <div class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-wallet"></i></span>
        <div><span>Despesas</span><strong><?php echo moeda_br($total_despesas); ?></strong><small>Total lançado</small></div>
    </div>

    <div class="card-info shadow-sm">
        <span class="card-icone verde"><i class="fa-solid fa-flag-checkered"></i></span>
        <div><span>Viagens concluídas</span><strong><?php echo $viagens_concluidas; ?></strong><small>Histórico consolidado</small></div>
    </div>

    <div class="card-info shadow-sm">
        <span class="card-icone amarelo"><i class="fa-solid fa-road"></i></span>
        <div><span>KM percorridos</span><strong><?php echo number_format($km_percorridos, 0, ',', '.'); ?></strong><small>Viagens finalizadas</small></div>
    </div>

    <div class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-car-side"></i></span>
        <div><span>Veículos ativos</span><strong><?php echo $veiculos_ativos; ?></strong><small>Frota monitorada</small></div>
    </div>
</div>

<div class="painel shadow-sm">
    <div class="painel-titulo">
        <div>
            <span class="mini-etiqueta">Custos</span>
            <h3>Despesas por categoria</h3>
        </div>
    </div>

    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Lançamentos</th>
                    <th>Total</th>
                    <th>Participação</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($categoria = $resumo_categorias->fetch_assoc()): ?>
                    <?php $participacao = $total_despesas > 0 ? ((float) $categoria['total'] / $total_despesas) * 100 : 0; ?>
                    <tr>
                        <td><?php echo escapar(ucfirst(str_replace('_', ' ', $categoria['categoria']))); ?></td>
                        <td><?php echo $categoria['quantidade']; ?></td>
                        <td><strong><?php echo moeda_br($categoria['total']); ?></strong></td>
                        <td><?php echo number_format($participacao, 1, ',', '.'); ?>%</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../rodape.php'; ?>
