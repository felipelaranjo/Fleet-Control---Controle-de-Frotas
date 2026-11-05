<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$filial_usuario = filial_do_usuario();
$visao_global = $perfil_atual === 'admin';

$titulo = $visao_global ? 'Visão da Frota' : 'Dashboard da Frota';
$prefixo = '../';

$filtro_filial = $visao_global ? '' : ' AND filial_id = ' . (int) $filial_usuario;

$total_veiculos = (int) $sql->query(
    "SELECT COUNT(*) AS total
     FROM veiculos
     WHERE ativo = 1" . $filtro_filial
)->fetch_assoc()['total'];

$disponiveis = (int) $sql->query(
    "SELECT COUNT(*) AS total
     FROM veiculos
     WHERE ativo = 1
       AND status = 'disponivel'" . $filtro_filial
)->fetch_assoc()['total'];

$reservados = (int) $sql->query(
    "SELECT COUNT(*) AS total
     FROM veiculos
     WHERE ativo = 1
       AND status = 'reservado'" . $filtro_filial
)->fetch_assoc()['total'];

$em_transito = (int) $sql->query(
    "SELECT COUNT(*) AS total
     FROM veiculos
     WHERE ativo = 1
       AND status = 'em_transito'" . $filtro_filial
)->fetch_assoc()['total'];

$em_manutencao = (int) $sql->query(
    "SELECT COUNT(*) AS total
     FROM veiculos
     WHERE ativo = 1
       AND status = 'manutencao'" . $filtro_filial
)->fetch_assoc()['total'];

$filtro_viagem = $visao_global ? '' : ' AND v.filial_id = ' . (int) $filial_usuario;

$viagens_hoje = (int) $sql->query(
    "SELECT COUNT(*) AS total
     FROM viagens v
     WHERE DATE(v.data_partida) = CURDATE()" . $filtro_viagem
)->fetch_assoc()['total'];

$despesas_mes = (float) $sql->query(
    "SELECT COALESCE(SUM(d.valor), 0) AS total
     FROM despesas_viagem d
     INNER JOIN viagens v ON v.id = d.viagem_id
     WHERE YEAR(d.lancado_em) = YEAR(CURDATE())
       AND MONTH(d.lancado_em) = MONTH(CURDATE())" . $filtro_viagem
)->fetch_assoc()['total'];

$filtro_manutencao = $visao_global ? '' : ' AND ve.filial_id = ' . (int) $filial_usuario;

$alertas = $sql->query(
    "SELECT p.id, p.tipo_servico, p.km_limite,
            ve.id AS veiculo_id, ve.placa, ve.modelo, ve.odometro_atual,
            (p.km_limite - ve.odometro_atual) AS km_restantes
     FROM planos_manutencao p
     INNER JOIN veiculos ve ON ve.id = p.veiculo_id
     WHERE p.status = 'pendente'
       AND ve.ativo = 1
       AND (p.km_limite - ve.odometro_atual) <= 500" . $filtro_manutencao . "
     ORDER BY km_restantes ASC
     LIMIT 6"
);

$proximas_viagens = $sql->query(
    "SELECT v.id, v.descricao_rota, v.data_partida, v.status,
            ve.placa, ve.modelo, u.nome AS motorista
     FROM viagens v
     INNER JOIN veiculos ve ON ve.id = v.veiculo_id
     INNER JOIN usuarios u ON u.id = v.motorista_id
     WHERE v.status IN ('agendada', 'em_andamento')" . $filtro_viagem . "
     ORDER BY v.data_partida ASC
     LIMIT 6"
);

include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta"><?php echo $visao_global ? 'Nível 3 - Visão global' : 'Nível 2 - Gestão da filial'; ?></span>
        <h3><?php echo $visao_global ? 'Panorama operacional da frota' : 'Controle da sua frota'; ?></h3>
        <p>
            <?php if ($visao_global): ?>
                Acompanhe os veículos de todas as filiais sem alterar a separação entre os perfis do sistema.
            <?php else: ?>
                Os indicadores abaixo consideram somente os veículos, motoristas e viagens da sua filial.
            <?php endif; ?>
        </p>
    </div>

    <div class="atalhos-topo">
        <a href="veiculo-form.php" class="botao botao-principal shadow-sm">
            <i class="fa-solid fa-plus"></i>
            Novo veículo
        </a>

        <a href="viagem-form.php" class="botao botao-secundario shadow-sm">
            <i class="fa-solid fa-route"></i>
            Nova viagem
        </a>
    </div>
</div>

