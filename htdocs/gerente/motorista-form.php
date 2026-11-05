<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$visao_global = $perfil_atual === 'admin';
$filial_usuario = filial_do_usuario();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$erro_formulario = '';

if ($id <= 0) {
    mensagem_erro('Motorista não informado.');
    voltar('motoristas.php');
}

$sql_consulta =
    "SELECT u.id, u.nome, u.cpf, u.rg, u.email, u.telefone,
            u.filial_id, u.ativo, u.foto,
            m.cnh_numero, m.cnh_categoria, m.cnh_validade
     FROM usuarios u
     LEFT JOIN motoristas m ON m.usuario_id = u.id
     WHERE u.id = ?
       AND u.perfil = 'motorista'";

if (!$visao_global) {
    $sql_consulta .= " AND u.filial_id = " . (int) $filial_usuario;
}

$sql_consulta .= " LIMIT 1";

$query = $sql->prepare($sql_consulta);
$query->bind_param('i', $id);
$query->execute();
$motorista = $query->get_result()->fetch_assoc();
$query->close();

if (!$motorista) {
    mensagem_erro('Motorista não encontrado ou fora da sua filial.');
    voltar('motoristas.php');
}

$nome = $motorista['nome'];
$cpf = $motorista['cpf'];
$rg = $motorista['rg'];
$email = $motorista['email'];
$telefone = $motorista['telefone'];
$filial_atual = (int) $motorista['filial_id'];
$ativo = (int) $motorista['ativo'];
$foto = $motorista['foto'] ?? null;
$cnh_numero = $motorista['cnh_numero'] ?? '';
$cnh_categoria = $motorista['cnh_categoria'] ?? '';
$cnh_validade = $motorista['cnh_validade'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $ativo = (int) ($_POST['ativo'] ?? 1) === 1 ? 1 : 0;
    $cnh_numero = trim($_POST['cnh_numero'] ?? '');
    $cnh_categoria = $_POST['cnh_categoria'] ?? '';
    $cnh_validade = $_POST['cnh_validade'] ?? '';
    $foto = $motorista['foto'] ?? $foto;

    if ($visao_global) {
        $filial_atual = (int) ($_POST['filial_id'] ?? 0);
    } else {
        $filial_atual = $filial_usuario;
    }

    $telefone_numeros = preg_replace('/\D/', '', $telefone);
    $cnh_numeros = preg_replace('/\D/', '', $cnh_numero);

    if ($nome === '' || $email === '' || $cnh_numero === '' || $cnh_categoria === '' || $cnh_validade === '') {
        $erro_formulario = 'Preencha todos os campos obrigatórios, incluindo os dados da CNH.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro_formulario = 'Informe um e-mail válido.';
    } elseif ($telefone !== '' && !in_array(strlen($telefone_numeros), [10, 11], true)) {
        $erro_formulario = 'Informe um telefone válido com DDD.';
    } elseif (strlen($cnh_numeros) !== 11) {
        $erro_formulario = 'A CNH deve possuir exatamente 11 números.';
    } elseif (!in_array($cnh_categoria, ['A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'], true)) {
        $erro_formulario = 'Selecione uma categoria de CNH válida.';
    } elseif ($filial_atual <= 0) {
        $erro_formulario = 'O motorista deve estar vinculado a uma filial.';
    }

    if ($erro_formulario === '' && !empty($_FILES['foto']['name'])) {
        $upload = salvar_imagem_upload(
            $_FILES['foto'],
            __DIR__ . '/../uploads/usuarios',
            'usuario'
        );

        if ($upload['erro'] !== '') {
            $erro_formulario = $upload['erro'];
        } else {
            $foto = $upload['arquivo'];
        }
    }

    if ($erro_formulario === '') {
        $sql->begin_transaction();

        try {
            $query = $sql->prepare(
                "UPDATE usuarios
                 SET nome = ?, rg = ?, email = ?, telefone = ?, foto = ?, filial_id = ?, ativo = ?
                 WHERE id = ?
                   AND perfil = 'motorista'"
            );

            $query->bind_param(
                'sssssiii',
                $nome,
                $rg,
                $email,
                $telefone,
                $foto,
                $filial_atual,
                $ativo,
                $id
            );

            if (!$query->execute()) {
                throw new Exception('Falha ao atualizar o usuário.');
            }

            $query->close();

            $query = $sql->prepare(
                "INSERT INTO motoristas
                (usuario_id, cnh_numero, cnh_categoria, cnh_validade)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    cnh_numero = VALUES(cnh_numero),
                    cnh_categoria = VALUES(cnh_categoria),
                    cnh_validade = VALUES(cnh_validade)"
            );

            $query->bind_param(
                'isss',
                $id,
                $cnh_numero,
                $cnh_categoria,
                $cnh_validade
            );

            if (!$query->execute()) {
                throw new Exception('Falha ao atualizar a CNH.');
            }

            $query->close();
            $sql->commit();

            registrar_log(
                $sql,
                'EDITAR',
                'motoristas',
                $id,
                $visao_global ? 'Motorista atualizado pelo Administrador' : 'Motorista atualizado pelo Gerente de Frota'
            );

            mensagem_sucesso('Informações do motorista atualizadas com sucesso.');
            voltar('motoristas.php');
        } catch (Throwable $erro) {
            $sql->rollback();
            $erro_formulario = 'Não foi possível salvar. Verifique e-mail e número da CNH.';
        }
    }
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

$titulo = 'Editar Motorista';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta"><?php echo $visao_global ? 'Administrador' : 'Nível 2 - Gerencial'; ?></span>
        <h3>Editar informações do motorista</h3>
    </div>

    <a href="motoristas.php" class="botao botao-secundario">
        <i class="fa-solid fa-arrow-left"></i>
        Voltar
    </a>
</div>

<?php if ($erro_formulario !== ''): ?>
    <div class="alert alert-danger shadow-sm">
        <?php echo escapar($erro_formulario); ?>
    </div>
<?php endif; ?>

<div class="form-layout">
    <div class="imagem shadow-sm">
        <i class="fa-solid fa-id-card"></i>
        <span>Condutor da frota</span>
    </div>

    <div class="contain-form painel shadow-sm">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="formulario">
                <div class="secao-form-titulo">
                    <div>
                        <strong>Dados do motorista</strong>
                        <span>Informações de contato e identificação do condutor.</span>
                    </div>
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="input-box">
                    <label for="nome">Nome completo</label>
                    <input type="text" id="nome" name="nome" maxlength="100" autocomplete="name" placeholder="Nome completo" required value="<?php echo escapar($nome); ?>">
                </div>

                <div class="input-box">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" value="<?php echo escapar($cpf); ?>" readonly>
                </div>

                <div class="input-box">
                    <label for="rg">RG</label>
                    <input type="text" id="rg" name="rg" maxlength="20" placeholder="Documento de identidade" value="<?php echo escapar($rg); ?>">
                </div>

                <div class="input-box">
                    <label for="telefone">Telefone</label>
                    <input
                        type="text"
                        id="telefone"
                        name="telefone"
                        maxlength="15"
                        inputmode="numeric"
                        data-mask="telefone"
                        placeholder="(11) 99999-9999"
                        value="<?php echo escapar($telefone); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        autocomplete="email"
                        placeholder="motorista@empresa.com"
                        required
                        value="<?php echo escapar($email); ?>"
                    >
                </div>

                <div class="input-box completa">
                    <label for="foto">Foto do motorista <small>(opcional)</small></label>
                    <div class="upload-preview">
                        <div class="preview-imagem circular" id="preview-foto-motorista">
                            <?php if (!empty($foto)): ?>
                                <img src="../uploads/usuarios/<?php echo escapar($foto); ?>" alt="Foto atual">
                            <?php else: ?>
                                <i class="fa-solid fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong>Imagem de perfil</strong>
                            <span>JPG, PNG ou WEBP com até 2 MB.</span>
                        </div>
                    </div>
                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" data-image-preview="#preview-foto-motorista">
                </div>

                <?php if ($visao_global): ?>
                    <div class="input-box">
                        <label for="filial_id">Filial</label>
                        <select id="filial_id" name="filial_id" required>
                            <option value="">Selecione</option>

                            <?php while ($filial = $filiais->fetch_assoc()): ?>
                                <option
                                    value="<?php echo $filial['id']; ?>"
                                    <?php echo $filial_atual === (int) $filial['id'] ? 'selected' : ''; ?>
                                >
                                    <?php echo escapar($filial['nome']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="secao-form-titulo">
                    <div>
                        <strong>Habilitação e situação</strong>
                    </div>
                    <i class="fa-solid fa-id-card"></i>
                </div>

                <div class="input-box">
                    <label for="cnh_numero">Número da CNH</label>
                    <input
                        type="text"
                        id="cnh_numero"
                        name="cnh_numero"
                        maxlength="11"
                        inputmode="numeric"
                        data-mask="cnh"
                        placeholder="00000000000"
                        required
                        value="<?php echo escapar($cnh_numero); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="cnh_categoria">Categoria</label>
                    <select id="cnh_categoria" name="cnh_categoria" required>
                        <option value="">Selecione</option>

                        <?php foreach (['A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'] as $categoria): ?>
                            <option value="<?php echo $categoria; ?>" <?php echo $cnh_categoria === $categoria ? 'selected' : ''; ?>>
                                <?php echo $categoria; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-box">
                    <label for="cnh_validade">Validade da CNH</label>
                    <input type="date" id="cnh_validade" name="cnh_validade" required value="<?php echo escapar($cnh_validade); ?>">
                </div>

                <div class="input-box">
                    <label for="ativo">Situação</label>
                    <select id="ativo" name="ativo">
                        <option value="1" <?php echo $ativo === 1 ? 'selected' : ''; ?>>Ativo</option>
                        <option value="0" <?php echo $ativo === 0 ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>

                <div class="fin">
                    <a class="botao botao-secundario" href="motoristas.php">Cancelar</a>
                    <button class="botao botao-principal shadow-sm" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Salvar alterações
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>



<?php include '../rodape.php'; ?>
