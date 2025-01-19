<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class CertificateController extends Controller
{
    private $caServerUrl;

    public function __construct()
    {
        $this->caServerUrl = env('CA_SERVER_URL');
    }

    public function generateCSR()
    {
        $privateKeyPath = storage_path('app/ca/server.key');
        $csrPath = storage_path('app/ca/server.csr');

        // Ensure private key exists
        if (!file_exists($privateKeyPath)) {
            return response()->json(['error' => 'Private key not found. Please generate it manually.'], 400);
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        if (!$privateKey) {
            return response()->json(['error' => 'Invalid private key.'], 500);
        }

        // Generate CSR
        $csrConfig = [
            "commonName" => "my-secure-server.com",
            "countryName" => "SY",
            "organizationName" => "My Security Project"
        ];

        $csr = openssl_csr_new($csrConfig, $privateKey);
        openssl_csr_export($csr, $csrOut);
        Storage::put('ca/server.csr', $csrOut);

        return response()->json(['message' => 'CSR generated successfully'], 201);
    }
    public function requestCertificate()
{
    $csrPath = storage_path('app/ca/server.csr');

    // Ensure CSR exists
    if (!file_exists($csrPath)) {
        return response()->json(['error' => 'CSR file not found. Generate it first.'], 400);
    }

    // Send CSR to Flask CA for signing
    $response = Http::attach(
        'csr', file_get_contents($csrPath), 'server.csr'
    )->post($this->caServerUrl . '/sign');

    // Debug the response from Flask CA
    Log::info('CA Response:', ['status' => $response->status(), 'body' => $response->body()]);

    if ($response->successful()) {
        Storage::put('ca/server.crt', $response->body());

        // Verify if the file was actually saved
        if (!Storage::exists('ca/server.crt')) {
            return response()->json(['error' => 'Certificate was not saved correctly'], 500);
        }

        return response()->json(['message' => 'Certificate received and stored'], 201);
    }

    return response()->json(['error' => 'Failed to get certificate from CA', 'details' => $response->body()], 500);
}

}
