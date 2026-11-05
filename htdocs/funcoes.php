<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function escapar($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function usuario_logado()
{
    return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id']);
}

function perfil_nome($perfil)
{
    if ($perfil === 'admin') {
        return 'Administrador';
    }

    if ($perfil === 'gerente') {
        return 'Gerente de Frota';
    }

    return 'Motorista';
}

function data_br($data)
{
    if (empty($data)) {
        return '-';
    }

    return date('d/m/Y', strtotime($data));
}

function data_hora_br($data)
{
    if (empty($data)) {
        return '-';
    }

    return date('d/m/Y H:i', strtotime($data));
}

function moeda_br($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function status_veiculo_nome($status)
{
    $nomes = [
        'disponivel' => 'Disponível',
        'reservado' => 'Reservado',
        'em_transito' => 'Em trânsito',
        'manutencao' => 'Manutenção'
    ];

    return $nomes[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function status_viagem_nome($status)
{
    $nomes = [
        'agendada' => 'Agendada',
        'em_andamento' => 'Em andamento',
        'concluida' => 'Concluída',
        'cancelada' => 'Cancelada'
    ];

    return $nomes[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function classe_status($status)
{
    $classes = [
        'disponivel' => 'disponivel',
        'reservado' => 'reservada',
        'em_transito' => 'transito',
        'manutencao' => 'manutencao',
        'agendada' => 'reservada',
        'em_andamento' => 'andamento',
        'concluida' => 'concluida',
        'cancelada' => 'cancelada',
        'pendente' => 'reservada',
        'concluido' => 'concluida',
        'cancelado' => 'cancelada'
    ];

    return $classes[$status] ?? 'andamento';
}

function cnh_atende_categoria($categoria_motorista, $categoria_veiculo)
{
    $permitidas = [
        'A' => ['A'],
        'B' => ['B'],
        'C' => ['B', 'C'],
        'D' => ['B', 'C', 'D'],
        'E' => ['B', 'C', 'D', 'E'],
        'AB' => ['A', 'B'],
        'AC' => ['A', 'B', 'C'],
        'AD' => ['A', 'B', 'C', 'D'],
        'AE' => ['A', 'B', 'C', 'D', 'E']
    ];

    $habilitacoes = $permitidas[$categoria_motorista] ?? [];

    return in_array($categoria_veiculo, $habilitacoes, true);
}

function cnh_valida($validade)
{
    if (empty($validade)) {
        return false;
    }

    return strtotime($validade) >= strtotime(date('Y-m-d'));
}

function redirecionar_dashboard()
{
    if (!usuario_logado()) {
        header('Location: login.php');
        exit;
    }

    $perfil = $_SESSION['usuario']['perfil'] ?? '';

    if ($perfil === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($perfil === 'gerente') {
        header('Location: gerente/dashboard.php');
    } elseif ($perfil === 'motorista') {
        header('Location: motorista/dashboard.php');
    } else {
        session_unset();
        session_destroy();
        header('Location: login.php');
    }

    exit;
}

function exigir_login()
{
    if (usuario_logado()) {
        return;
    }

    $em_area_interna = (
        strpos($_SERVER['PHP_SELF'], '/admin/') !== false ||
        strpos($_SERVER['PHP_SELF'], '/gerente/') !== false ||
        strpos($_SERVER['PHP_SELF'], '/motorista/') !== false
    );

    header('Location: ' . ($em_area_interna ? '../login.php' : 'login.php'));
    exit;
}

function exigir_perfil($perfil)
{
    exigir_login();

    $perfil_atual = $_SESSION['usuario']['perfil'] ?? '';

    if ($perfil_atual === 'admin' || $perfil_atual === $perfil) {
        return;
    }

    redirecionar_dashboard();
}

function filial_do_usuario()
{
    return (int) ($_SESSION['usuario']['filial_id'] ?? 0);
}

function mensagem_sucesso($texto)
{
    $_SESSION['sucesso'] = $texto;
}

function mensagem_erro($texto)
{
    $_SESSION['erro'] = $texto;
}

function mostrar_mensagens()
{
    if (!empty($_SESSION['sucesso'])) {
        echo '<div class="alert alert-success shadow-sm">' . escapar($_SESSION['sucesso']) . '</div>';
        unset($_SESSION['sucesso']);
    }

    if (!empty($_SESSION['erro'])) {
        echo '<div class="alert alert-danger shadow-sm">' . escapar($_SESSION['erro']) . '</div>';
        unset($_SESSION['erro']);
    }
}

function registrar_log($sql, $acao, $entidade, $entidade_id = null, $detalhes = '')
{
    $usuario_id = $_SESSION['usuario']['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $query = $sql->prepare(
        "INSERT INTO logs_auditoria
        (usuario_id, acao, entidade, entidade_id, detalhes, ip_origem)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    if ($query) {
        $query->bind_param(
            'ississ',
            $usuario_id,
            $acao,
            $entidade,
            $entidade_id,
            $detalhes,
            $ip
        );

        $query->execute();
        $query->close();
    }
}

function voltar($arquivo)
{
    header('Location: ' . $arquivo);
    exit;
}

function salvar_imagem_upload($arquivo, $pasta_destino, $prefixo_nome)
{
    if (empty($arquivo['name']) || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['arquivo' => null, 'erro' => ''];
    }

    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['arquivo' => null, 'erro' => 'Não foi possível receber a imagem enviada.'];
    }

    if ((int) ($arquivo['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['arquivo' => null, 'erro' => 'A imagem deve ter no máximo 2 MB.'];
    }

    $dados_imagem = @getimagesize($arquivo['tmp_name']);

    if ($dados_imagem === false) {
        return ['arquivo' => null, 'erro' => 'O arquivo enviado não é uma imagem válida.'];
    }

    $extensoes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp'
    ];

    $tipo_imagem = $dados_imagem[2] ?? 0;

    if (!isset($extensoes[$tipo_imagem])) {
        return ['arquivo' => null, 'erro' => 'Use uma imagem JPG, PNG ou WEBP.'];
    }

    if (!is_dir($pasta_destino)) {
        return ['arquivo' => null, 'erro' => 'A pasta de imagens não foi encontrada no servidor.'];
    }

    $nome_arquivo = $prefixo_nome . '_' . uniqid() . '.' . $extensoes[$tipo_imagem];
    $destino = rtrim($pasta_destino, '/\\') . DIRECTORY_SEPARATOR . $nome_arquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        return ['arquivo' => null, 'erro' => 'Não foi possível salvar a imagem no servidor.'];
    }

    return ['arquivo' => $nome_arquivo, 'erro' => ''];
}

?>
