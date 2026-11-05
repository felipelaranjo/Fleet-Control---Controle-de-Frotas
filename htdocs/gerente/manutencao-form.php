<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$visao_global = $perfil_atual === 'admin';
$filial_usuario = filial_do_usuario();
$veiculo_selecionado = (int) ($_GET['veiculo_id'] ?? $_POST['veiculo_id'] ?? 0);
$erro_formulario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $veiculo_id = (int) ($_POST['veiculo_id'] ?? 0);
    $tipo_servico = trim($_POST['tipo_servico'] ?? '');
    $km_limite = (int) ($_POST['km_limite'] ?? 0);
    $data_agendamento = $_POST['data_agendamento'] ?? '';
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($veiculo_id <= 0 || $tipo_servico === '' || $km_limite <= 0) {
        $erro_formulario = 'Informe o veículo, o serviço e a quilometragem limite.';
    } elseif ($km_limite > 9999999) {
        $erro_formulario = 'A quilometragem limite deve ser de até 9.999.999 km.';
    }

    if ($erro_formulario === '') {
        $sql_verificacao =
            "SELECT id, odometro_atual
             FROM veiculos
             WHERE id = ?
               AND ativo = 1";

        if (!$visao_global) {
            $sql_verificacao .= " AND filial_id = " . (int) $filial_usuario;
        }

        $query = $sql->prepare($sql_verificacao);
        $query->bind_param('i', $veiculo_id);
        $query->execute();
        $veiculo = $query->get_result()->fetch_assoc();
        $query->close();

        if (!$veiculo) {
            $erro_formulario = 'Veículo inválido ou fora da sua filial.';
        } elseif ($km_limite < (int) $veiculo['odometro_atual']) {
            $erro_formulario = 'A quilometragem limite não pode ser menor que o odômetro atual do veículo.';
        }
    }

    if ($erro_formulario === '') {
        $query = $sql->prepare(
            "INSERT INTO planos_manutencao
            (veiculo_id, tipo_servico, km_limite, data_agendamento, status, observacoes)
            VALUES (?, ?, ?, NULLIF(?, ''), 'pendente', ?)"
        );

        $query->bind_param(
            'isiss',
            $veiculo_id,
            $tipo_servico,
            $km_limite,
            $data_agendamento,
            $observacoes
        );

        if ($query->execute()) {
            $plano_id = $query->insert_id;
            registrar_log($sql, 'CADASTRAR', 'planos_manutencao', $plano_id, 'Plano de manutenção cadastrado');
            mensagem_sucesso('Plano de manutenção cadastrado com sucesso.');
            $query->close();
            voltar('manutencoes.php');
        }

        $erro_formulario = 'Não foi possível cadastrar a manutenção.';
        $query->close();
    }
}

$sql_veiculos =
    "SELECT id, placa, modelo, odometro_atual
     FROM veiculos
     WHERE ativo = 1";

if (!$visao_global) {
    $sql_veiculos .= " AND filial_id = " . (int) $filial_usuario;
}

$sql_veiculos .= " ORDER BY placa";
$veiculos = $sql->query($sql_veiculos);

$titulo = 'Nova Manutenção';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Manutenção da frota</span>
        <h3>Novo plano de manutenção</h3>
    </div>

    <a href="manutencoes.php" class="botao botao-secundario">
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
        <i class="fa-solid fa-screwdriver-wrench"></i>
        <span>Saúde do veículo</span>
    </div>

    <div class="contain-form painel shadow-sm">
        <form method="POST">
            <div class="formulario">
                <div class="secao-form-titulo">
                    <div>
                        <strong>Planejamento da manutenção</strong>
                    </div>
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>

                <div class="input-box completa">
                    <label for="veiculo_id">Veículo</label>
                    <select id="veiculo_id" name="veiculo_id" required>
                        <option value="">Selecione</option>

                        <?php while ($veiculo = $veiculos->fetch_assoc()): ?>
                            <option
                                value="<?php echo $veiculo['id']; ?>"
                                <?php echo $veiculo_selecionado === (int) $veiculo['id'] ? 'selected' : ''; ?>
                            >
                                <?php echo escapar($veiculo['placa'] . ' - ' . $veiculo['modelo'] . ' | ' . number_format($veiculo['odometro_atual'], 0, ',', '.') . ' km'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input-box">
                    <label for="tipo_servico">Tipo de serviço</label>
                    <input
                        type="text"
                        id="tipo_servico"
                        name="tipo_servico"
                        maxlength="100"
                        placeholder="Ex.: Troca de óleo e filtros"
                        required
                        value="<?php echo escapar($_POST['tipo_servico'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="km_limite">Quilometragem limite</label>
                    <input
                        type="number"
                        id="km_limite"
                        name="km_limite"
                        min="1"
                        max="9999999"
                        step="1"
                        inputmode="numeric"
                        placeholder="Ex.: 45000"
                        required
                        value="<?php echo escapar($_POST['km_limite'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="data_agendamento">Data prevista</label>
                    <input type="date" id="data_agendamento" name="data_agendamento" value="<?php echo escapar($_POST['data_agendamento'] ?? ''); ?>">
                </div>

                <div class="input-box completa">
                    <label for="observacoes">Observações</label>
                    <textarea
                        id="observacoes"
                        name="observacoes"
                        maxlength="500"
                        placeholder="Informações adicionais, peças previstas ou observações da oficina."
                    ><?php echo escapar($_POST['observacoes'] ?? ''); ?></textarea>
                </div>

                <div class="fin">
                    <a class="botao botao-secundario" href="manutencoes.php">Cancelar</a>
                    <button class="botao botao-principal shadow-sm" type="submit">Cadastrar manutenção</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../rodape.php'; ?>
