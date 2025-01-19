<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCertificate
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->secure()) {
            return response()->json(['error' => 'Secure connection required'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
