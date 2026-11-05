<?php
include "funcoes.php";

if (usuario_logado()) {
    redirecionar_dashboard();
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

    <title>Recuperar senha - FleetControl</title>
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
            <h2>Recuperação de senha</h2>
        </div>

        <div class="login-form">
            <a href="login.php" class="voltar-link">
                <i class="fa-solid fa-arrow-left"></i>
                Voltar para o login
            </a>
            <h2>Esqueci minha senha</h2>

            <form>
                <div class="input-box">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="seuemail@empresa.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <button class="botao botao-principal botao-login shadow-sm" type="button">
                    <i class="fa-solid fa-envelope"></i>
                    Enviar recuperação
                </button>
            </form>
        </div>
    </div>
</div>

<script src="js/script.js?v=final-20260925b"></script>
</body>
</html>
