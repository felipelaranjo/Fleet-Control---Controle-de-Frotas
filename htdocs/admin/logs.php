<?php
include "../conexao.php";
include "../funcoes.php";
exigir_perfil("admin");
$titulo = "Logs de Auditoria";
$prefixo = "../";
include "../cabecalho.php";
?>

<?php $logs=$sql->query("SELECT l.*,u.nome FROM logs_auditoria l LEFT JOIN usuarios u ON u.id=l.usuario_id ORDER BY l.id DESC"); ?>
<div class="painel">
<div class="tabela-wrap">
<table>
<tr>
<th>Data</th>
<th>Usuário</th>
<th>Ação</th>
<th>Entidade</th>
<th>ID</th>
<th>Detalhes</th>
<th>IP</th>
</tr>
<?php while($l=$logs->fetch_assoc()): ?>
<tr>
<td>
<?php echo date('d/m/Y H:i',strtotime($l['registrado_em'])); ?>
</td>
<td>
<?php echo escapar($l['nome']??'Sistema'); ?>
</td>
<td>
<?php echo escapar($l['acao']); ?>
</td>
<td>
<?php echo escapar($l['entidade']); ?>
</td>
<td>
<?php echo escapar($l['entidade_id']??'-'); ?>
</td>
<td>
<?php echo escapar($l['detalhes']); ?>
</td>
<td>
<?php echo escapar($l['ip_origem']); ?>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>
<?php include "../rodape.php"; ?>
