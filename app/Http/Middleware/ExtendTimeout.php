<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtendTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $seconds = 60): Response
    {
        // Aumenta o timeout do PHP temporariamente
        $originalTimeout = ini_get('max_execution_time');
        set_time_limit($seconds);

        $response = $next($request);

        // Restaura o timeout original (opcional, pois será resetado no próximo request)
        if ($originalTimeout !== false) {
            set_time_limit((int) $originalTimeout);
        }

        return $response;
    }
}
