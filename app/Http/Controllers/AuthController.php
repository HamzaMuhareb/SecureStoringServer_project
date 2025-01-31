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

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'national_id' => 'required|string|unique:users,national_id|max:15',
            'birth_date' => 'required|date',
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'national_id' => $request->national_id,
            'phone_number' => $request->phone_number,
            'birth_date' => $request->birth_date,
            'role' => 'individual',
        ]);

        $token = $user->createToken('LaravelAuthApp')->accessToken;

        return response()->json(['token' => $token], 200);
    }
    // Register a new institution user
    public function registerInstitution(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'national_id' => 'required|string|unique:users,national_id|max:15',
            'institution_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'role' => 'institution',
            'institution_name' => $request->institution_name,
            'national_id' => $request->national_id,
            'phone_number' => $request->phone_number,
        ]);

        return response()->json(['message' => 'Institution registered successfully', 'user' => $user], 201);
    }
    // Login user
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => 'required|string|max:15',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error validation' => $validator->errors()], 401);
        }

        $user = User::where('national_id', $request->national_id)->first();
        if (!$user) {
            return response()->json(['error' => 'not found'], 401);
        }
        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('LaravelAuthApp')->accessToken;
            return response()->json(['token' => $token], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Successfully logged out'], 200);
    }
}
