# FleetControl - Controle de Frotas

Projeto web escolar desenvolvido com PHP, MySQL, HTML, CSS, Bootstrap e JavaScript.

Esta é a versão final refinada do projeto. A estrutura continua simples e compatível com o conteúdo de um curso técnico: PHP procedural, MySQLi, `prepare()`, `bind_param()`, HTML explícito e CSS próprio.

## O que foi refinado

### Visual e CSS
- `css/style.css` revisado e consolidado, sem depender de correções soltas no fim do arquivo.
- Layout responsivo para desktop, tablet e celular.
- Menu lateral, cabeçalhos, cards, tabelas, filtros, perfil, dashboards e estados vazios padronizados.
- Formulários com melhor espaçamento, foco, campos desabilitados, área de upload, ajuda e botões.
- Sombras discretas com Bootstrap (`shadow-sm`) e CSS próprio.
- Correção dos cards de 3 colunas da tela de perfil.
- Identidade visual FleetControl mantida em todas as áreas.
- CSS e JavaScript recebem versão na URL para evitar que o InfinityFree/navegador mantenha arquivos antigos em cache.

### Cadastros e edições
- Campos organizados por seções para facilitar o preenchimento.
- `maxlength`, `min`, `max`, `required`, placeholders e tipos de input revisados.
- Máscaras de CPF, CNPJ, telefone, CNH, placa, RENAVAM, chassi e ano usando o `js/script.js` que já existia no projeto.
- Validações também no PHP, sem depender apenas do JavaScript.
- Senha com confirmação e botão mostrar/ocultar.
- CNH obrigatória para todo motorista.
- Limites de quilometragem e valores para evitar números absurdos.

### Imagens
- Usuários podem possuir foto de perfil.
- Filiais podem possuir imagem da unidade.
- Upload aceita JPG, PNG e WEBP com até 2 MB.
- A imagem é validada no PHP antes de ser salva.
- As imagens são gravadas em `uploads/usuarios/` e `uploads/filiais/`; o banco guarda apenas o nome do arquivo.
- O menu, o cabeçalho, o perfil e as listagens mostram a foto quando houver e usam um ícone padrão quando não houver.

### Hierarquia
- Administrador: visão global, usuários, filiais, frota, auditoria e configurações.
- Gerente: operação da própria filial, frota, motoristas, viagens, manutenção, despesas e relatórios.
- Motorista: somente suas viagens e operações relacionadas.
- Apenas o Administrador cadastra gerentes e motoristas.
- Gerentes podem editar motoristas da própria filial.
- Somente o Administrador pode excluir motorista.

### Frota e operação
- Gestão da frota com status, filtros e indicadores.
- Cadastro e edição de veículos com placa, chassi, RENAVAM, ano, CNH exigida e odômetro.
- Validação de motorista/CNH ao agendar viagem.
- Check-in, checklist obrigatório, despesas e check-out.
- Manutenção com alerta de proximidade por quilometragem.

## Banco de dados

### Instalação nova no Laragon
Importe:

`database/controle_frotas.sql`

A conexão padrão em `conexao.php` é:

- servidor: `localhost`
- usuário: `root`
- senha: vazia
- banco: `controle_frotas`

### Instalação nova no InfinityFree
1. Crie o banco pelo painel do InfinityFree.
2. Abra o phpMyAdmin pelo próprio painel.
3. Selecione o banco criado.
4. Importe:

`database/controle_frotas_infinityfree.sql`

5. Troque no `conexao.php` os quatro dados da conexão pelos dados mostrados em **MySQL Databases**.

### Se o seu banco já estava funcionando antes desta versão
Não importe tudo novamente.

Execute apenas uma vez:

`database/atualizacao_final.sql`

Esse arquivo adiciona:
- `usuarios.foto`
- `filiais.imagem`

Depois envie também estas pastas para dentro de `htdocs/uploads/`:
- `usuarios/`
- `filiais/`

## Usuários de demonstração

Em uma instalação nova pelos SQLs desta versão, todos utilizam a senha:

`123456`

Administrador:
- `admin@frotas.com`

Gerentes:
- `gerente.sp@frotas.com`
- `gerente.cp@frotas.com`

Motoristas:
- `motorista1@frotas.com`
- `motorista2@frotas.com`
- `motorista3@frotas.com`

As senhas são armazenadas no banco em formato de hash e o login utiliza `password_verify()`.

## Estrutura principal

- `admin/` - administração, usuários, filiais, auditoria e configurações.
- `gerente/` - frota, motoristas, viagens, manutenção, despesas e relatórios.
- `motorista/` - operação das viagens.
- `css/style.css` - identidade visual completa.
- `js/script.js` - menu, máscaras e interações simples.
- `uploads/usuarios/` - fotos de usuários.
- `uploads/filiais/` - imagens das filiais.
- `uploads/comprovantes/` - comprovantes de despesas.
- `uploads/checklists/` - fotos dos checklists.
- `database/` - SQLs do Laragon, InfinityFree e atualização do banco existente.

## Observação para o InfinityFree

Todos os arquivos do site devem ficar dentro de `htdocs`. As pastas de upload também devem existir dentro de `htdocs/uploads`.

Depois de substituir `style.css` ou `script.js`, a versão final já usa um identificador na URL dos arquivos para reduzir problemas de cache. Mesmo assim, se necessário, atualize a página com `Ctrl + F5`.

## Recuperação de senha

A tela `recuperar-senha.php` está incluída apenas como interface nesta entrega.
