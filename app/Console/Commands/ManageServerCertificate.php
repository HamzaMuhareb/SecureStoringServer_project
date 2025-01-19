<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ManageServerCertificate extends Command
{
    protected $signature = 'certificate:manage';
    protected $description = 'Generates CSR and requests a signed certificate from CA if needed.';

    private $caServerUrl;
    private $serverCertPath;
    private $serverKeyPath;
    private $csrPath;

    public function __construct()
    {
        parent::__construct();

        $this->caServerUrl = env('CA_SERVER_URL');
        $this->serverCertPath = storage_path('/app/ca/server.crt');
        $this->serverKeyPath = storage_path('/app/ca/server.key');
        $this->csrPath = storage_path('/app/ca/server.csr');
    }

    public function handle()
    {
        // Check if the certificate already exists
        if (file_exists($this->serverCertPath)) {
            $this->info('Server certificate already exists. Skipping certificate request.');
            return;
        }

        // Step 1: Ensure the private key exists
        if (!file_exists($this->serverKeyPath)) {
            $this->error('Private key not found. Please generate it manually.');
            return;
        }

        // Step 3: Request Signed Certificate
        $this->info('Requesting signed certificate from CA...');
        $this->requestCertificate();

        $this->info('Certificate management process completed.');
    }


    private function requestCertificate()
    {
        $response = Http::attach(
            'csr', file_get_contents($this->csrPath), 'server.csr'
            )->post(env('CA_SERVER_URL') . '/sign', [
                'server_name' => 'server'
            ]);
        if ($response->successful()) {
            Storage::put('ca/server.crt', $response->body());
            $this->info('Certificate received and stored successfully.');
        } else {
            $this->error('Failed to get certificate from CA.');
        }
    }
}
