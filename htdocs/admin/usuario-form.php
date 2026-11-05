<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('admin');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$registro = null;
$motorista = null;
$erro_formulario = '';

if ($id > 0) {
    $query = $sql->prepare(
        "SELECT *
         FROM usuarios
         WHERE id = ?"
    );

    $query->bind_param('i', $id);
    $query->execute();
    $registro = $query->get_result()->fetch_assoc();
    $query->close();

    if (!$registro) {
        mensagem_erro('Usuário não encontrado.');
        voltar('usuarios.php');
    }

    if ($registro['perfil'] === 'motorista') {
        $query = $sql->prepare(
            "SELECT *
             FROM motoristas
             WHERE usuario_id = ?"
        );

        $query->bind_param('i', $id);
        $query->execute();
        $motorista = $query->get_result()->fetch_assoc();
        $query->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $cpf = trim($_POST['cpf'] ?? '');
    $rg = trim($_POST['rg'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $perfil = $id > 0 && $registro ? $registro['perfil'] : ($_POST['perfil'] ?? '');
    $filial_id = (int) ($_POST['filial_id'] ?? 0);
    $ativo = (int) ($_POST['ativo'] ?? 1) === 1 ? 1 : 0;

    $cnh_numero = trim($_POST['cnh_numero'] ?? '');
    $cnh_categoria = $_POST['cnh_categoria'] ?? '';
    $cnh_validade = $_POST['cnh_validade'] ?? '';

    $foto = $registro['foto'] ?? null;

    $cpf_numeros = preg_replace('/\D/', '', $cpf);
    $telefone_numeros = preg_replace('/\D/', '', $telefone);
    $cnh_numeros = preg_replace('/\D/', '', $cnh_numero);

    if ($nome === '' || $cpf === '' || $email === '' || $perfil === '') {
        $erro_formulario = 'Preencha todos os campos obrigatórios.';
    } elseif (strlen($cpf_numeros) !== 11) {
        $erro_formulario = 'O CPF deve possuir exatamente 11 números.';
    } elseif ($telefone !== '' && !in_array(strlen($telefone_numeros), [10, 11], true)) {
        $erro_formulario = 'Informe um telefone com DDD e 10 ou 11 números.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro_formulario = 'Informe um endereço de e-mail válido.';
    } elseif (!in_array($perfil, ['admin', 'gerente', 'motorista'], true)) {
        $erro_formulario = 'Selecione um perfil válido.';
    } elseif (in_array($perfil, ['gerente', 'motorista'], true) && $filial_id <= 0) {
        $erro_formulario = 'Gerentes e motoristas devem estar vinculados a uma filial.';
    } elseif ($id === 0 && $senha === '') {
        $erro_formulario = 'Informe uma senha para o novo usuário.';
    } elseif ($senha !== '' && strlen($senha) < 6) {
        $erro_formulario = 'A senha deve possuir pelo menos 6 caracteres.';
    } elseif ($senha !== '' && $senha !== $confirmar_senha) {
        $erro_formulario = 'A confirmação da senha não confere.';
    } elseif ($perfil === 'motorista' && strlen($cnh_numeros) !== 11) {
        $erro_formulario = 'A CNH é obrigatória e deve possuir exatamente 11 números.';
    } elseif ($perfil === 'motorista' && !in_array($cnh_categoria, ['A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'], true)) {
        $erro_formulario = 'Selecione uma categoria de CNH válida.';
    } elseif ($perfil === 'motorista' && $cnh_validade === '') {
        $erro_formulario = 'Informe a validade da CNH do motorista.';
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
            if ($id > 0) {
                if ($senha !== '') {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                    $query = $sql->prepare(
                        "UPDATE usuarios
                         SET nome = ?, cpf = ?, rg = ?, email = ?, telefone = ?, foto = ?,
                             senha_hash = ?, perfil = ?, filial_id = ?, ativo = ?
                         WHERE id = ?"
                    );

                    $query->bind_param(
                        'ssssssssiii',
                        $nome,
                        $cpf,
                        $rg,
                        $email,
                        $telefone,
                        $foto,
                        $senha_hash,
                        $perfil,
                        $filial_id,
                        $ativo,
                        $id
                    );
                } else {
                    $query = $sql->prepare(
                        "UPDATE usuarios
                         SET nome = ?, cpf = ?, rg = ?, email = ?, telefone = ?, foto = ?,
                             perfil = ?, filial_id = ?, ativo = ?
                         WHERE id = ?"
                    );

                    $query->bind_param(
                        'sssssssiii',
                        $nome,
                        $cpf,
                        $rg,
                        $email,
                        $telefone,
                        $foto,
                        $perfil,
                        $filial_id,
                        $ativo,
                        $id
                    );
                }

                if (!$query->execute()) {
                    throw new Exception('Não foi possível atualizar o usuário.');
                }

                $query->close();
                $usuario_id = $id;
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                $query = $sql->prepare(
                    "INSERT INTO usuarios
                    (nome, cpf, rg, email, telefone, foto, senha_hash, perfil, filial_id, ativo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $query->bind_param(
                    'ssssssssii',
                    $nome,
                    $cpf,
                    $rg,
                    $email,
                    $telefone,
                    $foto,
                    $senha_hash,
                    $perfil,
                    $filial_id,
                    $ativo
                );

                if (!$query->execute()) {
                    throw new Exception('Não foi possível cadastrar o usuário.');
                }

                $usuario_id = $query->insert_id;
                $query->close();
            }

            if ($perfil === 'motorista') {
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
                    $usuario_id,
                    $cnh_numero,
                    $cnh_categoria,
                    $cnh_validade
                );

                if (!$query->execute()) {
                    throw new Exception('Não foi possível salvar a CNH do motorista.');
                }

                $query->close();
            } else {
                $query = $sql->prepare(
                    "DELETE FROM motoristas
                     WHERE usuario_id = ?"
                );

                $query->bind_param('i', $usuario_id);
                $query->execute();
                $query->close();
            }

            $sql->commit();

            if ($usuario_id === (int) $_SESSION['usuario']['id']) {
                $_SESSION['usuario']['nome'] = $nome;
                $_SESSION['usuario']['email'] = $email;
                $_SESSION['usuario']['telefone'] = $telefone;
                $_SESSION['usuario']['foto'] = $foto;
                $_SESSION['usuario']['filial_id'] = $filial_id;
            }

            registrar_log(
                $sql,
                $id > 0 ? 'EDITAR' : 'CADASTRAR',
                'usuarios',
                $usuario_id,
                $id > 0 ? 'Usuário atualizado pelo Administrador' : 'Usuário cadastrado pelo Administrador'
            );

            mensagem_sucesso($id > 0 ? 'Usuário atualizado com sucesso.' : 'Usuário cadastrado com sucesso.');
            voltar('usuarios.php');
        } catch (Throwable $erro) {
            $sql->rollback();

            if ($sql->errno === 1062 || strpos($erro->getMessage(), 'Duplicate') !== false) {
                $erro_formulario = 'CPF, e-mail ou número da CNH já está cadastrado.';
            } else {
                $erro_formulario = 'Não foi possível salvar o usuário. Verifique os dados e tente novamente.';
            }
        }
    }

    $registro = [
        'nome' => $nome,
        'cpf' => $cpf,
        'rg' => $rg,
        'email' => $email,
        'telefone' => $telefone,
        'foto' => $foto,
        'perfil' => $perfil,
        'filial_id' => $filial_id,
        'ativo' => $ativo
    ];

    $motorista = [
        'cnh_numero' => $cnh_numero,
        'cnh_categoria' => $cnh_categoria,
        'cnh_validade' => $cnh_validade
    ];
}

$filiais = $sql->query(
    "SELECT id, nome
     FROM filiais
     WHERE ativo = 1
     ORDER BY nome"
);

$titulo = $id > 0 ? 'Editar Usuário' : 'Cadastrar Usuário';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Nível 3 - Administrador</span>
        <h3><?php echo $id > 0 ? 'Editar cadastro' : 'Novo cadastro'; ?></h3>
    </div>

    <a href="usuarios.php" class="botao botao-secundario">
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
        <i class="fa-solid fa-user-gear"></i>
        <span>Cadastro corporativo</span>
    </div>

    <div class="contain-form painel shadow-sm">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="formulario">
                <div class="secao-form-titulo">
                    <div>
                        <strong>Dados pessoais</strong>
                        <span>Informações básicas de identificação e contato.</span>
                    </div>
                    <i class="fa-solid fa-user"></i>
                </div>

                <div class="input-box">
                    <label for="nome">Nome completo</label>
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="100"
                        autocomplete="name"
                        placeholder="Ex.: João da Silva"
                        required
                        value="<?php echo escapar($registro['nome'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="cpf">CPF</label>
                    <input
                        type="text"
                        id="cpf"
                        name="cpf"
                        maxlength="14"
                        inputmode="numeric"
                        data-mask="cpf"
                        placeholder="000.000.000-00"
                        required
                        value="<?php echo escapar($registro['cpf'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="rg">RG</label>
                    <input
                        type="text"
                        id="rg"
                        name="rg"
                        maxlength="20"
                        placeholder="Documento de identidade"
                        value="<?php echo escapar($registro['rg'] ?? ''); ?>"
                    >
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
                        autocomplete="tel"
                        placeholder="(11) 99999-9999"
                        value="<?php echo escapar($registro['telefone'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box completa">
                    <label for="foto">Foto do usuário <small>(opcional)</small></label>

                    <div class="upload-preview">
                        <div class="preview-imagem circular" id="preview-foto-usuario">
                            <?php if (!empty($registro['foto'])): ?>
                                <img src="../uploads/usuarios/<?php echo escapar($registro['foto']); ?>" alt="Foto atual">
                            <?php else: ?>
                                <i class="fa-solid fa-user"></i>
                            <?php endif; ?>
                        </div>

                        <div>
                            <strong>Imagem de perfil</strong>
                            <span>JPG, PNG ou WEBP com até 2 MB.</span>
                        </div>
                    </div>

                    <input
                        type="file"
                        id="foto"
                        name="foto"
                        accept="image/jpeg,image/png,image/webp"
                        data-image-preview="#preview-foto-usuario"
                    >
                </div>

                <div class="secao-form-titulo">
                    <div>
                        <strong>Acesso ao sistema</strong>
                    </div>
                    <i class="fa-solid fa-shield-halved"></i>
                </div>

                <div class="input-box">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        autocomplete="email"
                        placeholder="usuario@empresa.com"
                        required
                        value="<?php echo escapar($registro['email'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box senha-campo">
                    <label for="senha">
                        Senha
                        <?php if ($id > 0): ?>
                            <small>(deixe em branco para manter)</small>
                        <?php endif; ?>
                    </label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        minlength="6"
                        autocomplete="new-password"
                        placeholder="Mínimo de 6 caracteres"
                        <?php echo $id === 0 ? 'required' : ''; ?>
                    >
                    <button type="button" class="botao-senha" data-toggle-password="senha" aria-label="Mostrar ou ocultar senha">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div class="input-box senha-campo">
                    <label for="confirmar_senha">Confirmar senha</label>
                    <input
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        minlength="6"
                        autocomplete="new-password"
                        placeholder="Repita a senha"
                    >
                    <button type="button" class="botao-senha" data-toggle-password="confirmar_senha" aria-label="Mostrar ou ocultar confirmação de senha">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div class="input-box">
                    <label for="perfil">Perfil de acesso</label>

                    <?php if ($id > 0): ?>
                        <input type="text" value="<?php echo escapar(perfil_nome($registro['perfil'])); ?>" readonly>
                        <input type="hidden" id="perfil" name="perfil" value="<?php echo escapar($registro['perfil']); ?>">
                    <?php else: ?>
                        <select id="perfil" name="perfil" required>
                            <option value="">Selecione</option>
                            <option value="admin" <?php echo (($registro['perfil'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="gerente" <?php echo (($registro['perfil'] ?? '') === 'gerente') ? 'selected' : ''; ?>>Gerente de Frota</option>
                            <option value="motorista" <?php echo (($registro['perfil'] ?? '') === 'motorista') ? 'selected' : ''; ?>>Motorista</option>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="input-box">
                    <label for="filial_id">Filial</label>
                    <select id="filial_id" name="filial_id">
                        <option value="0">Sem filial</option>

                        <?php while ($filial = $filiais->fetch_assoc()): ?>
                            <option
                                value="<?php echo $filial['id']; ?>"
                                <?php echo ((int) ($registro['filial_id'] ?? 0) === (int) $filial['id']) ? 'selected' : ''; ?>
                            >
                                <?php echo escapar($filial['nome']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input-box">
                    <label for="ativo">Situação</label>
                    <select id="ativo" name="ativo">
                        <option value="1" <?php echo ((int) ($registro['ativo'] ?? 1) === 1) ? 'selected' : ''; ?>>Ativo</option>
                        <option value="0" <?php echo ((int) ($registro['ativo'] ?? 1) === 0) ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>

                <div id="dados-cnh" class="secao-form completa">
                    <div class="secao-form-titulo">
                        <div>
                            <strong>CNH do motorista</strong>
                        </div>
                        <i class="fa-solid fa-id-card"></i>
                    </div>

                    <div class="formulario formulario-interno">
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
                                value="<?php echo escapar($motorista['cnh_numero'] ?? ''); ?>"
                            >
                        </div>

                        <div class="input-box">
                            <label for="cnh_categoria">Categoria</label>
                            <select id="cnh_categoria" name="cnh_categoria">
                                <option value="">Selecione</option>
                                <?php foreach (['A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'] as $categoria): ?>
                                    <option value="<?php echo $categoria; ?>" <?php echo (($motorista['cnh_categoria'] ?? '') === $categoria) ? 'selected' : ''; ?>>
                                        <?php echo $categoria; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-box">
                            <label for="cnh_validade">Validade da CNH</label>
                            <input
                                type="date"
                                id="cnh_validade"
                                name="cnh_validade"
                                value="<?php echo escapar($motorista['cnh_validade'] ?? ''); ?>"
                            >
                        </div>
                    </div>
                </div>

                <div class="fin">
                    <a class="botao botao-secundario" href="usuarios.php">Cancelar</a>
                    <button class="botao botao-principal shadow-sm" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Salvar usuário
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const campoPerfil = document.getElementById('perfil');
const dadosCnh = document.getElementById('dados-cnh');
const cnhNumero = document.getElementById('cnh_numero');
const cnhCategoria = document.getElementById('cnh_categoria');
const cnhValidade = document.getElementById('cnh_validade');

function atualizarCamposCnh() {
    const motoristaSelecionado = campoPerfil && campoPerfil.value === 'motorista';

    if (!dadosCnh) {
        return;
    }

    dadosCnh.style.display = motoristaSelecionado ? 'block' : 'none';
    cnhNumero.required = motoristaSelecionado;
    cnhCategoria.required = motoristaSelecionado;
    cnhValidade.required = motoristaSelecionado;
}

if (campoPerfil) {
    campoPerfil.addEventListener('change', atualizarCamposCnh);
    atualizarCamposCnh();
}
</script>

<?php include '../rodape.php'; ?>
