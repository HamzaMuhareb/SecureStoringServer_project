<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCertificate
{
    public function handle(Request $request, Closure $next)
    {
        // Enforce HTTPS
        if (!$request->secure()) {
            return response()->json(['error' => 'Secure connection required'], Response::HTTP_FORBIDDEN);
        }

        // Validate client certificate (optional)
        $clientCert = $request->server('SSL_CLIENT_CERT') ?? null;
        if ($clientCert && !openssl_x509_checkpurpose($clientCert, X509_PURPOSE_SSL_CLIENT)) {
            return response()->json(['error' => 'Invalid client certificate'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
