<?php
include "conexao.php";
include "funcoes.php";

exigir_login();

$id_usuario = (int) $_SESSION['usuario']['id'];

$query = $sql->prepare(
    "SELECT u.*, f.nome AS nome_filial,
            m.cnh_numero, m.cnh_categoria, m.cnh_validade
     FROM usuarios u
     LEFT JOIN filiais f ON f.id = u.filial_id
     LEFT JOIN motoristas m ON m.usuario_id = u.id
     WHERE u.id = ?"
);
$query->bind_param('i', $id_usuario);
$query->execute();
$usuario = $query->get_result()->fetch_assoc();
$query->close();

if (!$usuario) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$perfil = $usuario['perfil'];
$perfil_legivel = perfil_nome($perfil);

$indicadores = [];

if ($perfil === 'admin') {
    $indicadores[] = [
        'titulo' => 'Usuários',
        'valor' => (int) $sql->query("SELECT COUNT(*) AS total FROM usuarios")->fetch_assoc()['total'],
        'icone' => 'fa-users'
    ];

    $indicadores[] = [
        'titulo' => 'Filiais',
        'valor' => (int) $sql->query("SELECT COUNT(*) AS total FROM filiais WHERE ativo = 1")->fetch_assoc()['total'],
        'icone' => 'fa-building'
    ];

    $indicadores[] = [
        'titulo' => 'Veículos',
        'valor' => (int) $sql->query("SELECT COUNT(*) AS total FROM veiculos WHERE ativo = 1")->fetch_assoc()['total'],
        'icone' => 'fa-car-side'
    ];
} elseif ($perfil === 'gerente') {
    $filial_id = (int) $usuario['filial_id'];

    $indicadores[] = [
        'titulo' => 'Frota da filial',
        'valor' => (int) $sql->query("SELECT COUNT(*) AS total FROM veiculos WHERE ativo = 1 AND filial_id = " . $filial_id)->fetch_assoc()['total'],
        'icone' => 'fa-car-side'
    ];

    $indicadores[] = [
        'titulo' => 'Motoristas',
        'valor' => (int) $sql->query("SELECT COUNT(*) AS total FROM usuarios WHERE perfil = 'motorista' AND ativo = 1 AND filial_id = " . $filial_id)->fetch_assoc()['total'],
        'icone' => 'fa-id-card'
    ];

    $indicadores[] = [
        'titulo' => 'Viagens ativas',
        'valor' => (int) $sql->query("SELECT COUNT(*) AS total FROM viagens WHERE status IN ('agendada','em_andamento') AND filial_id = " . $filial_id)->fetch_assoc()['total'],
        'icone' => 'fa-route'
    ];
} else {
    $query = $sql->prepare(
        "SELECT COUNT(*) AS total
         FROM viagens
         WHERE motorista_id = ?"
    );
    $query->bind_param('i', $id_usuario);
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
    $query->bind_param('i', $id_usuario);
    $query->execute();
    $query->bind_result($total_concluidas);
    $query->fetch();
    $query->close();

    $indicadores[] = [
        'titulo' => 'Minhas viagens',
        'valor' => (int) $total_viagens,
        'icone' => 'fa-route'
    ];

    $indicadores[] = [
        'titulo' => 'Concluídas',
        'valor' => (int) $total_concluidas,
        'icone' => 'fa-flag-checkered'
    ];

    $indicadores[] = [
        'titulo' => 'CNH',
        'valor' => !empty($usuario['cnh_categoria']) ? $usuario['cnh_categoria'] : '--',
        'icone' => 'fa-id-card'
    ];
}

$titulo = 'Meu Perfil';
$prefixo = '';
include 'cabecalho.php';
?>

