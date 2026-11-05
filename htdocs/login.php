<?php
include "conexao.php";
include "funcoes.php";

if (usuario_logado()) {
    redirecionar_dashboard();
}

$erro = '';
$email_informado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_informado = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email_informado === '' || $senha === '') {
        $erro = 'Preencha o e-mail e a senha.';
    } elseif ($sql->connect_errno) {
        $erro = 'Não foi possível conectar ao sistema. Tente novamente mais tarde.';
    } else {
        $query = $sql->prepare(
            "SELECT id, nome, cpf, rg, email, telefone, foto, senha_hash, perfil, filial_id, ativo
             FROM usuarios
             WHERE email = ?
             LIMIT 1"
        );

        if (!$query) {
            $erro = 'Não foi possível realizar o login. Tente novamente.';
        } else {
            $query->bind_param('s', $email_informado);
            $query->execute();
            $query->store_result();

            if ($query->num_rows !== 1) {
                $erro = 'E-mail ou senha incorretos.';
            } else {
                $query->bind_result(
                    $id,
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
                $query->fetch();

                if ((int) $ativo !== 1 || !password_verify($senha, $senha_hash)) {
                    $erro = 'E-mail ou senha incorretos.';
                } else {
                    $_SESSION['usuario'] = [
                        'id' => $id,
                        'nome' => $nome,
                        'cpf' => $cpf,
                        'rg' => $rg,
                        'email' => $email,
                        'telefone' => $telefone,
                        'foto' => $foto,
                        'senha_hash' => $senha_hash,
                        'perfil' => $perfil,
                        'filial_id' => $filial_id,
                        'ativo' => $ativo
                    ];

                    registrar_log(
                        $sql,
                        'LOGIN',
                        'usuarios',
                        $id,
                        'Login realizado com sucesso'
                    );

                    $query->close();
                    redirecionar_dashboard();
                }
            }

            $query->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    >
    <link rel="stylesheet" href="css/style.css?v=final-20260925b">

    <title>Login - FleetControl</title>
</head>
<body class="login-body">

<div class="login-page">
    <div class="login-caixa shadow">
        <div class="login-apresentacao">
            <div class="marca-login">
                <span class="marca-simbolo grande">
                    <i class="fa-solid fa-truck"></i>
                </span>
                <h1 class="logo">Fleet<span>Control</span></h1>
            </div>

            <span class="etiqueta etiqueta-clara">FleetControl</span>
            <h2>Gestão de Frotas</h2>
        </div>

        <div class="login-form">
            <a href="index.php" class="voltar-link">
                <i class="fa-solid fa-arrow-left"></i>
                Voltar
            </a>
            <h2>Acesse sua conta</h2>
            <p class="sub">Informe seu e-mail e sua senha.</p>

            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger shadow-sm d-flex align-items-center gap-2" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo escapar($erro); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-box">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo escapar($email_informado); ?>"
                        placeholder="seuemail@empresa.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="input-box senha-campo">
                    <label for="senha">Senha</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="botao-senha" data-toggle-password="senha" aria-label="Mostrar ou ocultar senha">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div class="login-recuperar">
                    <a href="recuperar-senha.php">Esqueci minha senha</a>
                </div>

                <button class="botao botao-principal botao-login shadow-sm" type="submit">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Entrar no sistema
                </button>
            </form>
        </div>
    </div>
</div>

<script src="js/script.js?v=final-20260925b"></script>
</body>
</html>
