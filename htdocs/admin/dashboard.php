<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('admin');

$titulo = 'Dashboard Administrativo';
$prefixo = '../';

$total_usuarios = (int) $sql->query("SELECT COUNT(*) AS total FROM usuarios")->fetch_assoc()['total'];
$total_filiais = (int) $sql->query("SELECT COUNT(*) AS total FROM filiais WHERE ativo = 1")->fetch_assoc()['total'];
$total_veiculos = (int) $sql->query("SELECT COUNT(*) AS total FROM veiculos WHERE ativo = 1")->fetch_assoc()['total'];
$total_motoristas = (int) $sql->query("SELECT COUNT(*) AS total FROM usuarios WHERE perfil = 'motorista' AND ativo = 1")->fetch_assoc()['total'];
$total_viagens_ativas = (int) $sql->query("SELECT COUNT(*) AS total FROM viagens WHERE status IN ('agendada', 'em_andamento')")->fetch_assoc()['total'];
$total_cnh_vencidas = (int) $sql->query(
    "SELECT COUNT(*) AS total
     FROM motoristas m
     INNER JOIN usuarios u ON u.id = m.usuario_id
     WHERE u.ativo = 1
       AND m.cnh_validade < CURDATE()"
)->fetch_assoc()['total'];

$frota_status = [
    'disponivel' => 0,
    'reservado' => 0,
    'em_transito' => 0,
    'manutencao' => 0
];

$resultado_status = $sql->query(
    "SELECT status, COUNT(*) AS total
     FROM veiculos
     WHERE ativo = 1
     GROUP BY status"
);

while ($linha = $resultado_status->fetch_assoc()) {
    $frota_status[$linha['status']] = (int) $linha['total'];
}

$logs = $sql->query(
    "SELECT l.*, u.nome AS usuario
     FROM logs_auditoria l
     LEFT JOIN usuarios u ON u.id = l.usuario_id
     ORDER BY l.registrado_em DESC
     LIMIT 6"
);

include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Nível 3 - Controle global</span>
        <h3>Visão administrativa da empresa</h3>
    </div>

    <div class="atalhos-topo">
        <a href="usuario-form.php" class="botao botao-principal shadow-sm">
            <i class="fa-solid fa-user-plus"></i>
            Novo usuário
        </a>

        <a href="../gerente/veiculos.php" class="botao botao-secundario shadow-sm">
            <i class="fa-solid fa-car-side"></i>
            Ver frota
        </a>
    </div>
</div>

<div class="cards">
    <a href="usuarios.php" class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-users"></i></span>
        <div>
            <span>Usuários</span>
            <strong><?php echo $total_usuarios; ?></strong>
            <small>Cadastros no sistema</small>
        </div>
    </a>

    <a href="filiais.php" class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-building"></i></span>
        <div>
            <span>Filiais ativas</span>
            <strong><?php echo $total_filiais; ?></strong>
            <small>Unidades operacionais</small>
        </div>
    </a>

    <a href="../gerente/veiculos.php" class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-car-side"></i></span>
        <div>
            <span>Veículos</span>
            <strong><?php echo $total_veiculos; ?></strong>
            <small>Frota ativa</small>
        </div>
    </a>

    <a href="usuarios.php?perfil=motorista" class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-id-card"></i></span>
        <div>
            <span>Motoristas</span>
            <strong><?php echo $total_motoristas; ?></strong>
            <small><?php echo $total_cnh_vencidas; ?> CNH(s) vencida(s)</small>
        </div>
    </a>
</div>

<div class="dashboard-grid">
    <div class="painel shadow-sm">
        <div class="painel-titulo">
            <div>
                <span class="mini-etiqueta">Frota corporativa</span>
                <h3>Situação dos veículos</h3>
            </div>

            <a href="../gerente/veiculos.php" class="link-destaque">Abrir frota</a>
        </div>

        <div class="status-grid">
            <div class="status-resumo disponivel-box">
                <span>Disponíveis</span>
                <strong><?php echo $frota_status['disponivel']; ?></strong>
            </div>

            <div class="status-resumo reservado-box">
                <span>Reservados</span>
                <strong><?php echo $frota_status['reservado']; ?></strong>
            </div>

            <div class="status-resumo transito-box">
                <span>Em trânsito</span>
                <strong><?php echo $frota_status['em_transito']; ?></strong>
            </div>

            <div class="status-resumo manutencao-box">
                <span>Manutenção</span>
                <strong><?php echo $frota_status['manutencao']; ?></strong>
            </div>
        </div>

        <div class="resumo-linha">
            <span><i class="fa-solid fa-route"></i> Viagens ativas</span>
            <strong><?php echo $total_viagens_ativas; ?></strong>
        </div>
    </div>

    <div class="painel shadow-sm">
        <div class="painel-titulo">
            <div>
                <span class="mini-etiqueta">Auditoria</span>
                <h3>Atividades recentes</h3>
            </div>

            <a href="logs.php" class="link-destaque">Ver todos</a>
        </div>

        <div class="atividade-lista">
            <?php while ($log = $logs->fetch_assoc()): ?>
                <div class="atividade-item">
                    <span class="atividade-icone">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </span>

                    <div>
                        <strong><?php echo escapar($log['acao']); ?></strong>
                        <p><?php echo escapar($log['detalhes'] ?: $log['entidade']); ?></p>
                        <small>
                            <?php echo escapar($log['usuario'] ?? 'Sistema'); ?> ·
                            <?php echo data_hora_br($log['registrado_em']); ?>
                        </small>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php include '../rodape.php'; ?>
