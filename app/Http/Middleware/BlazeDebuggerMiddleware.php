<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Livewire\Blaze\DebuggerMiddleware;
use Symfony\Component\HttpFoundation\Response;

class BlazeDebuggerMiddleware extends DebuggerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = '/'.ltrim($request->path(), '/');

        if (str_contains($path, 'livewire') && (str_contains($path, 'upload-file') || str_contains($path, 'update') || str_contains($path, 'preview-file'))) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
