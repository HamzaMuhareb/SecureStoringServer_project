<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use phpseclib3\Crypt\RSA;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class DocumentController extends Controller
{
    // List all documents for the authenticated user
    public function index()
    {
        $documents = Document::where('user_id', Auth::id())->get();

        return response()->json($documents, 200);
    }

    // Upload a new document securely with virus scanning
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|max:2048|mimes:pdf,jpg,jpeg,png,doc,docx',
            'document_type' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('document');
        $filePath = $file->getRealPath();

        // Scan the file for viruses before storing
        if (!$this->scanForMalware($filePath)) {
            return response()->json(['message' => 'The document contains malicious content and cannot be uploaded.'], 422);
        }

        // Sanitize file name and store the document securely
        $originalName = $file->getClientOriginalName();
        $safeFileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $extension = $file->getClientOriginalExtension();
        $path = $file->storeAs('documents', "$safeFileName.$extension");

        // Sign the document after uploading
        $this->signDocument(storage_path("app/$path"));

        $document = Document::create([
            'user_id' => Auth::id(),
            'document_name' => $originalName,
            'document_path' => $path,
            'document_type' => $request->document_type,
            'signature' => Hash::make($path),
        ]);

        return response()->json(['message' => 'Document uploaded successfully', 'document' => $document], 201);
    }

    // Download a document securely
    public function download($id)
    {
        $document = Document::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        // Verify document signature before downloading
        $filePath = storage_path("app/{$document->document_path}");
        if (!$this->verifySignature($filePath)) {
            return response()->json(['message' => 'Document signature verification failed'], 403);
        }

        return Storage::download($document->document_path);
    }

    // Delete a document securely
    public function destroy($id)
    {
        $document = Document::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $filePath = storage_path("app/{$document->document_path}");

        // Verify document signature before deletion
        if (!$this->verifySignature($filePath)) {
            return response()->json(['message' => 'Document signature verification failed'], 403);
        }

        Storage::delete($document->document_path);
        Storage::delete("{$document->document_path}.sig");
        $document->delete();

        return response()->json(['message' => 'Document deleted successfully'], 200);
    }

    private function signDocument($filePath)
    {
        $privateKey = file_get_contents(storage_path('ca/server.key'));
        $rsa = RSA::loadPrivateKey($privateKey);

        $documentData = file_get_contents($filePath);
        $signature = $rsa->sign($documentData);

        // Save signature with the document
        file_put_contents("$filePath.sig", $signature);
    }

    private function verifySignature($filePath)
    {
        $publicKey = file_get_contents(storage_path('ca/server.crt'));
        $rsa = RSA::loadPublicKey($publicKey);

        $documentData = file_get_contents($filePath);
        $signaturePath = "$filePath.sig";

        if (!file_exists($signaturePath)) {
            return false;
        }

        $signature = file_get_contents($signaturePath);
        return $rsa->verify($documentData, $signature);
    }

    private function scanForMalware($filePath)
    {
        $defenderPath = "C:\\Program Files\\Windows Defender\\MpCmdRun.exe";
        $process = new Process([$defenderPath, '-Scan', '-ScanType', '3', '-File', $filePath]);
        $process->run();

        // Check if scan was successful and threats were not found
        return $process->isSuccessful() && strpos($process->getOutput(), 'No threats detected') !== false;
    }



}
