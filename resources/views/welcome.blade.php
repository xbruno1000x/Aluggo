<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aluggo - Gestão Inteligente de Imóveis</title>
    @vite(['resources/scss/app.scss', 'resources/ts/app.ts'])
    <style>
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(rgba(22, 30, 47, 0.85), rgba(22, 30, 47, 0.9)), url('/images/background_city_night_degrade.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .logo-hero {
            max-width: 250px;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.5));
            animation: fadeInDown 1s ease-out;
        }
        
        .hero-title {
            animation: fadeInUp 1s ease-out 0.2s both;
        }
        
        .hero-subtitle {
            animation: fadeInUp 1s ease-out 0.4s both;
        }
        
        .hero-btn {
            animation: fadeInUp 1s ease-out 0.6s both;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .feature-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(181, 26, 43, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .feature-card:hover::before {
            left: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 10px 30px rgba(181, 26, 43, 0.4) !important;
        }
        
        .feature-icon {
            transition: transform 0.3s ease;
            display: inline-block;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.2) rotate(5deg);
        }
        
        .reason-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-left-width: 4px !important;
            background: linear-gradient(135deg, rgba(36, 47, 73, 0.95) 0%, rgba(36, 47, 73, 0.8) 100%) !important;
            position: relative;
            overflow: hidden;
        }
        
        .reason-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 165, 134, 0.1), transparent);
            transition: left 0.6s;
        }
        
        .reason-card:hover::before {
            left: 100%;
        }
        
        .reason-card:hover {
            transform: translateY(-5px) scale(1.02);
            background: linear-gradient(135deg, rgba(36, 47, 73, 1) 0%, rgba(181, 26, 43, 0.15) 100%) !important;
            border-left-width: 6px !important;
            box-shadow: 0 8px 25px rgba(181, 26, 43, 0.3) !important;
        }
        
        .features-section {
            background: linear-gradient(180deg, rgba(36, 47, 73, 1) 0%, rgba(22, 30, 47, 0.95) 100%);
        }
        
        .reasons-section {
            background: linear-gradient(180deg, rgba(22, 30, 47, 0.95) 0%, rgba(36, 47, 73, 1) 100%);
        }
        
        .btn-hover-effect {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-hover-effect::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-hover-effect:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-hover-effect:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 165, 134, 0.5) !important;
        }
        
        .section-title {
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #B51A2B, transparent);
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem !important;
            }
            
            .hero-subtitle {
                font-size: 1.2rem !important;
            }
            
            .logo-hero {
                max-width: 180px;
            }
        }
    </style>
