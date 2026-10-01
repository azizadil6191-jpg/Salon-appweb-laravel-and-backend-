<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PushToken;

class PushTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'pushToken' => 'required|string'
        ]);

        $user = auth()->user();
        
        PushToken::updateOrCreate(
            ['user_id' => $user->id],
            ['token' => $request->pushToken]
        );

        return response()->json(['status' => 'success']);
    }
}