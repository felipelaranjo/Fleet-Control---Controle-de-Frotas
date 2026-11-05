<?php
$perfil_atual = $_SESSION['usuario']['perfil'] ?? '';
$nome_atual = $_SESSION['usuario']['nome'] ?? '';

$base = '';

if (
    strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/gerente/') !== false ||
    strpos($_SERVER['PHP_SELF'], '/motorista/') !== false
) {
    $base = '../';
}

$pagina_atual = basename($_SERVER['PHP_SELF']);

$secao_atual = '';

if (in_array($pagina_atual, ['usuarios.php', 'usuario-form.php'], true)) {
    $secao_atual = 'usuarios';
} elseif (in_array($pagina_atual, ['filiais.php', 'filial-form.php'], true)) {
    $secao_atual = 'filiais';
} elseif (in_array($pagina_atual, ['veiculos.php', 'veiculo-form.php'], true)) {
    $secao_atual = 'frota';
} elseif (in_array($pagina_atual, ['motoristas.php', 'motorista-form.php'], true)) {
    $secao_atual = 'motoristas';
} elseif (in_array($pagina_atual, ['viagens.php', 'viagem-form.php', 'viagem-detalhes.php', 'check-in.php', 'check-out.php', 'checklist.php'], true)) {
    $secao_atual = 'viagens';
} elseif (in_array($pagina_atual, ['manutencoes.php', 'manutencao-form.php'], true)) {
    $secao_atual = 'manutencoes';
} elseif ($pagina_atual === 'despesas.php' || $pagina_atual === 'despesa.php') {
    $secao_atual = 'despesas';
} elseif ($pagina_atual === 'relatorios.php') {
    $secao_atual = 'relatorios';
} elseif ($pagina_atual === 'logs.php') {
    $secao_atual = 'auditoria';
} elseif ($pagina_atual === 'configuracoes.php') {
    $secao_atual = 'configuracoes';
} elseif ($pagina_atual === 'historico.php') {
    $secao_atual = 'historico';
} elseif ($pagina_atual === 'perfil.php') {
    $secao_atual = 'perfil';
} elseif ($pagina_atual === 'dashboard.php') {
    $secao_atual = 'dashboard';
}
?>

<nav class="menu-left">
    <div class="containlogo">
        <a href="<?php echo $base; ?>index.php" class="marca-menu">
            <span class="marca-simbolo">
                <i class="fa-solid fa-truck-fast"></i>
                <span class="marca-linha"></span>
            </span>

            <span>
                <strong>Fleet<span>Control</span></strong>
                <small>Gestão inteligente de frotas</small>
            </span>
        </a>
    </div>

    <a href="<?php echo $base; ?>perfil.php" class="usuario-menu">
        <span class="avatar">
            <?php if (!empty($_SESSION['usuario']['foto'])): ?>
                <img src="<?php echo $base; ?>uploads/usuarios/<?php echo escapar($_SESSION['usuario']['foto']); ?>" alt="Foto do usuário">
            <?php else: ?>
                <i class="fa-solid fa-user"></i>
            <?php endif; ?>
        </span>

        <span>
            <strong><?php echo escapar($nome_atual); ?></strong>
            <small><?php echo escapar(perfil_nome($perfil_atual)); ?></small>
        </span>

        <i class="fa-solid fa-chevron-right usuario-seta"></i>
    </a>

    <ul>
        <?php if ($perfil_atual === 'admin'): ?>
            <li class="menu-titulo">Administração</li>

            <li>
                <a class="<?php echo $secao_atual === 'dashboard' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>admin/dashboard.php">
                    <i class="fa-solid fa-gauge-high"></i>
                    Dashboard
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'usuarios' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>admin/usuarios.php">
                    <i class="fa-solid fa-users"></i>
                    Usuários
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'filiais' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>admin/filiais.php">
                    <i class="fa-solid fa-building"></i>
                    Filiais
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'frota' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>gerente/veiculos.php">
                    <i class="fa-solid fa-car-side"></i>
                    Frota
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'auditoria' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>admin/logs.php">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Auditoria
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'configuracoes' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>admin/configuracoes.php">
                    <i class="fa-solid fa-gear"></i>
                    Configurações
                </a>
            </li>
        <?php elseif ($perfil_atual === 'gerente'): ?>
            <li class="menu-titulo">Gestão da filial</li>

            <li>
                <a class="<?php echo $secao_atual === 'dashboard' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>gerente/dashboard.php">
                    <i class="fa-solid fa-gauge-high"></i>
                    Dashboard
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'frota' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>gerente/veiculos.php">
                    <i class="fa-solid fa-car-side"></i>
                    Frota
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'motoristas' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>gerente/motoristas.php">
                    <i class="fa-solid fa-id-card"></i>
                    Motoristas
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'viagens' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>gerente/viagens.php">
                    <i class="fa-solid fa-route"></i>
                    Viagens
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'manutencoes' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>gerente/manutencoes.php">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    Manutenções
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'despesas' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>gerente/despesas.php">
                    <i class="fa-solid fa-receipt"></i>
                    Despesas
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'relatorios' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>gerente/relatorios.php">
                    <i class="fa-solid fa-chart-column"></i>
                    Relatórios
                </a>
            </li>
        <?php elseif ($perfil_atual === 'motorista'): ?>
            <li class="menu-titulo">Área do motorista</li>

            <li>
                <a class="<?php echo $secao_atual === 'dashboard' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>motorista/dashboard.php">
                    <i class="fa-solid fa-gauge-high"></i>
                    Meu painel
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'viagens' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>motorista/viagens.php">
                    <i class="fa-solid fa-route"></i>
                    Minhas viagens
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'despesas' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>motorista/despesa.php">
                    <i class="fa-solid fa-receipt"></i>
                    Lançar despesa
                </a>
            </li>

            <li>
                <a class="<?php echo $secao_atual === 'historico' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>motorista/historico.php">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Histórico
                </a>
            </li>
        <?php endif; ?>

        <li class="separador"></li>

        <li>
            <a class="<?php echo $secao_atual === 'perfil' ? 'ativo' : ''; ?>" href="<?php echo $base; ?>perfil.php">
                <i class="fa-solid fa-user-circle"></i>
                Meu perfil
            </a>
        </li>

        <li>
            <a href="<?php echo $base; ?>sair.php" class="sair">
                <i class="fa-solid fa-right-from-bracket"></i>
                Sair
            </a>
        </li>
    </ul>
</nav>

<button class="btnmenu" type="button" aria-label="Abrir menu">
    <i class="fa-solid fa-bars"></i>
</button>
