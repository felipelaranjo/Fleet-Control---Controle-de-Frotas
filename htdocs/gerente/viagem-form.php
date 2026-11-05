<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$visao_global = $perfil_atual === 'admin';
$filial_usuario = filial_do_usuario();
$gerente_id = (int) $_SESSION['usuario']['id'];
$erro_formulario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $veiculo_id = (int) ($_POST['veiculo_id'] ?? 0);
    $motorista_id = (int) ($_POST['motorista_id'] ?? 0);
    $descricao_rota = trim($_POST['descricao_rota'] ?? '');
    $data_partida = $_POST['data_partida'] ?? '';
    $previsao_retorno = $_POST['previsao_retorno'] ?? '';

    if ($visao_global) {
        $filial_id = (int) ($_POST['filial_id'] ?? 0);
    } else {
        $filial_id = $filial_usuario;
    }

    if ($veiculo_id <= 0 || $motorista_id <= 0 || $descricao_rota === '' || $data_partida === '' || $previsao_retorno === '') {
        $erro_formulario = 'Preencha todos os campos obrigatórios da viagem.';
    } elseif (strtotime($previsao_retorno) <= strtotime($data_partida)) {
        $erro_formulario = 'A previsão de retorno deve ser posterior ao horário de saída.';
    } elseif ($filial_id <= 0) {
        $erro_formulario = 'Selecione a filial responsável pela viagem.';
    }

    if ($erro_formulario === '') {
        $query = $sql->prepare(
            "SELECT v.id, v.status, v.filial_id, v.categoria_cnh_exigida,
                    u.ativo, u.filial_id AS filial_motorista,
                    m.cnh_numero, m.cnh_categoria, m.cnh_validade
             FROM veiculos v
             INNER JOIN usuarios u ON u.id = ?
             LEFT JOIN motoristas m ON m.usuario_id = u.id
             WHERE v.id = ?
               AND v.ativo = 1
               AND u.perfil = 'motorista'"
        );

        $query->bind_param('ii', $motorista_id, $veiculo_id);
        $query->execute();
        $dados = $query->get_result()->fetch_assoc();
        $query->close();

        if (!$dados) {
            $erro_formulario = 'Veículo ou motorista inválido.';
        } elseif ((int) $dados['ativo'] !== 1) {
            $erro_formulario = 'O motorista selecionado está inativo.';
        } elseif ((int) $dados['filial_id'] !== $filial_id || (int) $dados['filial_motorista'] !== $filial_id) {
            $erro_formulario = 'Veículo e motorista precisam pertencer à filial da viagem.';
        } elseif (empty($dados['cnh_numero']) || empty($dados['cnh_categoria']) || empty($dados['cnh_validade'])) {
            $erro_formulario = 'O motorista não possui CNH completa cadastrada.';
        } elseif (!cnh_valida($dados['cnh_validade'])) {
            $erro_formulario = 'A CNH do motorista está vencida.';
        } elseif (!cnh_atende_categoria($dados['cnh_categoria'], $dados['categoria_cnh_exigida'])) {
            $erro_formulario = 'A categoria da CNH do motorista não atende ao veículo selecionado.';
        } elseif ($dados['status'] !== 'disponivel') {
            $erro_formulario = 'O veículo selecionado não está disponível.';
        }
    }

    if ($erro_formulario === '') {
        $query = $sql->prepare(
            "SELECT COUNT(*) AS total
             FROM viagens
             WHERE motorista_id = ?
               AND status IN ('agendada', 'em_andamento')
               AND data_partida < ?
               AND previsao_retorno > ?"
        );

        $query->bind_param('iss', $motorista_id, $previsao_retorno, $data_partida);
        $query->execute();
        $query->bind_result($conflito_motorista);
        $query->fetch();
        $query->close();

        if ((int) $conflito_motorista > 0) {
            $erro_formulario = 'O motorista já possui uma viagem no período informado.';
        }
    }

    if ($erro_formulario === '') {
        $query = $sql->prepare(
            "SELECT COUNT(*) AS total
             FROM viagens
             WHERE veiculo_id = ?
               AND status IN ('agendada', 'em_andamento')
               AND data_partida < ?
               AND previsao_retorno > ?"
        );

        $query->bind_param('iss', $veiculo_id, $previsao_retorno, $data_partida);
        $query->execute();
        $query->bind_result($conflito_veiculo);
        $query->fetch();
        $query->close();

        if ((int) $conflito_veiculo > 0) {
            $erro_formulario = 'O veículo já está vinculado a outra viagem no período informado.';
        }
    }

    if ($erro_formulario === '') {
        $sql->begin_transaction();

        try {
            $query = $sql->prepare(
                "INSERT INTO viagens
                (veiculo_id, motorista_id, gerente_id, filial_id, descricao_rota,
                 data_partida, previsao_retorno, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'agendada')"
            );

            $query->bind_param(
                'iiiisss',
                $veiculo_id,
                $motorista_id,
                $gerente_id,
                $filial_id,
                $descricao_rota,
                $data_partida,
                $previsao_retorno
            );

            if (!$query->execute()) {
                throw new Exception('Falha ao cadastrar a viagem.');
            }

            $viagem_id = $query->insert_id;
            $query->close();

            $query = $sql->prepare(
                "UPDATE veiculos
                 SET status = 'reservado'
                 WHERE id = ?
                   AND status = 'disponivel'"
            );

            $query->bind_param('i', $veiculo_id);

            if (!$query->execute() || $query->affected_rows !== 1) {
                throw new Exception('O veículo deixou de estar disponível.');
            }

            $query->close();
            $sql->commit();

            registrar_log($sql, 'CADASTRAR', 'viagens', $viagem_id, 'Viagem agendada');
            mensagem_sucesso('Viagem agendada com sucesso.');
            voltar('viagens.php');
        } catch (Throwable $erro) {
            $sql->rollback();
            $erro_formulario = 'Não foi possível agendar a viagem. Atualize a página e tente novamente.';
        }
    }
}

