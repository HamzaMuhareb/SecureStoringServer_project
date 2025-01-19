<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use App\Models\AuditLog;


// Public Routes

Route::get('/test',function () {return response()->json(['success' => 'yaaay'], 200);});
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('role:admin')->post('/register/institution', [AuthController::class, 'registerInstitution']);
Route::post('/login', [AuthController::class, 'login']);
// Protected Routes for Authenticated Users
Route::middleware(['auth:api', 'role:individual,institution'])->group(function () {

    // User Routes
    Route::post('/logout', [AuthController::class, 'logout']);

    // Document Management
    Route::middleware('role:individual')->group(function () {
        Route::get('/documents', [DocumentController::class, 'index']);
        Route::post('/documents/upload', [DocumentController::class, 'store']);
        Route::get('/documents/download/{id}', [DocumentController::class, 'download']);
        Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);
    });

    // Institution Routes
    Route::middleware('role:institution')->post('/institutions/search', [InstitutionController::class, 'searchDocumentsByNationalId']);
});
Route::get('/test-scan', function () {
    $defenderPath = "C:\\Program Files\\Windows Defender\\MpCmdRun.exe";
    $fileToScan = "C:\\path\\to\\test\\file.txt";  // Update with a test file path

    $process = new Symfony\Component\Process\Process([$defenderPath, '-Scan', '-ScanType', '3', '-File', $fileToScan]);
    $process->run();

    return $process->getOutput();
});

Route::get('/get-server-cert', function () {
    return response()->file(storage_path('ca/server.crt'), [
        'Content-Type' => 'application/x-pem-file'
    ]);
});

Route::middleware('role:institution')->get('/audit-logs', function () {
    return response()->json(AuditLog::latest()->get());
});

