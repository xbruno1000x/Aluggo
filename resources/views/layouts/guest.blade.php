{{-- resources/views/layouts/guest.blade.php --}}
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aluggo')</title>
    @vite(['resources/scss/app.scss', 'resources/ts/app.ts'])
    {{-- Scripts adicionais --}}
    @stack('scripts-body')
</head>
<body class="bg-dark d-flex justify-content-center align-items-center vh-100">

    {{-- Conteúdo da página --}}
    <main class="w-100" style="max-width: 400px;">
        @yield('content')
    </main>
</body>
</html>