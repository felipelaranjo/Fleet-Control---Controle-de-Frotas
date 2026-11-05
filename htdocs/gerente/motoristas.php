<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('gerente');

$perfil_atual = $_SESSION['usuario']['perfil'];
$visao_global = $perfil_atual === 'admin';
$filial_usuario = filial_do_usuario();

$titulo = 'Motoristas';
$prefixo = '../';

$busca = trim($_GET['busca'] ?? '');
$situacao_cnh = $_GET['cnh'] ?? '';

$sql_lista =
    "SELECT u.id, u.nome, u.cpf, u.rg, u.email, u.telefone, u.foto, u.ativo, u.filial_id,
            m.cnh_numero, m.cnh_categoria, m.cnh_validade,
            f.nome AS filial
     FROM usuarios u
     LEFT JOIN motoristas m ON m.usuario_id = u.id
     LEFT JOIN filiais f ON f.id = u.filial_id
     WHERE u.perfil = 'motorista'";

$tipos = '';
$parametros = [];

if (!$visao_global) {
    $sql_lista .= " AND u.filial_id = ?";
    $tipos .= 'i';
    $parametros[] = $filial_usuario;
}

if ($busca !== '') {
    $sql_lista .= " AND (u.nome LIKE ? OR u.cpf LIKE ? OR m.cnh_numero LIKE ?)";
    $termo = '%' . $busca . '%';
    $tipos .= 'sss';
    $parametros[] = $termo;
    $parametros[] = $termo;
    $parametros[] = $termo;
}

if ($situacao_cnh === 'vencida') {
    $sql_lista .= " AND m.cnh_validade < CURDATE()";
} elseif ($situacao_cnh === 'valida') {
    $sql_lista .= " AND m.cnh_validade >= CURDATE()";
} elseif ($situacao_cnh === 'pendente') {
    $sql_lista .= " AND m.usuario_id IS NULL";
}

$sql_lista .= " ORDER BY u.nome";

$query = $sql->prepare($sql_lista);

if ($tipos !== '') {
    $query->bind_param($tipos, ...$parametros);
}

$query->execute();
$lista = $query->get_result();

include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta"><?php echo $visao_global ? 'Visão administrativa' : 'Nível 2 - Gestão da filial'; ?></span>
        <h3>Motoristas da frota</h3>
    </div>

    <?php if ($visao_global): ?>
        <a href="../admin/usuario-form.php" class="botao botao-principal shadow-sm">
            <i class="fa-solid fa-user-plus"></i>
            Cadastrar motorista
        </a>
    <?php endif; ?>
</div>

<div class="painel shadow-sm">
    <form method="GET" class="filtros">
        <div class="input-box filtro-busca">
            <label for="busca">Buscar motorista</label>
            <input
                type="text"
                id="busca"
                name="busca"
                placeholder="Nome, CPF ou CNH"
                value="<?php echo escapar($busca); ?>"
            >
        </div>

        <div class="input-box">
            <label for="cnh">Situação da CNH</label>
            <select id="cnh" name="cnh">
                <option value="">Todas</option>
                <option value="valida" <?php echo $situacao_cnh === 'valida' ? 'selected' : ''; ?>>Válida</option>
                <option value="vencida" <?php echo $situacao_cnh === 'vencida' ? 'selected' : ''; ?>>Vencida</option>
                <option value="pendente" <?php echo $situacao_cnh === 'pendente' ? 'selected' : ''; ?>>Não cadastrada</option>
            </select>
        </div>

        <div class="filtro-acoes">
            <button class="botao botao-principal" type="submit">
                <i class="fa-solid fa-magnifying-glass"></i>
                Filtrar
            </button>

            <a href="motoristas.php" class="botao botao-secundario">Limpar</a>
        </div>
    </form>
</div>

<div class="painel shadow-sm">
    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Motorista</th>
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>CNH</th>
                    <th>Categoria</th>
                    <th>Validade</th>
                    <th>Filial</th>
                    <th>Situação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lista->num_rows === 0): ?>
                    <tr>
                        <td colspan="9" class="tabela-vazia">Nenhum motorista encontrado.</td>
                    </tr>
                <?php endif; ?>

                <?php while ($motorista = $lista->fetch_assoc()): ?>
                    <?php
                    $tem_cnh = !empty($motorista['cnh_numero']) && !empty($motorista['cnh_validade']);
                    $cnh_esta_valida = $tem_cnh && cnh_valida($motorista['cnh_validade']);
                    ?>
                    <tr>
                        <td>
                            <div class="usuario-tabela">
                                <span class="foto-lista">
                                    <?php if (!empty($motorista['foto'])): ?>
                                        <img src="../uploads/usuarios/<?php echo escapar($motorista['foto']); ?>" alt="Foto de <?php echo escapar($motorista['nome']); ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-user"></i>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <strong><?php echo escapar($motorista['nome']); ?></strong>
                                    <small class="tabela-subtexto"><?php echo escapar($motorista['email']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo escapar($motorista['cpf']); ?></td>
                        <td><?php echo escapar($motorista['telefone'] ?: '-'); ?></td>
                        <td><?php echo escapar($motorista['cnh_numero'] ?: 'Não cadastrada'); ?></td>
                        <td><?php echo escapar($motorista['cnh_categoria'] ?: '-'); ?></td>
                        <td><?php echo $tem_cnh ? data_br($motorista['cnh_validade']) : '-'; ?></td>
                        <td><?php echo escapar($motorista['filial'] ?? '-'); ?></td>
                        <td>
                            <?php if (!$tem_cnh): ?>
                                <span class="status cancelada">CNH obrigatória</span>
                            <?php elseif (!$cnh_esta_valida): ?>
                                <span class="status cancelada">CNH vencida</span>
                            <?php else: ?>
                                <span class="status disponivel">Habilitado</span>
                            <?php endif; ?>

                            <?php if (!(int) $motorista['ativo']): ?>
                                <span class="status cancelada">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="botao botao-secundario shadow-sm" href="motorista-form.php?id=<?php echo $motorista['id']; ?>">
                                <i class="fa-solid fa-pen"></i>
                                Editar
                            </a>
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
