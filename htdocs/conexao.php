<?php


$servidor = "sql311.infinityfree.com";
$usuario = "if0_42681094";
$senha = "40028922Com";
$banco = "if0_42681094_controle_de_frotas";

mysqli_report(MYSQLI_REPORT_OFF);

$sql = new mysqli(
    $servidor,
    $usuario,
    $senha,
    $banco
);

if ($sql->connect_errno) {
    http_response_code(500);
    die("Não foi possível conectar ao banco de dados. Verifique o arquivo conexao.php.");
}

$sql->set_charset("utf8mb4");
?>
