<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('motorista');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$id_motorista = (int) $_SESSION['usuario']['id'];
$erro_formulario = '';

$query = $sql->prepare(
    "SELECT v.id, v.veiculo_id, v.status, v.odometro_inicial,
            ve.placa, ve.modelo
     FROM viagens v
     INNER JOIN veiculos ve ON ve.id = v.veiculo_id
     WHERE v.id = ?
       AND v.motorista_id = ?
     LIMIT 1"
);
$query->bind_param('ii', $id, $id_motorista);
$query->execute();
$viagem = $query->get_result()->fetch_assoc();
$query->close();

if (!$viagem || $viagem['status'] !== 'agendada') {
    mensagem_erro('Esta viagem não está disponível para checklist.');
    voltar('viagens.php');
}

if ($viagem['odometro_inicial'] === null) {
    mensagem_erro('Faça o check-in antes de preencher o checklist.');
    voltar('check-in.php?id=' . $id);
}

$query = $sql->prepare(
    "SELECT *
     FROM checklists_viagem
     WHERE viagem_id = ?"
);
$query->bind_param('i', $id);
$query->execute();
$checklist_existente = $query->get_result()->fetch_assoc();
$query->close();

if ($checklist_existente) {
    mensagem_sucesso('O checklist desta viagem já foi concluído.');
    voltar('viagem-detalhes.php?id=' . $id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pneus = $_POST['pneus'] ?? '';
    $luzes_setas = $_POST['luzes_setas'] ?? '';
    $estepe_ferramentas = $_POST['estepe_ferramentas'] ?? '';
    $fluidos = $_POST['fluidos'] ?? '';
    $observacoes = trim($_POST['observacoes'] ?? '');
    $foto_caminho = null;

    $valores_permitidos = ['conforme', 'nao_conforme'];

    if (
        !in_array($pneus, $valores_permitidos, true) ||
        !in_array($luzes_setas, $valores_permitidos, true) ||
        !in_array($estepe_ferramentas, $valores_permitidos, true) ||
        !in_array($fluidos, $valores_permitidos, true)
    ) {
        $erro_formulario = 'Preencha todos os itens do checklist.';
    }

    if ($erro_formulario === '' && !empty($_FILES['foto']['name'])) {
        $upload = salvar_imagem_upload(
            $_FILES['foto'],
            __DIR__ . '/../uploads/checklists',
            'check'
        );

        if ($upload['erro'] !== '') {
            $erro_formulario = $upload['erro'];
        } else {
            $foto_caminho = 'checklists/' . $upload['arquivo'];
        }
    }

    if ($erro_formulario === '') {
        $sql->begin_transaction();

        try {
            $query = $sql->prepare(
                "INSERT INTO checklists_viagem
                (viagem_id, pneus, luzes_setas, estepe_ferramentas, fluidos, observacoes, foto_caminho)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $query->bind_param(
                'issssss',
                $id,
                $pneus,
                $luzes_setas,
                $estepe_ferramentas,
                $fluidos,
                $observacoes,
                $foto_caminho
            );

            if (!$query->execute()) {
                throw new Exception('Falha ao salvar checklist.');
            }

            $checklist_id = $query->insert_id;
            $query->close();

            $query = $sql->prepare(
                "UPDATE viagens
                 SET status = 'em_andamento'
                 WHERE id = ?
                   AND motorista_id = ?
                   AND status = 'agendada'"
            );
            $query->bind_param('ii', $id, $id_motorista);

            if (!$query->execute() || $query->affected_rows !== 1) {
                throw new Exception('Não foi possível liberar a viagem.');
            }

            $query->close();

            $query = $sql->prepare(
                "UPDATE veiculos
                 SET status = 'em_transito', odometro_atual = ?
                 WHERE id = ?"
            );
            $odometro_inicial = (int) $viagem['odometro_inicial'];
            $veiculo_id = (int) $viagem['veiculo_id'];

            $query->bind_param('ii', $odometro_inicial, $veiculo_id);
            $query->execute();
            $query->close();

            $sql->commit();

            registrar_log($sql, 'CHECKLIST', 'checklists_viagem', $checklist_id, 'Checklist concluído e viagem liberada');
            mensagem_sucesso('Checklist concluído. A viagem foi liberada para iniciar.');
            voltar('viagem-detalhes.php?id=' . $id);
        } catch (Throwable $erro) {
            $sql->rollback();
            $erro_formulario = 'Não foi possível concluir o checklist.';
        }
    }
}

$titulo = 'Checklist de Vistoria';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Etapa obrigatória</span>
        <h3>Vistoria antes da partida</h3>
    </div>

    <span class="status reservada"><?php echo escapar($viagem['placa'] . ' - ' . $viagem['modelo']); ?></span>
</div>

<?php if ($erro_formulario !== ''): ?>
    <div class="alert alert-danger shadow-sm"><?php echo escapar($erro_formulario); ?></div>
<?php endif; ?>

<div class="painel shadow-sm checklist-painel">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="checklist-grid">
            <?php
            $itens = [
                'pneus' => ['Pneus', 'fa-circle-dot'],
                'luzes_setas' => ['Luzes e setas', 'fa-lightbulb'],
                'estepe_ferramentas' => ['Estepe e ferramentas', 'fa-toolbox'],
                'fluidos' => ['Fluidos', 'fa-droplet']
            ];
            ?>

            <?php foreach ($itens as $campo => $dados): ?>
                <div class="check-item">
                    <span class="check-item-icone"><i class="fa-solid <?php echo $dados[1]; ?>"></i></span>
                    <div>
                        <strong><?php echo $dados[0]; ?></strong>
                        <select name="<?php echo $campo; ?>" required>
                            <option value="">Selecione</option>
                            <option value="conforme" <?php echo (($_POST[$campo] ?? '') === 'conforme') ? 'selected' : ''; ?>>Conforme</option>
                            <option value="nao_conforme" <?php echo (($_POST[$campo] ?? '') === 'nao_conforme') ? 'selected' : ''; ?>>Não conforme</option>
                        </select>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="formulario checklist-complemento">
            <div class="input-box completa">
                <label for="observacoes">Observações</label>
                <textarea
                    id="observacoes"
                    name="observacoes"
                    maxlength="800"
                    placeholder="Registre avarias, ruídos ou outras observações relevantes."
                ><?php echo escapar($_POST['observacoes'] ?? ''); ?></textarea>
            </div>

            <div class="input-box completa">
                <label for="foto">Foto de eventual dano <small>(opcional)</small></label>

                <div class="upload-preview">
                    <div class="preview-imagem" id="preview-checklist">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <div>
                        <strong>Registro visual</strong>
                        <span>JPG, PNG ou WEBP com até 2 MB.</span>
                    </div>
                </div>

                <input
                    type="file"
                    id="foto"
                    name="foto"
                    accept="image/jpeg,image/png,image/webp"
                    data-image-preview="#preview-checklist"
                >
            </div>

            <div class="fin">
                <a class="botao botao-secundario" href="viagem-detalhes.php?id=<?php echo $id; ?>">Voltar</a>
                <button class="botao botao-principal shadow-sm" type="submit">
                    <i class="fa-solid fa-clipboard-check"></i>
                    Concluir checklist
                </button>
            </div>
        </div>
    </form>
</div>

<?php include '../rodape.php'; ?>
