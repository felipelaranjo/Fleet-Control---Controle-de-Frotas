<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('motorista');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$id_motorista = (int) $_SESSION['usuario']['id'];
$erro_formulario = '';

$query = $sql->prepare(
    "SELECT v.id, v.veiculo_id, v.status, v.odometro_inicial,
            v.combustivel_inicial, v.descricao_rota,
            ve.placa, ve.modelo,
            c.id AS checklist_id
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

if (!$viagem || $viagem['status'] !== 'em_andamento') {
    mensagem_erro('Esta viagem não está disponível para check-out.');
    voltar('viagens.php');
}

if (empty($viagem['checklist_id'])) {
    mensagem_erro('O checklist obrigatório não foi encontrado para esta viagem.');
    voltar('viagem-detalhes.php?id=' . $id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $odometro_final = (int) ($_POST['odometro_final'] ?? 0);
    $combustivel_final = (float) ($_POST['combustivel_final'] ?? -1);
    $observacoes = trim($_POST['observacoes_encerramento'] ?? '');

    if ($odometro_final > 9999999) {
        $erro_formulario = 'A quilometragem informada é muito alta.';
    } elseif ($odometro_final < (int) $viagem['odometro_inicial']) {
        $erro_formulario = 'O odômetro final não pode ser menor que o odômetro inicial.';
    } elseif ($combustivel_final < 0 || $combustivel_final > 100) {
        $erro_formulario = 'O nível final de combustível deve estar entre 0 e 100%.';
    }

    if ($erro_formulario === '') {
        $sql->begin_transaction();

        try {
            $query = $sql->prepare(
                "UPDATE viagens
                 SET odometro_final = ?, combustivel_final = ?,
                     observacoes_encerramento = ?, retorno_real = NOW(), status = 'concluida'
                 WHERE id = ?
                   AND motorista_id = ?
                   AND status = 'em_andamento'"
            );

            $query->bind_param(
                'idsii',
                $odometro_final,
                $combustivel_final,
                $observacoes,
                $id,
                $id_motorista
            );

            if (!$query->execute() || $query->affected_rows !== 1) {
                throw new Exception('Falha ao encerrar a viagem.');
            }

            $query->close();

            $query = $sql->prepare(
                "UPDATE veiculos
                 SET odometro_atual = ?, status = 'disponivel'
                 WHERE id = ?"
            );
            $veiculo_id = (int) $viagem['veiculo_id'];
            $query->bind_param('ii', $odometro_final, $veiculo_id);
            $query->execute();
            $query->close();

            $sql->commit();

            registrar_log($sql, 'CHECK-OUT', 'viagens', $id, 'Viagem encerrada pelo motorista');
            mensagem_sucesso('Viagem encerrada com sucesso.');
            voltar('dashboard.php');
        } catch (Throwable $erro) {
            $sql->rollback();
            $erro_formulario = 'Não foi possível encerrar a viagem.';
        }
    }
}

$titulo = 'Check-out de Viagem';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Encerramento da operação</span>
        <h3>Finalizar viagem</h3>
    </div>
    <span class="status andamento"><?php echo escapar($viagem['placa'] . ' - ' . $viagem['modelo']); ?></span>
</div>

<?php if ($erro_formulario !== ''): ?>
    <div class="alert alert-danger shadow-sm"><?php echo escapar($erro_formulario); ?></div>
<?php endif; ?>

<div class="form-layout">
    <div class="imagem shadow-sm">
        <i class="fa-solid fa-flag-checkered"></i>
        <span><?php echo escapar($viagem['placa']); ?></span>
    </div>

    <div class="contain-form painel shadow-sm">
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="formulario">
                <div class="aviso-form completa">
                    <i class="fa-solid fa-route"></i>
                    <span><?php echo escapar($viagem['descricao_rota']); ?> · início em <?php echo number_format($viagem['odometro_inicial'], 0, ',', '.'); ?> km</span>
                </div>

                <div class="input-box">
                    <label for="odometro_final">Odômetro final</label>
                    <input
                        type="number"
                        id="odometro_final"
                        name="odometro_final"
                        min="<?php echo (int) $viagem['odometro_inicial']; ?>"
                        max="9999999"
                        step="1"
                        inputmode="numeric"
                        placeholder="Quilometragem ao retornar"
                        required
                        value="<?php echo escapar($_POST['odometro_final'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="combustivel_final">Combustível final (%)</label>
                    <input
                        type="number"
                        id="combustivel_final"
                        name="combustivel_final"
                        min="0"
                        max="100"
                        step="0.1"
                        inputmode="decimal"
                        placeholder="Ex.: 40"
                        required
                        value="<?php echo escapar($_POST['combustivel_final'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box completa">
                    <label for="observacoes_encerramento">Avarias ou observações</label>
                    <textarea
                        id="observacoes_encerramento"
                        name="observacoes_encerramento"
                        maxlength="800"
                        placeholder="Informe problemas mecânicos, colisões ou outras ocorrências."
                    ><?php echo escapar($_POST['observacoes_encerramento'] ?? ''); ?></textarea>
                </div>

                <div class="fin">
                    <a class="botao botao-secundario" href="viagem-detalhes.php?id=<?php echo $id; ?>">Voltar</a>
                    <button class="botao botao-principal shadow-sm" type="submit">Encerrar viagem</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../rodape.php'; ?>
