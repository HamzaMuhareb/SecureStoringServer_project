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

class InstitutionController extends Controller
{
    // Search documents by National ID (for institutions)
    public function searchDocumentsByNationalId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'required|string|max:15'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $documents = Document::whereHas('user', function ($query) use ($request) {
            $query->where('national_id', $request->national_id);
        })->get();

        if ($documents->isEmpty()) {
            return response()->json(['message' => 'No documents found for the provided National ID'], 404);
        }

        return response()->json($documents, 200);
    }
}
