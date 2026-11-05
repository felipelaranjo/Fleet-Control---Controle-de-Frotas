<?php
include "../conexao.php";
include "../funcoes.php";

exigir_perfil('admin');

$titulo = 'Usuários';
$prefixo = '../';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_status'])) {
    $id = (int) ($_POST['usuario_id'] ?? 0);
    $novo_status = (int) ($_POST['novo_status'] ?? 0) === 1 ? 1 : 0;

    if ($id === (int) $_SESSION['usuario']['id'] && $novo_status === 0) {
        mensagem_erro('Você não pode inativar a própria conta enquanto está logado.');
        voltar('usuarios.php');
    }

    $query = $sql->prepare(
        "UPDATE usuarios
         SET ativo = ?
         WHERE id = ?"
    );

    $query->bind_param('ii', $novo_status, $id);

    if ($query->execute()) {
        registrar_log(
            $sql,
            $novo_status === 1 ? 'ATIVAR' : 'INATIVAR',
            'usuarios',
            $id,
            $novo_status === 1 ? 'Usuário ativado pelo Administrador' : 'Usuário inativado pelo Administrador'
        );

        mensagem_sucesso($novo_status === 1 ? 'Usuário ativado com sucesso.' : 'Usuário inativado com sucesso.');
    } else {
        mensagem_erro('Não foi possível alterar a situação do usuário.');
    }

    $query->close();
    voltar('usuarios.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_motorista'])) {
    $id = (int) ($_POST['usuario_id'] ?? 0);

    $query = $sql->prepare(
        "SELECT COUNT(*)
         FROM viagens
         WHERE motorista_id = ?"
    );

    $query->bind_param('i', $id);
    $query->execute();
    $query->bind_result($total_viagens);
    $query->fetch();
    $query->close();

    if ((int) $total_viagens > 0) {
        mensagem_erro('Este motorista possui viagens registradas. Inative o cadastro para preservar o histórico.');
        voltar('usuarios.php');
    }

    $query = $sql->prepare(
        "DELETE FROM usuarios
         WHERE id = ?
           AND perfil = 'motorista'"
    );

    $query->bind_param('i', $id);

    if ($query->execute() && $query->affected_rows === 1) {
        registrar_log($sql, 'EXCLUIR', 'usuarios', $id, 'Motorista excluído pelo Administrador');
        mensagem_sucesso('Motorista excluído com sucesso.');
    } else {
        mensagem_erro('Não foi possível excluir o motorista.');
    }

    $query->close();
    voltar('usuarios.php');
}

$filtro_perfil = $_GET['perfil'] ?? '';
$busca = trim($_GET['busca'] ?? '');

$sql_lista =
    "SELECT u.*, f.nome AS filial,
            m.cnh_numero, m.cnh_validade
     FROM usuarios u
     LEFT JOIN filiais f ON f.id = u.filial_id
     LEFT JOIN motoristas m ON m.usuario_id = u.id
     WHERE 1 = 1";

$tipos = '';
$parametros = [];

if (in_array($filtro_perfil, ['admin', 'gerente', 'motorista'], true)) {
    $sql_lista .= " AND u.perfil = ?";
    $tipos .= 's';
    $parametros[] = $filtro_perfil;
}

if ($busca !== '') {
    $sql_lista .= " AND (u.nome LIKE ? OR u.email LIKE ? OR u.cpf LIKE ?)";
    $termo = '%' . $busca . '%';
    $tipos .= 'sss';
    $parametros[] = $termo;
    $parametros[] = $termo;
    $parametros[] = $termo;
}

$sql_lista .=
    " ORDER BY
        CASE u.perfil
            WHEN 'admin' THEN 1
            WHEN 'gerente' THEN 2
            ELSE 3
        END,
        u.nome";

$query = $sql->prepare($sql_lista);

if ($tipos !== '') {
    $query->bind_param($tipos, ...$parametros);
}

$query->execute();
$usuarios = $query->get_result();

include '../cabecalho.php';
?>

<div class="dashboard-intro painel shadow-sm">
    <div>
        <span class="etiqueta">Nível 3 - Administrador</span>
        <h3>Gestão de usuários</h3>
    </div>

    <a href="usuario-form.php" class="botao botao-principal shadow-sm">
        <i class="fa-solid fa-user-plus"></i>
        Cadastrar usuário
    </a>