<div class="cards cards-cinco">
    <a href="veiculos.php" class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-car-side"></i></span>
        <div>
            <span>Frota ativa</span>
            <strong><?php echo $total_veiculos; ?></strong>
            <small>Veículos cadastrados</small>
        </div>
    </a>

    <a href="veiculos.php?status=disponivel" class="card-info shadow-sm">
        <span class="card-icone verde"><i class="fa-solid fa-circle-check"></i></span>
        <div>
            <span>Disponíveis</span>
            <strong><?php echo $disponiveis; ?></strong>
            <small>Prontos para uso</small>
        </div>
    </a>

    <a href="veiculos.php?status=reservado" class="card-info shadow-sm">
        <span class="card-icone amarelo"><i class="fa-solid fa-calendar-check"></i></span>
        <div>
            <span>Reservados</span>
            <strong><?php echo $reservados; ?></strong>
            <small>Com viagem agendada</small>
        </div>
    </a>

    <a href="veiculos.php?status=em_transito" class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-road"></i></span>
        <div>
            <span>Em trânsito</span>
            <strong><?php echo $em_transito; ?></strong>
            <small>Em operação</small>
        </div>
    </a>

    <a href="veiculos.php?status=manutencao" class="card-info shadow-sm">
        <span class="card-icone vermelho"><i class="fa-solid fa-screwdriver-wrench"></i></span>
        <div>
            <span>Manutenção</span>
            <strong><?php echo $em_manutencao; ?></strong>
            <small>Indisponíveis</small>
        </div>
    </a>
</div>

<div class="metricas-faixa painel shadow-sm">
    <div>
        <span>Viagens de hoje</span>
        <strong><?php echo $viagens_hoje; ?></strong>
    </div>

    <div>
        <span>Despesas no mês</span>
        <strong><?php echo moeda_br($despesas_mes); ?></strong>
    </div>

    <div>
        <span>Disponibilidade da frota</span>
        <strong>
            <?php echo $total_veiculos > 0 ? number_format(($disponiveis / $total_veiculos) * 100, 0) : 0; ?>%
        </strong>
    </div>
</div>

<div class="dashboard-grid">
    <div class="painel shadow-sm">
        <div class="painel-titulo">
            <div>
                <span class="mini-etiqueta">Manutenção preventiva</span>
                <h3>Alertas da frota</h3>
            </div>

            <a href="manutencoes.php" class="link-destaque">Ver manutenções</a>
        </div>

        <div class="alerta-lista">
            <?php if ($alertas->num_rows === 0): ?>
                <div class="estado-vazio compacto">
                    <i class="fa-solid fa-circle-check"></i>
                    <p>Nenhum veículo está dentro da faixa crítica de manutenção.</p>
                </div>
            <?php endif; ?>

            <?php while ($alerta = $alertas->fetch_assoc()): ?>
                <div class="alerta-item <?php echo (int) $alerta['km_restantes'] <= 0 ? 'critico' : ''; ?>">
                    <span class="alerta-icone">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </span>

                    <div>
                        <strong><?php echo escapar($alerta['placa'] . ' - ' . $alerta['modelo']); ?></strong>
                        <p><?php echo escapar($alerta['tipo_servico']); ?></p>
                        <small>
                            <?php if ((int) $alerta['km_restantes'] <= 0): ?>
                                Limite atingido ou ultrapassado
                            <?php else: ?>
                                Faltam <?php echo number_format($alerta['km_restantes'], 0, ',', '.'); ?> km
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="painel shadow-sm">
        <div class="painel-titulo">
            <div>
                <span class="mini-etiqueta">Operação</span>
                <h3>Próximas viagens</h3>
            </div>

            <a href="viagens.php" class="link-destaque">Ver todas</a>
        </div>

        <div class="viagem-lista">
            <?php if ($proximas_viagens->num_rows === 0): ?>
                <div class="estado-vazio compacto">
                    <i class="fa-solid fa-route"></i>
                    <p>Nenhuma viagem ativa no momento.</p>
                </div>
            <?php endif; ?>

            <?php while ($viagem = $proximas_viagens->fetch_assoc()): ?>
                <div class="viagem-item">
                    <div>
                        <strong><?php echo escapar($viagem['descricao_rota']); ?></strong>
                        <p><?php echo escapar($viagem['placa'] . ' · ' . $viagem['motorista']); ?></p>
                        <small><?php echo data_hora_br($viagem['data_partida']); ?></small>
                    </div>

                    <span class="status <?php echo classe_status($viagem['status']); ?>">
                        <?php echo escapar(status_viagem_nome($viagem['status'])); ?>
                    </span>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php include '../rodape.php'; ?>
