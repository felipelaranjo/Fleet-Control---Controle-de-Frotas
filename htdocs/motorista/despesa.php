<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('motorista');

$id_motorista = (int) $_SESSION['usuario']['id'];
$viagem_selecionada = (int) ($_GET['viagem_id'] ?? $_POST['viagem_id'] ?? 0);
$erro_formulario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $viagem_id = (int) ($_POST['viagem_id'] ?? 0);
    $categoria = $_POST['categoria'] ?? '';
    $valor = (float) ($_POST['valor'] ?? 0);
    $odometro = (int) ($_POST['odometro_momento'] ?? 0);
    $comprovante_caminho = null;

    if (!in_array($categoria, ['combustivel', 'pedagio', 'manutencao_emergencia'], true)) {
        $erro_formulario = 'Selecione uma categoria de despesa válida.';
    } elseif ($valor <= 0) {
        $erro_formulario = 'Informe um valor maior que zero.';
    } elseif ($valor > 99999999.99) {
        $erro_formulario = 'O valor informado ultrapassa o limite permitido.';
    } elseif ($odometro > 9999999) {
        $erro_formulario = 'A quilometragem informada é muito alta.';
    }

    if ($erro_formulario === '') {
        $query = $sql->prepare(
            "SELECT id, odometro_inicial
             FROM viagens
             WHERE id = ?
               AND motorista_id = ?
               AND status = 'em_andamento'"
        );
        $query->bind_param('ii', $viagem_id, $id_motorista);
        $query->execute();
        $viagem = $query->get_result()->fetch_assoc();
        $query->close();

        if (!$viagem) {
            $erro_formulario = 'Você só pode lançar despesas em uma viagem sua que esteja em andamento.';
        } elseif ($odometro > 0 && $odometro < (int) $viagem['odometro_inicial']) {
            $erro_formulario = 'O odômetro da despesa não pode ser menor que o odômetro do check-in.';
        }
    }

    if ($erro_formulario === '' && !empty($_FILES['comprovante']['name'])) {
        $extensao = strtolower(pathinfo($_FILES['comprovante']['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($extensao, $permitidos, true)) {
            $erro_formulario = 'O comprovante deve ser JPG, PNG ou PDF.';
        } elseif ((int) $_FILES['comprovante']['size'] > 5 * 1024 * 1024) {
            $erro_formulario = 'O comprovante deve ter no máximo 5 MB.';
        } else {
            $nome_arquivo = uniqid('comp_', true) . '.' . $extensao;
            $comprovante_caminho = 'comprovantes/' . $nome_arquivo;
            $destino = __DIR__ . '/../uploads/' . $comprovante_caminho;

            if (!move_uploaded_file($_FILES['comprovante']['tmp_name'], $destino)) {
                $erro_formulario = 'Não foi possível salvar o comprovante.';
                $comprovante_caminho = null;
            }
        }
    }

    if ($erro_formulario === '') {
        $query = $sql->prepare(
            "INSERT INTO despesas_viagem
            (viagem_id, categoria, valor, odometro_momento, comprovante_caminho)
            VALUES (?, ?, ?, NULLIF(?, 0), ?)"
        );

        $query->bind_param(
            'isdis',
            $viagem_id,
            $categoria,
            $valor,
            $odometro,
            $comprovante_caminho
        );

        if ($query->execute()) {
            $despesa_id = $query->insert_id;
            registrar_log($sql, 'DESPESA', 'despesas_viagem', $despesa_id, 'Despesa lançada durante a viagem');
            mensagem_sucesso('Despesa lançada com sucesso.');
            $query->close();
            voltar('viagem-detalhes.php?id=' . $viagem_id);
        }

        $erro_formulario = 'Não foi possível lançar a despesa.';
        $query->close();
    }
}

$query = $sql->prepare(
    "SELECT id, descricao_rota
     FROM viagens
     WHERE motorista_id = ?
       AND status = 'em_andamento'
     ORDER BY data_partida DESC"
);
$query->bind_param('i', $id_motorista);
$query->execute();
$viagens = $query->get_result();

$titulo = 'Lançar Despesa';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Prestação de contas</span>
        <h3>Registrar despesa da viagem</h3>
    </div>
</div>

<?php if ($erro_formulario !== ''): ?>
    <div class="alert alert-danger shadow-sm"><?php echo escapar($erro_formulario); ?></div>
<?php endif; ?>

<div class="form-layout">
    <div class="imagem shadow-sm">
        <i class="fa-solid fa-receipt"></i>
        <span>Prestação de contas</span>
    </div>

    <div class="contain-form painel shadow-sm">
        <?php if ($viagens->num_rows === 0): ?>
            <div class="estado-vazio">
                <i class="fa-solid fa-circle-info"></i>
                <strong>Nenhuma viagem em andamento</strong>
                <span>As despesas só podem ser lançadas durante uma viagem ativa.</span>
            </div>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="formulario">
                    <div class="secao-form-titulo">
                        <div>
                            <strong>Dados da despesa</strong>
                            <span>O registro ficará vinculado à viagem selecionada.</span>
                        </div>
                        <i class="fa-solid fa-receipt"></i>
                    </div>

                    <div class="input-box completa">
                        <label for="viagem_id">Viagem</label>
                        <select id="viagem_id" name="viagem_id" required>
                            <option value="">Selecione</option>

                            <?php while ($viagem = $viagens->fetch_assoc()): ?>
                                <option value="<?php echo $viagem['id']; ?>" <?php echo $viagem_selecionada === (int) $viagem['id'] ? 'selected' : ''; ?>>
                                    <?php echo escapar($viagem['descricao_rota']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="input-box">
                        <label for="categoria">Categoria</label>
                        <select id="categoria" name="categoria" required>
                            <option value="combustivel">Combustível</option>
                            <option value="pedagio">Pedágio</option>
                            <option value="manutencao_emergencia">Manutenção de emergência</option>
                        </select>
                    </div>

                    <div class="input-box">
                        <label for="valor">Valor</label>
                        <input
                            type="number"
                            id="valor"
                            name="valor"
                            min="0.01"
                            max="99999999.99"
                            step="0.01"
                            inputmode="decimal"
                            placeholder="0,00"
                            required
                            value="<?php echo escapar($_POST['valor'] ?? ''); ?>"
                        >
                    </div>

                    <div class="input-box">
                        <label for="odometro_momento">Odômetro no momento</label>
                        <input
                            type="number"
                            id="odometro_momento"
                            name="odometro_momento"
                            min="0"
                            max="9999999"
                            step="1"
                            inputmode="numeric"
                            placeholder="Opcional"
                            value="<?php echo escapar($_POST['odometro_momento'] ?? ''); ?>"
                        >
                    </div>

                    <div class="input-box completa">
                        <label for="comprovante">Comprovante</label>
                        <input type="file" id="comprovante" name="comprovante" accept="image/jpeg,image/png,application/pdf">
                        <small class="texto-ajuda">JPG, PNG ou PDF. Máximo de 5 MB.</small>
                    </div>

                    <div class="fin">
                        <a class="botao botao-secundario" href="dashboard.php">Cancelar</a>
                        <button class="botao botao-principal shadow-sm" type="submit">Lançar despesa</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php
$query->close();
include '../rodape.php';
?>