</div>

<div class="painel shadow-sm">
    <form class="filtros" method="GET">
        <div class="input-box filtro-busca">
            <label for="busca">Buscar</label>
            <input
                type="text"
                id="busca"
                name="busca"
                placeholder="Nome, CPF ou e-mail"
                value="<?php echo escapar($busca); ?>"
            >
        </div>

        <div class="input-box">
            <label for="perfil">Perfil</label>
            <select id="perfil" name="perfil">
                <option value="">Todos</option>
                <option value="admin" <?php echo $filtro_perfil === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                <option value="gerente" <?php echo $filtro_perfil === 'gerente' ? 'selected' : ''; ?>>Gerente</option>
                <option value="motorista" <?php echo $filtro_perfil === 'motorista' ? 'selected' : ''; ?>>Motorista</option>
            </select>
        </div>

        <div class="filtro-acoes">
            <button class="botao botao-principal" type="submit">
                <i class="fa-solid fa-magnifying-glass"></i>
                Filtrar
            </button>

            <a href="usuarios.php" class="botao botao-secundario">Limpar</a>
        </div>
    </form>
</div>

<div class="painel shadow-sm">
    <div class="painel-titulo">
        <div>
            <span class="mini-etiqueta">Hierarquia</span>
            <h3>Usuários cadastrados</h3>
        </div>

    </div>

    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Filial</th>
                    <th>CNH</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="usuario-tabela">
                                <span class="foto-lista">
                                    <?php if (!empty($usuario['foto'])): ?>
                                        <img src="../uploads/usuarios/<?php echo escapar($usuario['foto']); ?>" alt="Foto de <?php echo escapar($usuario['nome']); ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-user"></i>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <strong><?php echo escapar($usuario['nome']); ?></strong>
                                    <span class="tabela-subtexto"><?php echo escapar($usuario['telefone'] ?: 'Sem telefone'); ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?php echo escapar($usuario['cpf']); ?></td>
                        <td><?php echo escapar($usuario['email']); ?></td>
                        <td>
                            <span class="status <?php echo $usuario['perfil'] === 'admin' ? 'andamento' : ($usuario['perfil'] === 'gerente' ? 'reservada' : 'disponivel'); ?>">
                                <?php echo escapar(perfil_nome($usuario['perfil'])); ?>
                            </span>
                        </td>
                        <td><?php echo escapar($usuario['filial'] ?? '-'); ?></td>
                        <td>
                            <?php if ($usuario['perfil'] === 'motorista'): ?>
                                <?php if (!empty($usuario['cnh_numero'])): ?>
                                    <span class="status <?php echo cnh_valida($usuario['cnh_validade']) ? 'disponivel' : 'cancelada'; ?>">
                                        <?php echo cnh_valida($usuario['cnh_validade']) ? 'Cadastrada' : 'Vencida'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status cancelada">Pendente</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="texto-ajuda">Não se aplica</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status <?php echo $usuario['ativo'] ? 'disponivel' : 'cancelada'; ?>">
                                <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="acoes-tabela">
                                <a class="botao botao-secundario shadow-sm" href="usuario-form.php?id=<?php echo $usuario['id']; ?>">
                                    <i class="fa-solid fa-pen"></i>
                                    Editar
                                </a>

                                <?php if ((int) $usuario['id'] !== (int) $_SESSION['usuario']['id']): ?>
                                    <form method="POST" class="form-acao-inline">
                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                        <input type="hidden" name="novo_status" value="<?php echo $usuario['ativo'] ? 0 : 1; ?>">

                                        <button class="botao botao-secundario" type="submit" name="alterar_status">
                                            <?php echo $usuario['ativo'] ? 'Inativar' : 'Ativar'; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($usuario['perfil'] === 'motorista'): ?>
                                    <form method="POST" class="form-acao-inline">
                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">

                                        <button
                                            class="botao botao-perigo"
                                            type="submit"
                                            name="excluir_motorista"
                                            onclick="return confirm('Excluir definitivamente este motorista?');"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                            Excluir
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
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
