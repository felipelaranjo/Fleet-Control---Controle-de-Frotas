INFINITYFREE - FLEETCONTROL - VERSÃO FINAL

INSTALAÇÃO NOVA
1. Crie o banco em MySQL Databases no painel do InfinityFree.
2. Abra o phpMyAdmin pelo botão do próprio banco.
3. Selecione o banco criado.
4. Importe: controle_frotas_infinityfree.sql
5. No conexao.php, use exatamente o MySQL Hostname, usuário, senha e nome do banco fornecidos pelo InfinityFree.

BANCO QUE JÁ ESTAVA FUNCIONANDO ANTES DESTA VERSÃO
1. Não importe o banco inteiro novamente.
2. Selecione o seu banco atual no phpMyAdmin.
3. Importe ou execute apenas: atualizacao_final.sql
4. Esse arquivo adiciona os campos de imagem de usuários e filiais.

UPLOADS
Confirme que existem dentro de htdocs:
- uploads/usuarios/
- uploads/filiais/
- uploads/comprovantes/
- uploads/checklists/

O sistema não tenta criar essas pastas fora de htdocs.

USUÁRIOS DE DEMONSTRAÇÃO EM UMA INSTALAÇÃO NOVA
admin@frotas.com / 123456
gerente.sp@frotas.com / 123456
gerente.cp@frotas.com / 123456
motorista1@frotas.com / 123456
motorista2@frotas.com / 123456
motorista3@frotas.com / 123456
