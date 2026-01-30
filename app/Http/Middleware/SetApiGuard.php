<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiGuard
{
    /**
     * Handle an incoming request and set the appropriate guard based on subdomain
     */
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        $host = $request->getHost();
        
        // Determine guard based on subdomain
        if (str_starts_with($host, 'admin.')) {
            config(['auth.defaults.guard' => 'admin']);
        } elseif (str_starts_with($host, 'seller.')) {
            config(['auth.defaults.guard' => 'seller']);
        } else {
            config(['auth.defaults.guard' => 'buyer']);
        }

        return $next($request);
    }
}
