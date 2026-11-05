<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('motorista');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$id_motorista = (int) $_SESSION['usuario']['id'];
$erro_formulario = '';

$query = $sql->prepare(
    "SELECT v.id, v.veiculo_id, v.status, v.odometro_inicial,
            ve.placa, ve.modelo, ve.odometro_atual
     FROM viagens v
     INNER JOIN veiculos ve ON ve.id = v.veiculo_id
     WHERE v.id = ?
       AND v.motorista_id = ?
     LIMIT 1"
);
$query->bind_param('ii', $id, $id_motorista);
$query->execute();
$viagem = $query->get_result()->fetch_assoc();
$query->close();

if (!$viagem || $viagem['status'] !== 'agendada') {
    mensagem_erro('Esta viagem não está disponível para check-in.');
    voltar('viagens.php');
}

if ($viagem['odometro_inicial'] !== null) {
    voltar('checklist.php?id=' . $id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $odometro = (int) ($_POST['odometro_inicial'] ?? 0);
    $combustivel = (float) ($_POST['combustivel_inicial'] ?? -1);

    if ($odometro <= 0) {
        $erro_formulario = 'Informe a quilometragem atual do veículo.';
    } elseif ($odometro > 9999999) {
        $erro_formulario = 'A quilometragem informada é muito alta.';
    } elseif ($combustivel < 0 || $combustivel > 100) {
        $erro_formulario = 'O nível de combustível deve estar entre 0 e 100%.';
    }

    if ($erro_formulario === '') {
        $diferenca = abs($odometro - (int) $viagem['odometro_atual']);

        $query = $sql->prepare(
            "UPDATE viagens
             SET odometro_inicial = ?, combustivel_inicial = ?
             WHERE id = ?
               AND motorista_id = ?
               AND status = 'agendada'"
        );

        $query->bind_param('idii', $odometro, $combustivel, $id, $id_motorista);

        if ($query->execute()) {
            if ($diferenca > 5) {
                registrar_log(
                    $sql,
                    'ALERTA_ODOMETRO',
                    'viagens',
                    $id,
                    'Divergência superior a 5 km no check-in'
                );
            }

            registrar_log($sql, 'CHECK-IN', 'viagens', $id, 'Check-in registrado; aguardando checklist');
            mensagem_sucesso('Check-in registrado. Agora preencha o checklist obrigatório.');
            $query->close();
            voltar('checklist.php?id=' . $id);
        }

        $erro_formulario = 'Não foi possível registrar o check-in.';
        $query->close();
    }
}

$titulo = 'Check-in de Viagem';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Etapa 1 de 2</span>
        <h3>Registrar início da viagem</h3>
    </div>
    <span class="status reservada"><?php echo escapar($viagem['placa'] . ' - ' . $viagem['modelo']); ?></span>
</div>

<?php if ($erro_formulario !== ''): ?>
    <div class="alert alert-danger shadow-sm"><?php echo escapar($erro_formulario); ?></div>
<?php endif; ?>

<div class="form-layout">
    <div class="imagem shadow-sm">
        <i class="fa-solid fa-gauge-high"></i>
        <span><?php echo escapar($viagem['placa']); ?></span>
    </div>

    <div class="contain-form painel shadow-sm">
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <div class="formulario">
                <div class="aviso-form completa">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Último odômetro registrado: <strong><?php echo number_format($viagem['odometro_atual'], 0, ',', '.'); ?> km</strong></span>
                </div>

                <div class="input-box">
                    <label for="odometro_inicial">Odômetro atual</label>
                    <input
                        type="number"
                        id="odometro_inicial"
                        name="odometro_inicial"
                        min="0"
                        max="9999999"
                        step="1"
                        inputmode="numeric"
                        placeholder="Ex.: 42350"
                        required
                        value="<?php echo escapar($_POST['odometro_inicial'] ?? ''); ?>"
                    >
                </div>

                <div class="input-box">
                    <label for="combustivel_inicial">Combustível inicial (%)</label>
                    <input
                        type="number"
                        id="combustivel_inicial"
                        name="combustivel_inicial"
                        min="0"
                        max="100"
                        step="0.1"
                        inputmode="decimal"
                        placeholder="Ex.: 75"
                        required
                        value="<?php echo escapar($_POST['combustivel_inicial'] ?? ''); ?>"
                    >
                </div>

                <div class="fin">
                    <a class="botao botao-secundario" href="viagem-detalhes.php?id=<?php echo $id; ?>">Voltar</a>
                    <button class="botao botao-principal shadow-sm" type="submit">Continuar para checklist</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include '../rodape.php'; ?>
