<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('motorista');

$id = (int) ($_GET['id'] ?? 0);
$id_motorista = (int) $_SESSION['usuario']['id'];

$query = $sql->prepare(
    "SELECT v.*, ve.placa, ve.modelo, ve.marca, ve.categoria_cnh_exigida,
            c.id AS checklist_id, c.preenchido_em
     FROM viagens v
     INNER JOIN veiculos ve ON ve.id = v.veiculo_id
     LEFT JOIN checklists_viagem c ON c.viagem_id = v.id
     WHERE v.id = ?
       AND v.motorista_id = ?
     LIMIT 1"
);
$query->bind_param('ii', $id, $id_motorista);
$query->execute();
$viagem = $query->get_result()->fetch_assoc();
$query->close();

if (!$viagem) {
    mensagem_erro('Viagem não encontrada.');
    voltar('viagens.php');
}

$checkin_realizado = $viagem['odometro_inicial'] !== null;
$checklist_realizado = !empty($viagem['checklist_id']);
$viagem_iniciada = $viagem['status'] === 'em_andamento';
$viagem_concluida = $viagem['status'] === 'concluida';

$titulo = 'Detalhes da Viagem';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="painel shadow-sm viagem-cabecalho">
    <div>
        <span class="etiqueta">Ordem de viagem #<?php echo $viagem['id']; ?></span>
        <h3><?php echo escapar($viagem['descricao_rota']); ?></h3>
        <p><?php echo escapar($viagem['marca'] . ' ' . $viagem['modelo'] . ' · ' . $viagem['placa']); ?></p>
    </div>

    <span class="status <?php echo classe_status($viagem['status']); ?>">
        <?php echo escapar(status_viagem_nome($viagem['status'])); ?>
    </span>
</div>

<div class="etapas-viagem painel shadow-sm">
    <div class="etapa <?php echo $checkin_realizado ? 'concluida-etapa' : 'ativa-etapa'; ?>">
        <span>1</span>
        <div>
            <strong>Check-in</strong>
            <small><?php echo $checkin_realizado ? 'Concluído' : 'Pendente'; ?></small>
        </div>
    </div>

    <div class="etapa <?php echo $checklist_realizado ? 'concluida-etapa' : ($checkin_realizado ? 'ativa-etapa' : ''); ?>">
        <span>2</span>
        <div>
            <strong>Checklist</strong>
            <small><?php echo $checklist_realizado ? 'Concluído' : 'Pendente'; ?></small>
        </div>
    </div>

    <div class="etapa <?php echo $viagem_iniciada ? 'ativa-etapa' : ($viagem_concluida ? 'concluida-etapa' : ''); ?>">
        <span>3</span>
        <div>
            <strong>Em trânsito</strong>
            <small><?php echo $viagem_iniciada ? 'Operação ativa' : ($viagem_concluida ? 'Concluído' : 'Aguardando'); ?></small>
        </div>
    </div>

    <div class="etapa <?php echo $viagem_concluida ? 'concluida-etapa' : ''; ?>">
        <span>4</span>
        <div>
            <strong>Check-out</strong>
            <small><?php echo $viagem_concluida ? 'Concluído' : 'Pendente'; ?></small>
        </div>
    </div>
</div>

<div class="perfil-grid viagem-grid">
    <div class="painel shadow-sm">
        <div class="painel-titulo">
            <div>
                <span class="mini-etiqueta">Rota</span>
                <h3>Informações da operação</h3>
            </div>
            <i class="fa-solid fa-road"></i>
        </div>

        <div class="dados-lista">
            <div class="dado"><span>Saída</span><strong><?php echo data_hora_br($viagem['data_partida']); ?></strong></div>
            <div class="dado"><span>Retorno previsto</span><strong><?php echo data_hora_br($viagem['previsao_retorno']); ?></strong></div>
            <div class="dado"><span>Veículo</span><strong><?php echo escapar($viagem['placa'] . ' - ' . $viagem['modelo']); ?></strong></div>
            <div class="dado"><span>CNH exigida</span><strong>Categoria <?php echo escapar($viagem['categoria_cnh_exigida']); ?></strong></div>
            <div class="dado">
                <span>Odômetro inicial</span>
                <strong>
                    <?php echo $viagem['odometro_inicial'] !== null
                        ? number_format($viagem['odometro_inicial'], 0, ',', '.') . ' km'
                        : 'Pendente'; ?>
                </strong>
            </div>
            <div class="dado">
                <span>Combustível inicial</span>
                <strong>
                    <?php echo $viagem['combustivel_inicial'] !== null
                        ? number_format($viagem['combustivel_inicial'], 1, ',', '.') . '%'
                        : 'Pendente'; ?>
                </strong>
            </div>
        </div>
    </div>

    <div class="painel shadow-sm">
        <div class="painel-titulo">
            <div>
                <span class="mini-etiqueta">Próxima ação</span>
                <h3>O que fazer agora</h3>
            </div>
            <i class="fa-solid fa-list-check"></i>
        </div>

        <div class="acoes-operacao">
            <?php if ($viagem['status'] === 'agendada' && !$checkin_realizado): ?>
                <a class="botao botao-principal shadow-sm" href="check-in.php?id=<?php echo $viagem['id']; ?>">Fazer check-in</a>
            <?php elseif ($viagem['status'] === 'agendada' && !$checklist_realizado): ?>
                <a class="botao botao-principal shadow-sm" href="checklist.php?id=<?php echo $viagem['id']; ?>">Preencher checklist</a>
            <?php elseif ($viagem['status'] === 'em_andamento'): ?>
                <div class="acoes-viagem-botoes">
                    <a class="botao botao-secundario" href="despesa.php?viagem_id=<?php echo $viagem['id']; ?>">Lançar despesa</a>
                    <a class="botao botao-principal shadow-sm" href="check-out.php?id=<?php echo $viagem['id']; ?>">Fazer check-out</a>
                </div>
            <?php elseif ($viagem['status'] === 'concluida'): ?>
                <div class="estado-vazio compacto">
                    <i class="fa-solid fa-circle-check"></i>
                    <p>Viagem concluída em <?php echo data_hora_br($viagem['retorno_real']); ?>.</p>
                </div>
            <?php else: ?>
                <div class="estado-vazio compacto">
                    <i class="fa-solid fa-ban"></i>
                    <p>Esta viagem foi cancelada.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="fin">
    <a class="botao botao-secundario" href="viagens.php">
        <i class="fa-solid fa-arrow-left"></i>
        Voltar para minhas viagens
    </a>
</div>

<?php include '../rodape.php'; ?>
