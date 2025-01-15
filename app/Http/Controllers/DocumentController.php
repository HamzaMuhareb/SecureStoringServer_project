<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Document;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


class DocumentController extends Controller
{
    // List all documents for the authenticated user
    public function index()
    {
        $documents = Document::where('user_id', Auth::id())->get();

        return response()->json($documents, 200);
    }

    // Upload a new document
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|max:2048|mimes:pdf,jpg,jpeg,png,doc,docx',
            'document_type' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $request->file('document')->store('documents');

        $document = Document::create([
            'user_id' => Auth::id(),
            'document_name' => $request->file('document')->getClientOriginalName(),
            'document_path' => $path,
            'document_type' => $request->document_type,
            'signature' => Hash::make($path),
        ]);

        return response()->json(['message' => 'Document uploaded successfully', 'document' => $document], 201);
    }

    // Download a document
    public function download($id)
    {
        $document = Document::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        return Storage::download($document->document_path);
    }

    // Delete a document
    public function destroy($id)
    {
        $document = Document::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        Storage::delete($document->document_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted successfully'], 200);
    }
}

