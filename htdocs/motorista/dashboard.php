<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('motorista');

$titulo = 'Meu Painel';
$prefixo = '../';
$id_motorista = (int) $_SESSION['usuario']['id'];

$query = $sql->prepare(
    "SELECT COUNT(*) AS total
     FROM viagens
     WHERE motorista_id = ?"
);
$query->bind_param('i', $id_motorista);
$query->execute();
$query->bind_result($total_viagens);
$query->fetch();
$query->close();

$query = $sql->prepare(
    "SELECT COUNT(*) AS total
     FROM viagens
     WHERE motorista_id = ?
       AND status = 'concluida'"
);
$query->bind_param('i', $id_motorista);
$query->execute();
$query->bind_result($total_concluidas);
$query->fetch();
$query->close();

$query = $sql->prepare(
    "SELECT v.*, ve.placa, ve.modelo, ve.marca,
            c.id AS checklist_id
     FROM viagens v
     INNER JOIN veiculos ve ON ve.id = v.veiculo_id
     LEFT JOIN checklists_viagem c ON c.viagem_id = v.id
     WHERE v.motorista_id = ?
       AND v.status IN ('agendada', 'em_andamento')
     ORDER BY
        CASE v.status WHEN 'em_andamento' THEN 1 ELSE 2 END,
        v.data_partida ASC
     LIMIT 1"
);
$query->bind_param('i', $id_motorista);
$query->execute();
$proxima = $query->get_result()->fetch_assoc();
$query->close();

$query = $sql->prepare(
    "SELECT v.id, v.descricao_rota, v.data_partida, v.status,
            ve.placa, ve.modelo
     FROM viagens v
     INNER JOIN veiculos ve ON ve.id = v.veiculo_id
     WHERE v.motorista_id = ?
     ORDER BY v.data_partida DESC
     LIMIT 5"
);
$query->bind_param('i', $id_motorista);
$query->execute();
$viagens_recentes = $query->get_result();

include '../cabecalho.php';
?>

<div class="painel shadow-sm motorista-boas-vindas">
    <div>
        <span class="etiqueta">Área operacional</span>
        <h3>Olá, <?php echo escapar($_SESSION['usuario']['nome']); ?>.</h3>
    </div>

    <a href="../perfil.php" class="botao botao-secundario shadow-sm">
        <i class="fa-solid fa-user"></i>
        Meu perfil
    </a>
</div>

<div class="cards cards-tres compactos">
    <div class="card-info shadow-sm">
        <span class="card-icone"><i class="fa-solid fa-route"></i></span>
        <div>
            <span>Minhas viagens</span>
            <strong><?php echo (int) $total_viagens; ?></strong>
            <small>Total de registros</small>
        </div>
    </div>

    <div class="card-info shadow-sm">
        <span class="card-icone verde"><i class="fa-solid fa-flag-checkered"></i></span>
        <div>
            <span>Concluídas</span>
            <strong><?php echo (int) $total_concluidas; ?></strong>
            <small>Viagens finalizadas</small>
        </div>
    </div>

    <div class="card-info shadow-sm">
        <span class="card-icone amarelo"><i class="fa-solid fa-car-side"></i></span>
        <div>
            <span>Próximo veículo</span>
            <strong class="numero-menor"><?php echo $proxima ? escapar($proxima['placa']) : '--'; ?></strong>
            <small><?php echo $proxima ? escapar($proxima['modelo']) : 'Sem viagem pendente'; ?></small>
        </div>
    </div>
</div>

<div class="painel shadow-sm">
    <div class="painel-titulo">
        <div>
            <span class="mini-etiqueta">Próxima operação</span>
            <h3>Viagem em destaque</h3>
        </div>
        <i class="fa-solid fa-route"></i>
    </div>

    <?php if ($proxima): ?>
        <div class="viagem-destaque motorista-destaque">
            <div>
                <span class="mini-etiqueta">Rota</span>
                <strong><?php echo escapar($proxima['descricao_rota']); ?></strong>
                <p>
                    <?php echo escapar($proxima['marca'] . ' ' . $proxima['modelo'] . ' · ' . $proxima['placa']); ?><br>
                    Saída: <?php echo data_hora_br($proxima['data_partida']); ?>
                </p>
            </div>

            <div class="acoes-viagem">
                <span class="status <?php echo classe_status($proxima['status']); ?>">
                    <?php echo escapar(status_viagem_nome($proxima['status'])); ?>
                </span>

                <?php if ($proxima['status'] === 'agendada' && $proxima['odometro_inicial'] === null): ?>
                    <a class="botao botao-principal shadow-sm" href="check-in.php?id=<?php echo $proxima['id']; ?>">
                        Fazer check-in
                    </a>
                <?php elseif ($proxima['status'] === 'agendada' && empty($proxima['checklist_id'])): ?>
                    <a class="botao botao-principal shadow-sm" href="checklist.php?id=<?php echo $proxima['id']; ?>">
                        Preencher checklist
                    </a>
                <?php else: ?>
                    <a class="botao botao-principal shadow-sm" href="viagem-detalhes.php?id=<?php echo $proxima['id']; ?>">
                        Abrir viagem
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="estado-vazio">
            <i class="fa-solid fa-circle-check"></i>
            <strong>Nenhuma viagem pendente</strong>
        </div>
    <?php endif; ?>
</div>

<div class="painel shadow-sm">
    <div class="painel-titulo">
        <div>
            <span class="mini-etiqueta">Histórico recente</span>
            <h3>Últimas viagens</h3>
        </div>

        <a href="viagens.php" class="botao botao-secundario">Ver todas</a>
    </div>

    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Rota</th>
                    <th>Veículo</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($viagem = $viagens_recentes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo escapar($viagem['descricao_rota']); ?></td>
                        <td><?php echo escapar($viagem['placa'] . ' - ' . $viagem['modelo']); ?></td>
                        <td><?php echo data_hora_br($viagem['data_partida']); ?></td>
                        <td>
                            <span class="status <?php echo classe_status($viagem['status']); ?>">
                                <?php echo escapar(status_viagem_nome($viagem['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <a class="link-destaque" href="viagem-detalhes.php?id=<?php echo $viagem['id']; ?>">Detalhes</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$query->close();
include '../rodape.php';
?>
