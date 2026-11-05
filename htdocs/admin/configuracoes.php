<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = (int) $_SESSION['usuario']['id'];

    foreach (($_POST['valor'] ?? []) as $id => $valor) {
        $id = (int) $id;
        $valor = trim($valor);

        $query = $sql->prepare(
            "UPDATE configuracoes_globais
             SET valor = ?, atualizado_por = ?
             WHERE id = ?"
        );

        $query->bind_param('sii', $valor, $usuario_id, $id);
        $query->execute();
        $query->close();
    }

    registrar_log(
        $sql,
        'EDITAR',
        'configuracoes_globais',
        null,
        'Parâmetros globais atualizados'
    );

    mensagem_sucesso('Configurações atualizadas com sucesso.');
    voltar('configuracoes.php');
}

$configuracoes = $sql->query(
    "SELECT *
     FROM configuracoes_globais
     ORDER BY id"
);

$titulo = 'Configurações Globais';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Administração do sistema</span>
        <h3>Parâmetros globais</h3>
    </div>

    <span class="status andamento">Acesso exclusivo do Admin</span>
</div>

<div class="painel shadow-sm">
    <form method="POST">
        <div class="formulario">
            <div class="secao-form-titulo">
                <div>
                    <strong>Parâmetros operacionais</strong>
                </div>
                <i class="fa-solid fa-gear"></i>
            </div>

            <?php while ($configuracao = $configuracoes->fetch_assoc()): ?>
                <div class="input-box">
                    <label for="config-<?php echo $configuracao['id']; ?>">
                        <?php echo escapar(str_replace('_', ' ', ucfirst($configuracao['chave']))); ?>
                    </label>

                    <input
                        type="text"
                        id="config-<?php echo $configuracao['id']; ?>"
                        name="valor[<?php echo $configuracao['id']; ?>]"
                        maxlength="255"
                        required
                        value="<?php echo escapar($configuracao['valor']); ?>"
                    >

                    <small class="texto-ajuda">
                        <?php echo escapar($configuracao['descricao']); ?>
                    </small>
                </div>
            <?php endwhile; ?>

            <div class="fin">
                <button class="botao botao-principal shadow-sm" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Salvar alterações
                </button>
            </div>
        </div>
    </form>
</div>

<?php include '../rodape.php'; ?>
