<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use phpseclib3\Crypt\RSA;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use App\Models\AuditLog;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::where('user_id', Auth::id())->get();
        return response()->json($documents, 200);
    }


public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'document' => 'required|file|max:2048|mimes:pdf,jpg,jpeg,png,doc,docx',
        'document_type' => 'required|string|max:50'
    ]);

    if ($validator->fails()) {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'upload',
            'document_name' => $request->file('document')->getClientOriginalName(),
            'status' => 'failed',
            'details' => json_encode($validator->errors())
        ]);

        return response()->json(['errors' => $validator->errors()], 422);
    }

    $file = $request->file('document');
    $originalName = $file->getClientOriginalName();
    $safeFileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
    $extension = $file->getClientOriginalExtension();
    $path = $file->storeAs('documents', "$safeFileName.$extension");

    // Sign document using CA
    $this->signDocument(storage_path("app/$path"));

    Document::create([
        'user_id' => Auth::id(),
        'document_name' => $originalName,
        'document_path' => $path,
        'document_type' => $request->document_type,
    ]);

    AuditLog::create([
        'user_id' => Auth::id(),
        'action' => 'upload',
        'document_name' => $originalName,
        'status' => 'success',
        'details' => "Document uploaded successfully."
    ]);

    return response()->json(['message' => 'Document uploaded successfully'], 201);
}

public function download($id)
{
    $document = Document::where('id', $id)->where('user_id', Auth::id())->first();

    if (!$document) {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'download',
            'document_name' => 'Unknown',
            'status' => 'failed',
            'details' => 'Document not found.'
        ]);

        return response()->json(['message' => 'Document not found'], 404);
    }

    $filePath = storage_path("app/{$document->document_path}");
    if (!$this->verifySignature($filePath)) {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'download',
            'document_name' => $document->document_name,
            'status' => 'failed',
            'details' => 'Signature verification failed.'
        ]);

        return response()->json(['message' => 'Document signature verification failed'], 403);
    }

    AuditLog::create([
        'user_id' => Auth::id(),
        'action' => 'download',
        'document_name' => $document->document_name,
        'status' => 'success',
        'details' => 'Document downloaded successfully.'
    ]);

    return Storage::download($document->document_path);
}


public function destroy($id)
{
    $document = Document::where('id', $id)->where('user_id', Auth::id())->first();

    if (!$document) {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'document_name' => 'Unknown',
            'status' => 'failed',
            'details' => 'Document not found.'
        ]);

        return response()->json(['message' => 'Document not found'], 404);
    }

    $filePath = storage_path("app/{$document->document_path}");
    if (!$this->verifySignature($filePath)) {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'document_name' => $document->document_name,
            'status' => 'failed',
            'details' => 'Signature verification failed.'
        ]);

        return response()->json(['message' => 'Document signature verification failed'], 403);
    }

    Storage::delete($document->document_path);
    Storage::delete("{$document->document_path}.sig");
    $document->delete();

    AuditLog::create([
        'user_id' => Auth::id(),
        'action' => 'delete',
        'document_name' => $document->document_name,
        'status' => 'success',
        'details' => 'Document deleted successfully.'
    ]);

    return response()->json(['message' => 'Document deleted successfully'], 200);
}


    private function scanForMalware($filePath)
    {
        $defenderPath = "C:\\Program Files\\Windows Defender\\MpCmdRun.exe";
        $process = new Process([$defenderPath, '-Scan', '-ScanType', '3', '-File', $filePath]);
        $process->run();

        return $process->isSuccessful() && strpos($process->getOutput(), 'No threats detected') !== false;
    }

    private function signDocument($filePath)
    {
        $response = Http::attach('file', file_get_contents($filePath), basename($filePath))
            ->post(env('CA_SERVER_URL') . '/sign_document');

        if ($response->failed()) {
            throw new \Exception('Failed to sign document using CA');
        }

        file_put_contents("$filePath.sig", $response->body());
    }


    private function verifySignature($filePath)
    {
        $serverCert = file_get_contents(env('CA_SERVER_URL') . '/get_server_cert/laravel_server');
        $rsa = RSA::loadPublicKey($serverCert);

        $documentData = file_get_contents($filePath);
        $signaturePath = "$filePath.sig";

        if (!file_exists($signaturePath)) {
            return false;
        }

        $signature = file_get_contents($signaturePath);
        return $rsa->verify($documentData, $signature);
    }

}