</head>
<body class="bg-primary">
    
    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center text-light">
                    <img src="{{ asset('images/aluggo_logo.png') }}" alt="Aluggo Logo" class="logo-hero mb-4">
                    <h1 class="display-3 fw-bold mb-4 hero-title">Bem-vindo ao Aluggo</h1>
                    <p class="lead fs-4 mb-5 hero-subtitle">A solução completa para gestão inteligente de imóveis e aluguéis</p>
                    <div class="hero-btn">
                        <a href="{{ route('admin.login') }}" class="btn btn-warning btn-lg px-5 py-3 rounded-pill shadow btn-hover-effect">
                            <span style="position: relative; z-index: 1;">Acessar Plataforma</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-warning section-title">Principais Funcionalidades</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card bg-primary border-warning h-100 shadow-sm feature-card">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary mb-3 feature-icon">🏢</div>
                            <h3 class="h5 text-warning mb-3 fw-bold">Gestão de Propriedades</h3>
                            <p class="text-light mb-0">Cadastre e organize todas as suas propriedades e imóveis em um único lugar. Controle completo sobre cada unidade.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card bg-primary border-warning h-100 shadow-sm feature-card">
                        <div class="card-body text-center">
                            <div class="display-4 text-danger mb-3 feature-icon">📝</div>
                            <h3 class="h5 text-warning mb-3 fw-bold">Contratos de Aluguel</h3>
                            <p class="text-light mb-0">Gerencie contratos, locatários e valores mensais. Acompanhe datas de início, fim e renovações automaticamente.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card bg-primary border-warning h-100 shadow-sm feature-card">
                        <div class="card-body text-center">
                            <div class="display-4 text-danger mb-3 feature-icon">💰</div>
                            <h3 class="h5 text-warning mb-3 fw-bold">Confirmação de Pagamentos</h3>
                            <p class="text-light mb-0">Marque pagamentos recebidos, visualize atrasos e gere relatórios financeiros detalhados mês a mês.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card bg-primary border-warning h-100 shadow-sm feature-card">
                        <div class="card-body text-center">
                            <div class="display-4 text-danger mb-3 feature-icon">🏗️</div>
                            <h3 class="h5 text-warning mb-3 fw-bold">Controle de Obras</h3>
                            <p class="text-light mb-0">Registre obras, manutenções e melhorias em seus imóveis. Acompanhe custos e histórico completo.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card bg-primary border-warning h-100 shadow-sm feature-card">
                        <div class="card-body text-center">
                            <div class="display-4 text-danger mb-3 feature-icon">📊</div>
                            <h3 class="h5 text-warning mb-3 fw-bold">Relatórios Financeiros</h3>
                            <p class="text-light mb-0">Análises detalhadas de receitas, despesas, taxas e rentabilidade. Tome decisões baseadas em dados reais.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4">
                    <div class="card bg-primary border-warning h-100 shadow-sm feature-card">
                        <div class="card-body text-center">
                            <div class="display-4 text-danger mb-3 feature-icon">🔒</div>
                            <h3 class="h5 text-warning mb-3 fw-bold">Segurança e 2FA</h3>
                            <p class="text-light mb-0">Autenticação de dois fatores, recuperação de senha e proteção total dos seus dados sensíveis.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reasons Section -->
    <section class="reasons-section py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold text-warning section-title">Por que escolher o Aluggo?</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card bg-secondary border-warning border-start border-3 h-100 shadow-sm reason-card">
                        <div class="card-body">
                            <h4 class="h5 text-warning mb-2 fw-bold">✨ Interface Intuitiva</h4>
                            <p class="text-light mb-0">Design moderno e fácil de usar. Acesse de qualquer dispositivo e gerencie seus imóveis com poucos cliques.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card bg-secondary border-warning border-start border-3 h-100 shadow-sm reason-card">
                        <div class="card-body">
                            <h4 class="h5 text-warning mb-2 fw-bold">⚡ Automatização Inteligente</h4>
                            <p class="text-light mb-0">Cálculos automáticos de valores proporcionais, alertas de atrasos e geração de relatórios sem esforço manual.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card bg-secondary border-warning border-start border-3 h-100 shadow-sm reason-card">
                        <div class="card-body">
                            <h4 class="h5 text-warning mb-2 fw-bold">📈 Visão Completa do Negócio</h4>
                            <p class="text-light mb-0">Dashboards e relatórios que mostram a saúde financeira do seu portfólio imobiliário em tempo real.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card bg-secondary border-warning border-start border-3 h-100 shadow-sm reason-card">
                        <div class="card-body">
                            <h4 class="h5 text-warning mb-2 fw-bold">🎯 Controle Total</h4>
                            <p class="text-light mb-0">Gerencie múltiplas propriedades, locatários, contratos e pagamentos de forma centralizada e organizada.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card bg-secondary border-warning border-start border-3 h-100 shadow-sm reason-card">
                        <div class="card-body">
                            <h4 class="h5 text-warning mb-2 fw-bold">💼 Profissionalismo</h4>
                            <p class="text-light mb-0">Ferramentas profissionais para proprietários que levam a gestão imobiliária a sério e buscam resultados.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card bg-secondary border-warning border-start border-3 h-100 shadow-sm reason-card">
                        <div class="card-body">
                            <h4 class="h5 text-warning mb-2 fw-bold">🔐 Dados Protegidos</h4>
                            <p class="text-light mb-0">Suas informações financeiras e contratuais protegidas com criptografia e autenticação de dois fatores.</p>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="{{ route('admin.login') }}" class="btn btn-warning btn-lg px-5 py-3 rounded-pill shadow btn-hover-effect mb-3">
                    <span style="position: relative; z-index: 1;">Começar Agora</span>
                </a>
                <p class="mt-4 text-light fs-5">
                    Não tem uma conta? 
                    <a href="{{ route('admin.register') }}" class="text-warning text-decoration-none fw-bold" style="transition: opacity 0.3s;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                        Cadastre-se gratuitamente
                    </a>
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-center py-4 border-top border-warning">
        <div class="container">
            <p class="mb-0 text-light opacity-75">
                &copy; {{ date('Y') }} Aluggo. Gestão Inteligente de Imóveis.
            </p>
        </div>
    </footer>

</body>
</html>
