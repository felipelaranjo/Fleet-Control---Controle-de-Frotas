<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('admin');

if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];

    $query = $sql->prepare(
        "UPDATE filiais
         SET ativo = 0
         WHERE id = ?"
    );

    $query->bind_param('i', $id);
    $query->execute();
    $query->close();

    registrar_log($sql, 'INATIVAR', 'filiais', $id, 'Filial inativada');
    mensagem_sucesso('Filial inativada com sucesso.');
    voltar('filiais.php');
}

$lista = $sql->query(
    "SELECT f.*,
            (SELECT COUNT(*) FROM usuarios u WHERE u.filial_id = f.id AND u.ativo = 1) AS total_usuarios,
            (SELECT COUNT(*) FROM veiculos v WHERE v.filial_id = f.id AND v.ativo = 1) AS total_veiculos
     FROM filiais f
     ORDER BY f.nome"
);

$titulo = 'Filiais';
$prefixo = '../';
include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Estrutura da empresa</span>
        <h3>Filiais cadastradas</h3>
    </div>

    <a class="botao botao-principal shadow-sm" href="filial-form.php">
        <i class="fa-solid fa-plus"></i>
        Cadastrar filial
    </a>
</div>

<div class="painel shadow-sm">
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Unidade</th>
                    <th>CNPJ</th>
                    <th>Localização</th>
                    <th>Usuários</th>
                    <th>Veículos</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($filial = $lista->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="usuario-tabela">
                                <div class="filial-thumb">
                                    <?php if (!empty($filial['imagem'])): ?>
                                        <img src="../uploads/filiais/<?php echo escapar($filial['imagem']); ?>" alt="<?php echo escapar($filial['nome']); ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-building"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?php echo escapar($filial['nome']); ?></strong>
                                    <span class="tabela-subtexto"><?php echo escapar($filial['endereco']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?php echo escapar($filial['cnpj']); ?></td>
                        <td>
                            <?php echo escapar($filial['cidade']); ?>
                            <span class="tabela-subtexto"><?php echo escapar($filial['estado']); ?></span>
                        </td>
                        <td><?php echo (int) $filial['total_usuarios']; ?></td>
                        <td><?php echo (int) $filial['total_veiculos']; ?></td>
                        <td>
                            <span class="status <?php echo $filial['ativo'] ? 'disponivel' : 'cancelada'; ?>">
                                <?php echo $filial['ativo'] ? 'Ativa' : 'Inativa'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="acoes-tabela">
                                <a class="botao botao-secundario" href="filial-form.php?id=<?php echo $filial['id']; ?>">
                                    <i class="fa-solid fa-pen"></i>
                                    Editar
                                </a>

                                <?php if ($filial['ativo']): ?>
                                    <a
                                        class="botao botao-perigo"
                                        data-confirmar="Inativar esta filial?"
                                        href="filiais.php?excluir=<?php echo $filial['id']; ?>"
                                    >
                                        <i class="fa-solid fa-ban"></i>
                                        Inativar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../rodape.php'; ?>
