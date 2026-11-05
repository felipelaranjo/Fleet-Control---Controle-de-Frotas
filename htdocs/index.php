<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
    >
    <link rel="stylesheet" href="css/style.css?v=final-20260925">

    <title>FleetControl - Controle de Frotas</title>
</head>
<body class="pagina-inicial">

<nav class="site-nav">
    <div class="site-nav-conteudo">
        <a href="index.php" class="marca-site">
            <span class="marca-simbolo">
                <i class="fa-solid fa-truck-fast"></i>
                <span class="marca-linha"></span>
            </span>
            <span class="marca-texto">Fleet<span>Control</span></span>
        </a>

        <a href="login.php" class="botao botao-principal shadow-sm">
            <i class="fa-solid fa-right-to-bracket"></i>
            Entrar no sistema
        </a>
    </div>
</nav>

<main>
    <section class="hero">
        <div class="hero-conteudo">
            <div class="hero-texto">
                <span class="etiqueta">Gestão de frotas corporativas</span>
                <h1>Controle sua frota com mais <span>organização e segurança.</span></h1>
                <p>
                    O FleetControl reúne veículos, viagens, manutenção, despesas e motoristas
                    em um único sistema, deixando cada informação no lugar certo para cada
                    perfil de usuário.
                </p>

                <div class="hero-botoes">
                    <a href="#recursos" class="botao botao-secundario botao-grande">
                        Conhecer recursos
                    </a>
                </div>

                <div class="hero-destaques">
                    <div>
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Controle por perfil</span>
                    </div>
                    <div>
                        <i class="fa-solid fa-car"></i>
                        <span>Gestão da frota</span>
                    </div>
                    <div>
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Indicadores</span>
                    </div>
                </div>
            </div>

            <div class="hero-painel shadow">
                <div class="hero-painel-topo">
                    <div>
                        <span class="mini-etiqueta">Visão geral</span>
                        <h2>FleetControl</h2>
                    </div>
                    <span class="status status-disponivel">Sistema organizado</span>
                </div>

                <div class="hero-indicadores">
                    <div class="hero-indicador shadow-sm">
                        <span class="hero-icone azul">
                            <i class="fa-solid fa-car"></i>
                        </span>
                        <div>
                            <strong>Frota</strong>
                            <small>Veículos monitorados</small>
                        </div>
                    </div>

                    <div class="hero-indicador shadow-sm">
                        <span class="hero-icone verde">
                            <i class="fa-solid fa-route"></i>
                        </span>
                        <div>
                            <strong>Viagens</strong>
                            <small>Rotas e operações</small>
                        </div>
                    </div>

                    <div class="hero-indicador shadow-sm">
                        <span class="hero-icone amarelo">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </span>
                        <div>
                            <strong>Manutenção</strong>
                            <small>Alertas preventivos</small>
                        </div>
                    </div>
                </div>

                <div class="hero-linha">
                    <span>Operação organizada por perfil</span>
                    <strong>3 níveis</strong>
                </div>
                <div class="barra-progresso">
                    <span></span>
                </div>
            </div>
        </div>
    </section>

    <section class="recursos" id="recursos">
        <div class="secao-titulo">
            <span class="etiqueta">Principais recursos</span>
            <h2>Um sistema para cada etapa da operação.</h2>
            <p>
                Cada perfil encontra apenas as informações e funções necessárias para sua rotina.
            </p>
        </div>

        <div class="recursos-grid">
            <div class="recurso-card shadow-sm">
                <span class="recurso-icone">
                    <i class="fa-solid fa-user-shield"></i>
                </span>
                <h3>Administrador</h3>
                <p>Usuários, filiais, auditoria e configurações globais.</p>
            </div>

            <div class="recurso-card shadow-sm">
                <span class="recurso-icone">
                    <i class="fa-solid fa-chart-pie"></i>
                </span>
                <h3>Gerente de Frota</h3>
                <p>Veículos, manutenção, viagens, despesas e indicadores.</p>
            </div>

            <div class="recurso-card shadow-sm">
                <span class="recurso-icone">
                    <i class="fa-solid fa-id-card"></i>
                </span>
                <h3>Motorista</h3>
                <p>Viagens, check-in, checklist, despesas e check-out.</p>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <p>FleetControl &copy; 2026 - Sistema Web de Controle e Gestão de Frotas</p>
</footer>

</body>
</html>
