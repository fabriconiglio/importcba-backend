<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableCsrfForAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin/*')) {
            $request->setLaravelSession(app('session.store'));
        }
        
        return $next($request);
    }
} 