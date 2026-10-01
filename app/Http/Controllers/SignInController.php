<?php

namespace App\Http\Controllers;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SignInController extends Controller
{
    public function signIn(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the client by email
        $client = Client::where('email', $request->email)->first();

        // If client does not exist or password is incorrect
        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Generate a token if credentials are valid
        $token = $client->createToken('authToken')->plainTextToken;

        // Return the success response with token
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'client' => $client,
            'token' => $token,
        ]);
    }
}
