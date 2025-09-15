<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Verifica se o usuário está autenticado como 'proprietario'
        if (Auth::guard('proprietario')->check()) {
            // Se o usuário estiver autenticado, continuar com a requisição
            return $next($request);
        }

        // Caso contrário, redireciona para a página de login
        return redirect()->route('admin.login');
    }
}