<div class="perfil-cabecalho painel shadow-sm">
    <div class="perfil-identidade">
        <div class="perfil-avatar-grande">
            <?php if (!empty($usuario['foto'])): ?>
                <img src="uploads/usuarios/<?php echo escapar($usuario['foto']); ?>" alt="Foto de <?php echo escapar($usuario['nome']); ?>">
            <?php else: ?>
                <i class="fa-solid fa-user"></i>
            <?php endif; ?>
        </div>

        <div>
            <span class="etiqueta">Minha conta</span>
            <h2><?php echo escapar($usuario['nome']); ?></h2>
            <p><?php echo escapar($perfil_legivel); ?> · <?php echo escapar($usuario['nome_filial'] ?? 'Sem filial vinculada'); ?></p>
        </div>
    </div>

    <span class="status <?php echo $usuario['ativo'] ? 'disponivel' : 'cancelada'; ?>">
        <?php echo $usuario['ativo'] ? 'Conta ativa' : 'Conta inativa'; ?>
    </span>
</div>

<div class="cards cards-tres compactos perfil-indicadores">
    <?php foreach ($indicadores as $indicador): ?>
        <div class="card-info shadow-sm">
            <span class="card-icone"><i class="fa-solid <?php echo $indicador['icone']; ?>"></i></span>
            <div>
                <span><?php echo escapar($indicador['titulo']); ?></span>
                <strong><?php echo escapar($indicador['valor']); ?></strong>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="perfil-grid">
    <div class="painel shadow-sm">
        <div class="painel-titulo">
            <div>
                <span class="mini-etiqueta">Informações pessoais</span>
                <h3>Dados da conta</h3>
            </div>
            <i class="fa-solid fa-user-circle"></i>
        </div>

        <div class="dados-lista">
            <div class="dado"><span>Nome</span><strong><?php echo escapar($usuario['nome']); ?></strong></div>
            <div class="dado"><span>CPF</span><strong><?php echo escapar($usuario['cpf']); ?></strong></div>
            <div class="dado"><span>RG</span><strong><?php echo escapar($usuario['rg'] ?: 'Não informado'); ?></strong></div>
            <div class="dado"><span>E-mail</span><strong><?php echo escapar($usuario['email']); ?></strong></div>
            <div class="dado"><span>Telefone</span><strong><?php echo escapar($usuario['telefone'] ?: 'Não informado'); ?></strong></div>
            <div class="dado"><span>Filial</span><strong><?php echo escapar($usuario['nome_filial'] ?? 'Não vinculada'); ?></strong></div>
        </div>
    </div>

    <div class="painel shadow-sm">
        <div class="painel-titulo">
            <div>
                <span class="mini-etiqueta">Hierarquia</span>
                <h3>Seu acesso no sistema</h3>
            </div>
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <div class="perfil-nivel">
            <span class="nivel-icone">
                <?php if ($perfil === 'admin'): ?>
                    <i class="fa-solid fa-user-shield"></i>
                <?php elseif ($perfil === 'gerente'): ?>
                    <i class="fa-solid fa-chart-pie"></i>
                <?php else: ?>
                    <i class="fa-solid fa-id-card"></i>
                <?php endif; ?>
            </span>

            <div>
                <strong><?php echo escapar($perfil_legivel); ?></strong>
            </div>
        </div>

        <?php if ($perfil === 'motorista'): ?>
            <div class="subpainel">
                <div class="painel-titulo sem-margem">
                    <div>
                        <span class="mini-etiqueta">Documento obrigatório</span>
                        <h3>Carteira Nacional de Habilitação</h3>
                    </div>

                    <?php if (!empty($usuario['cnh_validade'])): ?>
                        <span class="status <?php echo cnh_valida($usuario['cnh_validade']) ? 'disponivel' : 'cancelada'; ?>">
                            <?php echo cnh_valida($usuario['cnh_validade']) ? 'Válida' : 'Vencida'; ?>
                        </span>
                    <?php else: ?>
                        <span class="status cancelada">Não cadastrada</span>
                    <?php endif; ?>
                </div>

                <div class="cnh-resumo">
                    <div><span>Número</span><strong><?php echo escapar($usuario['cnh_numero'] ?: 'Não informado'); ?></strong></div>
                    <div><span>Categoria</span><strong><?php echo escapar($usuario['cnh_categoria'] ?: 'Não informado'); ?></strong></div>
                    <div><span>Validade</span><strong><?php echo !empty($usuario['cnh_validade']) ? data_br($usuario['cnh_validade']) : 'Não informado'; ?></strong></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'rodape.php'; ?>
