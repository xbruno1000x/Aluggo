{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Meu App')</title>
    @vite(['resources/scss/app.scss', 'resources/ts/app.ts'])
    {{-- Scripts adicionais --}}
    @stack('scripts-body')
</head>
<body class="bg-dark text-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('admin.menu') }}">
            <img src="{{ asset('images/aluggo_logo.png') }}" alt="Aluggo" style="height:55px;" />
            <span class="visually-hidden">Aluggo</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" id="patrimonioDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Gestão de Patrimônio
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="patrimonioDropdown">
                        <li><a class="dropdown-item" href="{{ route('imoveis.index') }}">Gestão de Imóveis</a></li>
                        <li><a class="dropdown-item" href="{{ route('propriedades.index') }}">Gestão de Propriedades</a></li>
                        <li><a class="dropdown-item" href="#">Gestão de Obras e Manutenções</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" id="financeiroDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Controle Financeiro
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="financeiroDropdown">
                        <li><a class="dropdown-item" href="{{ route('alugueis.index') }}">Gestão de Aluguéis</a></li>
                        <li><a class="dropdown-item" href="#">Cadastro de Transações de Compra e Venda</a></li>
                        <li><a class="dropdown-item" href="#">Relatórios e Rentabilidade</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" id="locatariosDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Alugueis
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="locatariosDropdown">
                        <li><a class="dropdown-item" href="{{ route('locatarios.index') }}">Meus Inquilinos</a></li>
                        <li><a class="dropdown-item" href="{{ route('alugueis.create') }}">Cadastro de Contratos de Aluguel</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" id="configDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Configurações
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="configDropdown">
                        <li><a class="dropdown-item" href="{{ route('account.settings') }}">Configurações da Conta</a></li>
                    </ul>
                </li>

            </ul>
            
            <form method="POST" action="{{ route('admin.logout') }}" class="d-flex ms-auto">
                @csrf
                <button type="submit" class="btn btn-danger">Sair</button>
            </form>
        </div>
    </div>
</nav>

<main class="container-fluid px-4 py-4 bg-dark text-light">
    @yield('content')
</main>

</body>
</html>