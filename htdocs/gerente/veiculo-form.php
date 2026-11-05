<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$visao_global = $perfil_atual === 'admin';
$filial_usuario = filial_do_usuario();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$registro = null;
$erro_formulario = '';

if ($id > 0) {
    $sql_busca =
        "SELECT *
         FROM veiculos
         WHERE id = ?";

    if (!$visao_global) {
        $sql_busca .= " AND filial_id = " . (int) $filial_usuario;
    }

    $query = $sql->prepare($sql_busca);
    $query->bind_param('i', $id);
    $query->execute();
    $registro = $query->get_result()->fetch_assoc();
    $query->close();

    if (!$registro) {
        mensagem_erro('Veículo não encontrado ou fora da sua filial.');
        voltar('veiculos.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placa = strtoupper(trim($_POST['placa'] ?? ''));
    $modelo = trim($_POST['modelo'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    $chassi = strtoupper(trim($_POST['chassi'] ?? ''));
    $renavam = preg_replace('/\D/', '', $_POST['renavam'] ?? '');
    $ano = (int) ($_POST['ano'] ?? 0);
    $categoria = $_POST['categoria_cnh_exigida'] ?? 'B';
    $odometro_inicial = (int) ($_POST['odometro_inicial'] ?? 0);
    $odometro_atual = (int) ($_POST['odometro_atual'] ?? $odometro_inicial);
    $status = $_POST['status'] ?? 'disponivel';
    $ativo = (int) ($_POST['ativo'] ?? 1) === 1 ? 1 : 0;

    if ($visao_global) {
        $filial_id = (int) ($_POST['filial_id'] ?? 0);
    } else {
        $filial_id = $filial_usuario;
    }

    if ($placa === '' || $modelo === '' || $marca === '' || $chassi === '' || $renavam === '') {
        $erro_formulario = 'Preencha todos os campos obrigatórios do veículo.';
    } elseif (strlen($placa) !== 7) {
        $erro_formulario = 'A placa deve possuir 7 caracteres no padrão Mercosul.';
    } elseif (strlen($chassi) !== 17) {
        $erro_formulario = 'O chassi deve possuir exatamente 17 caracteres.';
    } elseif (strlen($renavam) !== 11) {
        $erro_formulario = 'O RENAVAM deve possuir exatamente 11 números.';
    } elseif ($ano < 1900 || $ano > ((int) date('Y') + 1)) {
        $erro_formulario = 'Informe um ano válido para o veículo.';
    } elseif ($odometro_inicial > 9999999 || $odometro_atual > 9999999) {
        $erro_formulario = 'Informe uma quilometragem de até 9.999.999 km.';
    } elseif ($odometro_atual < $odometro_inicial) {
        $erro_formulario = 'O odômetro atual não pode ser menor que o odômetro inicial.';
    } elseif (!in_array($categoria, ['A', 'B', 'C', 'D', 'E'], true)) {
        $erro_formulario = 'Selecione uma categoria de CNH válida.';
    } elseif (!in_array($status, ['disponivel', 'reservado', 'em_transito', 'manutencao'], true)) {
        $erro_formulario = 'Selecione um status válido.';
    } elseif ($filial_id <= 0) {
        $erro_formulario = 'Vincule o veículo a uma filial.';
    }

    if ($erro_formulario === '') {
        if ($id > 0) {
            $sql_update =
                "UPDATE veiculos
                 SET placa = ?, modelo = ?, marca = ?, chassi = ?, renavam = ?,
                     ano = ?, categoria_cnh_exigida = ?, odometro_inicial = ?,
                     odometro_atual = ?, status = ?, filial_id = ?, ativo = ?
                 WHERE id = ?";

            if (!$visao_global) {
                $sql_update .= " AND filial_id = " . (int) $filial_usuario;
            }

            $query = $sql->prepare($sql_update);
            $query->bind_param(
                'sssssisiisiii',
                $placa,
                $modelo,
                $marca,
                $chassi,
                $renavam,
                $ano,
                $categoria,
                $odometro_inicial,
                $odometro_atual,
                $status,
                $filial_id,
                $ativo,
                $id
            );
        } else {
            $query = $sql->prepare(
                "INSERT INTO veiculos
                (placa, modelo, marca, chassi, renavam, ano, categoria_cnh_exigida,
                 odometro_inicial, odometro_atual, status, filial_id, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $query->bind_param(
                'sssssisiisii',
                $placa,
                $modelo,
                $marca,
                $chassi,
                $renavam,
                $ano,
                $categoria,
                $odometro_inicial,
                $odometro_atual,
                $status,
                $filial_id,
                $ativo
            );
        }

        if ($query->execute()) {
            $registro_id = $id > 0 ? $id : $query->insert_id;

            registrar_log(
                $sql,
                $id > 0 ? 'EDITAR' : 'CADASTRAR',
                'veiculos',
                $registro_id,
                $id > 0 ? 'Veículo atualizado' : 'Veículo cadastrado'
            );

            mensagem_sucesso($id > 0 ? 'Veículo atualizado com sucesso.' : 'Veículo cadastrado com sucesso.');
            $query->close();
            voltar('veiculos.php');
        }

        $erro_formulario = 'Não foi possível salvar. Verifique se placa, chassi ou RENAVAM já estão cadastrados.';
        $query->close();
    }

    $registro = [
        'placa' => $placa,
        'modelo' => $modelo,
        'marca' => $marca,
        'chassi' => $chassi,
        'renavam' => $renavam,
        'ano' => $ano,
        'categoria_cnh_exigida' => $categoria,
        'odometro_inicial' => $odometro_inicial,
        'odometro_atual' => $odometro_atual,
        'status' => $status,
        'filial_id' => $filial_id,
        'ativo' => $ativo
    ];
}

$filiais = null;

if ($visao_global) {
    $filiais = $sql->query(
        "SELECT id, nome
         FROM filiais
         WHERE ativo = 1
         ORDER BY nome"
    );
}

$titulo = $id > 0 ? 'Editar Veículo' : 'Cadastrar Veículo';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Gestão da frota</span>
        <h3><?php echo $id > 0 ? 'Atualizar veículo' : 'Novo veículo'; ?></h3>
    </div>

    <a href="veiculos.php" class="botao botao-secundario">
        <i class="fa-solid fa-arrow-left"></i>
        Voltar
    </a>
</div>

<?php if ($erro_formulario !== ''): ?>
    <div class="alert alert-danger shadow-sm">
        <?php echo escapar($erro_formulario); ?>
    </div>
<?php endif; ?>

<div class="form-layout">
    <div class="imagem shadow-sm">
        <i class="fa-solid fa-car-side"></i>
        <span>Patrimônio da frota</span>
    </div>

    <div class="contain-form painel shadow-sm">
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="formulario">
                <div class="secao-form-titulo">
                    <div>
                        <strong>Identificação do veículo</strong>
                    </div>
                    <i class="fa-solid fa-car-side"></i>
                </div>

                <div class="input-box">
                    <label for="placa">Placa</label>
                    <input
                        type="text"
                        id="placa"
                        name="placa"
                        maxlength="7"
                        data-mask="placa"
                        placeholder="ABC1D23"
                        required
                        value="<?php echo escapar($registro['placa'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="marca">Marca</label>
                    <input type="text" id="marca" name="marca" maxlength="60" placeholder="Ex.: Toyota" required value="<?php echo escapar($registro['marca'] ?? ''); ?>">
                </div>

                <div class="input-box">
                    <label for="modelo">Modelo</label>
                    <input type="text" id="modelo" name="modelo" maxlength="80" placeholder="Ex.: Corolla" required value="<?php echo escapar($registro['modelo'] ?? ''); ?>">
                </div>

                <div class="input-box">
                    <label for="ano">Ano</label>
                    <input
                        type="text"
                        id="ano"
                        name="ano"
                        maxlength="4"
                        inputmode="numeric"
                        data-mask="ano"
                        required
                        value="<?php echo escapar($registro['ano'] ?? date('Y')); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="chassi">Chassi</label>
                    <input
                        type="text"
                        id="chassi"
                        name="chassi"
                        maxlength="17"
                        data-mask="chassi"
                        placeholder="17 caracteres"
                        required
                        value="<?php echo escapar($registro['chassi'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="renavam">RENAVAM</label>
                    <input
                        type="text"
                        id="renavam"
                        name="renavam"
                        maxlength="11"
                        inputmode="numeric"
                        data-mask="renavam"
                        placeholder="00000000000"
                        required
                        value="<?php echo escapar($registro['renavam'] ?? ''); ?>"
                    >
                </div>

                <div class="secao-form-titulo">
                    <div>
                        <strong>Operação e controle</strong>
                        <span>CNH exigida, quilometragem, filial e status do ativo.</span>
                    </div>
                    <i class="fa-solid fa-gauge-high"></i>
                </div>

                <div class="input-box">
                    <label for="categoria_cnh_exigida">Categoria de CNH exigida</label>
                    <select id="categoria_cnh_exigida" name="categoria_cnh_exigida" required>
                        <?php foreach (['A', 'B', 'C', 'D', 'E'] as $categoria): ?>
                            <option
                                value="<?php echo $categoria; ?>"
                                <?php echo (($registro['categoria_cnh_exigida'] ?? 'B') === $categoria) ? 'selected' : ''; ?>
                            >
                                <?php echo $categoria; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-box">
                    <label for="odometro_inicial">Odômetro inicial</label>
                    <input
                        type="number"
                        id="odometro_inicial"
                        name="odometro_inicial"
                        min="0"
                        required
                        value="<?php echo escapar($registro['odometro_inicial'] ?? 0); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="odometro_atual">Odômetro atual</label>
                    <input
                        type="number"
                        id="odometro_atual"
                        name="odometro_atual"
                        min="0"
                        required
                        value="<?php echo escapar($registro['odometro_atual'] ?? $registro['odometro_inicial'] ?? 0); ?>"
                    >
                </div>

                <?php if ($visao_global): ?>
                    <div class="input-box">
                        <label for="filial_id">Filial</label>
                        <select id="filial_id" name="filial_id" required>
                            <option value="">Selecione</option>

                            <?php while ($filial = $filiais->fetch_assoc()): ?>
                                <option
                                    value="<?php echo $filial['id']; ?>"
                                    <?php echo ((int) ($registro['filial_id'] ?? 0) === (int) $filial['id']) ? 'selected' : ''; ?>
                                >
                                    <?php echo escapar($filial['nome']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="input-box">
                        <label>Filial</label>
                        <input type="text" value="Sua filial de acesso" readonly>
                    </div>
                <?php endif; ?>

                <div class="input-box">
                    <label for="status">Status operacional</label>
                    <select id="status" name="status">
                        <option value="disponivel" <?php echo (($registro['status'] ?? 'disponivel') === 'disponivel') ? 'selected' : ''; ?>>Disponível</option>
                        <option value="reservado" <?php echo (($registro['status'] ?? '') === 'reservado') ? 'selected' : ''; ?>>Reservado</option>
                        <option value="em_transito" <?php echo (($registro['status'] ?? '') === 'em_transito') ? 'selected' : ''; ?>>Em trânsito</option>
                        <option value="manutencao" <?php echo (($registro['status'] ?? '') === 'manutencao') ? 'selected' : ''; ?>>Manutenção</option>
                    </select>
                </div>

                <div class="input-box">
                    <label for="ativo">Situação cadastral</label>
                    <select id="ativo" name="ativo">
                        <option value="1" <?php echo ((int) ($registro['ativo'] ?? 1) === 1) ? 'selected' : ''; ?>>Ativo</option>
                        <option value="0" <?php echo ((int) ($registro['ativo'] ?? 1) === 0) ? 'selected' : ''; ?>>Inativo</option>
                    </select>
                </div>

                <div class="fin">
                    <a class="botao botao-secundario" href="veiculos.php">Cancelar</a>
                    <button class="botao botao-principal shadow-sm" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Salvar veículo
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>



<?php include '../rodape.php'; ?>
