<?php
include "conexao.php";
include "funcoes.php";
if (usuario_logado()) {
    registrar_log($sql, 'LOGOUT', 'usuarios', $_SESSION['usuario']['id'], 'Logout realizado');
}
session_unset();
session_destroy();
header("Location: index.php");
exit;
?>
