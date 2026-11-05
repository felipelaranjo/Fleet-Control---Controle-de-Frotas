<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('admin');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$registro = null;
$erro_formulario = '';

if ($id > 0) {
    $query = $sql->prepare(
        "SELECT *
         FROM filiais
         WHERE id = ?"
    );

    $query->bind_param('i', $id);
    $query->execute();
    $registro = $query->get_result()->fetch_assoc();
    $query->close();

    if (!$registro) {
        mensagem_erro('Filial não encontrada.');
        voltar('filiais.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = strtoupper(trim($_POST['estado'] ?? ''));
    $endereco = trim($_POST['endereco'] ?? '');
    $ativo = (int) ($_POST['ativo'] ?? 1) === 1 ? 1 : 0;
    $imagem = $registro['imagem'] ?? null;

    $cnpj_numeros = preg_replace('/\D/', '', $cnpj);

    if ($nome === '' || $cnpj === '' || $cidade === '' || $estado === '' || $endereco === '') {
        $erro_formulario = 'Preencha todos os campos obrigatórios.';
    } elseif (strlen($cnpj_numeros) !== 14) {
        $erro_formulario = 'O CNPJ deve possuir exatamente 14 números.';
    } elseif (strlen($estado) !== 2) {
        $erro_formulario = 'Informe a sigla do estado com 2 letras.';
    }

    if ($erro_formulario === '' && !empty($_FILES['imagem']['name'])) {
        $upload = salvar_imagem_upload(
            $_FILES['imagem'],
            __DIR__ . '/../uploads/filiais',
            'filial'
        );

        if ($upload['erro'] !== '') {
            $erro_formulario = $upload['erro'];
        } else {
            $imagem = $upload['arquivo'];
        }
    }

    if ($erro_formulario === '') {
        if ($id > 0) {
            $query = $sql->prepare(
                "UPDATE filiais
                 SET nome = ?, cnpj = ?, cidade = ?, estado = ?, endereco = ?, imagem = ?, ativo = ?
                 WHERE id = ?"
            );

            $query->bind_param(
                'ssssssii',
                $nome,
                $cnpj,
                $cidade,
                $estado,
                $endereco,
                $imagem,
                $ativo,
                $id
            );
        } else {
            $query = $sql->prepare(
                "INSERT INTO filiais
                (nome, cnpj, cidade, estado, endereco, imagem, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $query->bind_param(
                'ssssssi',
                $nome,
                $cnpj,
                $cidade,
                $estado,
                $endereco,
                $imagem,
                $ativo
            );
        }

        if ($query->execute()) {
            $registro_id = $id > 0 ? $id : $query->insert_id;

            registrar_log(
                $sql,
                $id > 0 ? 'EDITAR' : 'CADASTRAR',
                'filiais',
                $registro_id,
                $id > 0 ? 'Filial atualizada' : 'Filial cadastrada'
            );

            mensagem_sucesso($id > 0 ? 'Filial atualizada com sucesso.' : 'Filial cadastrada com sucesso.');
            $query->close();
            voltar('filiais.php');
        }

        $erro_formulario = 'Não foi possível salvar. Verifique se o CNPJ já está cadastrado.';
        $query->close();
    }

    $registro = [
        'nome' => $nome,
        'cnpj' => $cnpj,
        'cidade' => $cidade,
        'estado' => $estado,
        'endereco' => $endereco,
        'imagem' => $imagem,
        'ativo' => $ativo
    ];
}

$titulo = $id > 0 ? 'Editar Filial' : 'Cadastrar Filial';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Estrutura da empresa</span>
        <h3><?php echo $id > 0 ? 'Atualizar filial' : 'Nova filial'; ?></h3>
    </div>

    <a href="filiais.php" class="botao botao-secundario">
        <i class="fa-solid fa-arrow-left"></i>
        Voltar
    </a>
</div>

<?php if ($erro_formulario !== ''): ?>
    <div class="alert alert-danger shadow-sm">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?php echo escapar($erro_formulario); ?>
    </div>
<?php endif; ?>

<div class="form-layout">
    <div class="imagem shadow-sm">
        <i class="fa-solid fa-building"></i>
        <span>Unidade corporativa</span>
    </div>

    <div class="contain-form painel shadow-sm">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="formulario">
                <div class="secao-form-titulo">
                    <div>
                        <strong>Identificação da filial</strong>
                    </div>
                    <i class="fa-solid fa-building"></i>
                </div>

                <div class="input-box">
                    <label for="nome">Nome da filial</label>
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="100"
                        placeholder="Ex.: Matriz São Paulo"
                        required
                        value="<?php echo escapar($registro['nome'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="cnpj">CNPJ</label>
                    <input
                        type="text"
                        id="cnpj"
                        name="cnpj"
                        maxlength="18"
                        inputmode="numeric"
                        data-mask="cnpj"
                        placeholder="00.000.000/0000-00"
                        required
                        value="<?php echo escapar($registro['cnpj'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="cidade">Cidade</label>
                    <input
                        type="text"
                        id="cidade"
                        name="cidade"
                        maxlength="80"
                        placeholder="Ex.: São Paulo"
                        required
                        value="<?php echo escapar($registro['cidade'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="estado">Estado</label>
                    <input
                        type="text"
                        id="estado"
                        name="estado"
                        maxlength="2"
                        data-uppercase
                        placeholder="SP"
                        required
                        value="<?php echo escapar($registro['estado'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box completa">
                    <label for="endereco">Endereço</label>
                    <input
                        type="text"
                        id="endereco"
                        name="endereco"
                        maxlength="200"
                        placeholder="Rua, número e bairro"
                        required
                        value="<?php echo escapar($registro['endereco'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box completa">
                    <label for="imagem">Imagem da filial <small>(opcional)</small></label>

                    <div class="upload-preview">
                        <div class="preview-imagem" id="preview-imagem-filial">
                            <?php if (!empty($registro['imagem'])): ?>
                                <img src="../uploads/filiais/<?php echo escapar($registro['imagem']); ?>" alt="Imagem atual da filial">
                            <?php else: ?>
                                <i class="fa-solid fa-building"></i>
                            <?php endif; ?>
                        </div>

                        <div>
                            <strong>Foto da unidade</strong>
                            <span>JPG, PNG ou WEBP com até 2 MB.</span>
                        </div>
                    </div>

                    <input
                        type="file"
                        id="imagem"
                        name="imagem"
                        accept="image/jpeg,image/png,image/webp"
                        data-image-preview="#preview-imagem-filial"
                    >
                </div>

                <div class="input-box">
                    <label for="ativo">Situação</label>
                    <select id="ativo" name="ativo">
                        <option value="1" <?php echo ((int) ($registro['ativo'] ?? 1) === 1) ? 'selected' : ''; ?>>Ativa</option>
                        <option value="0" <?php echo ((int) ($registro['ativo'] ?? 1) === 0) ? 'selected' : ''; ?>>Inativa</option>
                    </select>
                </div>

                <div class="fin">
                    <a class="botao botao-secundario" href="filiais.php">Cancelar</a>
                    <button class="botao botao-principal shadow-sm" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <?php echo $id > 0 ? 'Salvar alterações' : 'Cadastrar filial'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../rodape.php'; ?>
