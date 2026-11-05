<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('motorista');

$titulo = 'Minhas Viagens';
$prefixo = '../';
$id_motorista = (int) $_SESSION['usuario']['id'];
$status = $_GET['status'] ?? '';

$sql_lista =
    "SELECT v.*, ve.placa, ve.modelo, ve.marca,
            c.id AS checklist_id
     FROM viagens v
     INNER JOIN veiculos ve ON ve.id = v.veiculo_id
     LEFT JOIN checklists_viagem c ON c.viagem_id = v.id
     WHERE v.motorista_id = ?";

$tipos = 'i';
$parametros = [$id_motorista];

if (in_array($status, ['agendada', 'em_andamento', 'concluida', 'cancelada'], true)) {
    $sql_lista .= " AND v.status = ?";
    $tipos .= 's';
    $parametros[] = $status;
}

$sql_lista .= " ORDER BY v.data_partida DESC";

$query = $sql->prepare($sql_lista);
$query->bind_param($tipos, ...$parametros);
$query->execute();
$lista = $query->get_result();

include '../cabecalho.php';
?>

<div class="painel shadow-sm">
    <form method="GET" class="filtros">
        <div class="input-box">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Todas</option>
                <option value="agendada" <?php echo $status === 'agendada' ? 'selected' : ''; ?>>Agendadas</option>
                <option value="em_andamento" <?php echo $status === 'em_andamento' ? 'selected' : ''; ?>>Em andamento</option>
                <option value="concluida" <?php echo $status === 'concluida' ? 'selected' : ''; ?>>Concluídas</option>
                <option value="cancelada" <?php echo $status === 'cancelada' ? 'selected' : ''; ?>>Canceladas</option>
            </select>
        </div>

        <div class="filtro-acoes">
            <button class="botao botao-principal" type="submit">Filtrar</button>
            <a href="viagens.php" class="botao botao-secundario">Limpar</a>
        </div>
    </form>
</div>

<div class="lista-cards-mobile">
    <?php if ($lista->num_rows === 0): ?>
        <div class="estado-vazio painel shadow-sm">
            <i class="fa-solid fa-route"></i>
            <strong>Nenhuma viagem encontrada</strong>
            <span>Altere o filtro ou aguarde uma nova atribuição.</span>
        </div>
    <?php endif; ?>

    <?php while ($viagem = $lista->fetch_assoc()): ?>
        <div class="viagem-card painel shadow-sm">
            <div class="viagem-card-topo">
                <div>
                    <span class="mini-etiqueta">Ordem #<?php echo $viagem['id']; ?></span>
                    <h3><?php echo escapar($viagem['descricao_rota']); ?></h3>
                </div>

                <span class="status <?php echo classe_status($viagem['status']); ?>">
                    <?php echo escapar(status_viagem_nome($viagem['status'])); ?>
                </span>
            </div>

            <div class="viagem-card-dados">
                <div>
                    <span>Veículo</span>
                    <strong><?php echo escapar($viagem['placa'] . ' · ' . $viagem['modelo']); ?></strong>
                </div>
                <div>
                    <span>Saída</span>
                    <strong><?php echo data_hora_br($viagem['data_partida']); ?></strong>
                </div>
                <div>
                    <span>Retorno previsto</span>
                    <strong><?php echo data_hora_br($viagem['previsao_retorno']); ?></strong>
                </div>
            </div>

            <div class="fin viagem-card-acoes">
                <a class="botao botao-principal" href="viagem-detalhes.php?id=<?php echo $viagem['id']; ?>">Abrir viagem</a>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php
$query->close();
include '../rodape.php';
?>
