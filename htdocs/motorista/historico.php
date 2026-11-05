<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('motorista');

$titulo = 'Histórico de Viagens';
$prefixo = '../';
$id_motorista = (int) $_SESSION['usuario']['id'];

$query = $sql->prepare(
    "SELECT v.*, ve.placa, ve.modelo,
            COALESCE((
                SELECT SUM(d.valor)
                FROM despesas_viagem d
                WHERE d.viagem_id = v.id
            ), 0) AS total_despesas
     FROM viagens v
     INNER JOIN veiculos ve ON ve.id = v.veiculo_id
     WHERE v.motorista_id = ?
       AND v.status = 'concluida'
     ORDER BY v.data_partida DESC"
);
$query->bind_param('i', $id_motorista);
$query->execute();
$lista = $query->get_result();

include '../cabecalho.php';
?>

<div class="painel shadow-sm">
    <div class="painel-titulo">
        <div>
            <span class="mini-etiqueta">Histórico pessoal</span>
            <h3>Viagens concluídas</h3>
        </div>
    </div>

    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Rota</th>
                    <th>Veículo</th>
                    <th>KM percorridos</th>
                    <th>Despesas</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lista->num_rows === 0): ?>
                    <tr><td colspan="6" class="tabela-vazia">Nenhuma viagem concluída.</td></tr>
                <?php endif; ?>

                <?php while ($viagem = $lista->fetch_assoc()): ?>
                    <?php
                    $km = 0;
                    if ($viagem['odometro_final'] !== null && $viagem['odometro_inicial'] !== null) {
                        $km = (int) $viagem['odometro_final'] - (int) $viagem['odometro_inicial'];
                    }
                    ?>
                    <tr>
                        <td><?php echo data_br($viagem['data_partida']); ?></td>
                        <td><?php echo escapar($viagem['descricao_rota']); ?></td>
                        <td><?php echo escapar($viagem['placa'] . ' - ' . $viagem['modelo']); ?></td>
                        <td><?php echo number_format($km, 0, ',', '.'); ?> km</td>
                        <td><?php echo moeda_br($viagem['total_despesas']); ?></td>
                        <td><span class="status concluida">Concluída</span></td>
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
