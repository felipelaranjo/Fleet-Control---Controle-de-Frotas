<?php
$titulo = $titulo ?? 'FleetControl';
$prefixo = $prefixo ?? '';
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

    <link rel="stylesheet" href="<?php echo $prefixo; ?>css/style.css?v=final-20260925">

    <title><?php echo escapar($titulo); ?> - FleetControl</title>
</head>
<body>

<?php include __DIR__ . '/menu.php'; ?>

<div class="background"></div>

<div class="pagina">
    <main class="conteudo-principal">
        <div class="cabecalho-pagina">
            <div>
                <span class="etiqueta">FleetControl</span>
                <h1><?php echo escapar($titulo); ?></h1>
            </div>

            <a href="<?php echo $prefixo; ?>perfil.php" class="perfil-topo shadow-sm">
                <span class="perfil-topo-avatar">
                    <?php if (!empty($_SESSION['usuario']['foto'])): ?>
                        <img src="<?php echo $prefixo; ?>uploads/usuarios/<?php echo escapar($_SESSION['usuario']['foto']); ?>" alt="Foto do usuário">
                    <?php else: ?>
                        <i class="fa-solid fa-user"></i>
                    <?php endif; ?>
                </span>
                <span>
                    <strong><?php echo escapar($_SESSION['usuario']['nome'] ?? 'Usuário'); ?></strong>
                    <small>Meu perfil</small>
                </span>
            </a>
        </div>

        <?php mostrar_mensagens(); ?>