$sql_veiculos =
    "SELECT id, placa, modelo, categoria_cnh_exigida
     FROM veiculos
     WHERE ativo = 1
       AND status = 'disponivel'";

$sql_motoristas =
    "SELECT u.id, u.nome, m.cnh_categoria, m.cnh_validade
     FROM usuarios u
     INNER JOIN motoristas m ON m.usuario_id = u.id
     WHERE u.perfil = 'motorista'
       AND u.ativo = 1";

if (!$visao_global) {
    $sql_veiculos .= " AND filial_id = " . (int) $filial_usuario;
    $sql_motoristas .= " AND u.filial_id = " . (int) $filial_usuario;
}

$sql_veiculos .= " ORDER BY placa";
$sql_motoristas .= " ORDER BY u.nome";

$veiculos = $sql->query($sql_veiculos);
$motoristas = $sql->query($sql_motoristas);

$filiais = null;

if ($visao_global) {
    $filiais = $sql->query(
        "SELECT id, nome
         FROM filiais
         WHERE ativo = 1
         ORDER BY nome"
    );
}

$titulo = 'Nova Viagem';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Planejamento operacional</span>
        <h3>Agendar nova viagem</h3>
    </div>

    <a href="viagens.php" class="botao botao-secundario">
        <i class="fa-solid fa-arrow-left"></i>
        Voltar
    </a>
</div>

<?php if ($erro_formulario !== ''): ?>
    <div class="alert alert-danger shadow-sm">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?php echo escapar($erro_formulario); ?>
    </div>
<?php endif; ?>

<div class="form-layout">
    <div class="imagem shadow-sm">
        <i class="fa-solid fa-route"></i>
        <span>Ordem de viagem</span>
    </div>

    <div class="contain-form painel shadow-sm">
        <form method="POST">
            <div class="formulario">
                <div class="secao-form-titulo">
                    <div>
                        <strong>Rota e responsabilidade</strong>
                    </div>
                    <i class="fa-solid fa-route"></i>
                </div>

                <div class="input-box completa">
                    <label for="descricao_rota">Descrição da rota</label>
                    <textarea
                        id="descricao_rota"
                        name="descricao_rota"
                        maxlength="300"
                        placeholder="Ex.: Matriz São Paulo → Cliente em Guarulhos"
                        required
                    ><?php echo escapar($_POST['descricao_rota'] ?? ''); ?></textarea>
                </div>

                <?php if ($visao_global): ?>
                    <div class="input-box completa">
                        <label for="filial_id">Filial responsável</label>
                        <select id="filial_id" name="filial_id" required>
                            <option value="">Selecione</option>

                            <?php while ($filial = $filiais->fetch_assoc()): ?>
                                <option value="<?php echo $filial['id']; ?>" <?php echo ((int) ($_POST['filial_id'] ?? 0) === (int) $filial['id']) ? 'selected' : ''; ?>>
                                    <?php echo escapar($filial['nome']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="input-box">
                    <label for="veiculo_id">Veículo</label>
                    <select id="veiculo_id" name="veiculo_id" required>
                        <option value="">Selecione</option>

                        <?php while ($veiculo = $veiculos->fetch_assoc()): ?>
                            <option value="<?php echo $veiculo['id']; ?>" <?php echo ((int) ($_POST['veiculo_id'] ?? 0) === (int) $veiculo['id']) ? 'selected' : ''; ?>>
                                <?php echo escapar($veiculo['placa'] . ' - ' . $veiculo['modelo'] . ' | CNH ' . $veiculo['categoria_cnh_exigida']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input-box">
                    <label for="motorista_id">Motorista</label>
                    <select id="motorista_id" name="motorista_id" required>
                        <option value="">Selecione</option>

                        <?php while ($motorista = $motoristas->fetch_assoc()): ?>
                            <?php $cnh_ok = cnh_valida($motorista['cnh_validade']); ?>
                            <option
                                value="<?php echo $motorista['id']; ?>"
                                <?php echo ((int) ($_POST['motorista_id'] ?? 0) === (int) $motorista['id']) ? 'selected' : ''; ?>
                            >
                                <?php
                                echo escapar(
                                    $motorista['nome'] .
                                    ' | CNH ' . $motorista['cnh_categoria'] .
                                    ($cnh_ok ? '' : ' - VENCIDA')
                                );
                                ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="secao-form-titulo">
                    <div>
                        <strong>Período da viagem</strong>
                    </div>
                    <i class="fa-solid fa-calendar-check"></i>
                </div>

                <div class="input-box">
                    <label for="data_partida">Data e hora de saída</label>
                    <input
                        type="datetime-local"
                        id="data_partida"
                        name="data_partida"
                        required
                        value="<?php echo escapar($_POST['data_partida'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="previsao_retorno">Previsão de retorno</label>
                    <input
                        type="datetime-local"
                        id="previsao_retorno"
                        name="previsao_retorno"
                        required
                        value="<?php echo escapar($_POST['previsao_retorno'] ?? ''); ?>"
                    >
                </div>

                <div class="fin">
                    <a class="botao botao-secundario" href="viagens.php">Cancelar</a>
                    <button class="botao botao-principal shadow-sm" type="submit">
                        <i class="fa-solid fa-calendar-check"></i>
                        Agendar viagem
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../rodape.php'; ?>
