<?php

namespace App\Http\Controllers;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index(Request $request)
{   
    $search = $request->input('search');

    // Fetch clients with optional search criteria
    $clients = Client::when($search, function ($query, $search) {
        return $query->where('fullname', 'like', '%' . $search . '%')
                     ->orWhere('nickname', 'like', '%' . $search . '%')
                     ->orWhere('email', 'like', '%' . $search . '%')
                     ->orWhere('phone_number', 'like', '%' . $search . '%');
    })->get();

    $clientCount = $clients->count(); // Count the number of clients

    // Default to admin view
    return view('admin.clients.index', compact('clients', 'clientCount')); 
}


public function managerIndex(Request $request)
{   
    $search = $request->input('search');

    // Fetch clients with optional search criteria
    $clients = Client::when($search, function ($query, $search) {
        return $query->where('fullname', 'like', '%' . $search . '%')
                     ->orWhere('nickname', 'like', '%' . $search . '%')
                     ->orWhere('email', 'like', '%' . $search . '%')
                     ->orWhere('phone_number', 'like', '%' . $search . '%');
    })->get();

    $clientCount = $clients->count(); // Count the number of clients

    // Check if the logged-in user is a manager
    if (auth()->guard('manager')->check()) {
        return view('manager.clients', compact('clients', 'clientCount')); // Manager-specific view
    }
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Incoming Client Registration:', $request->all());
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'nickname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients',
            'phone_number' => 'required|string|max:20',
            'gender' => 'required|string|in:Male,Female',
            'date_of_birth' => 'required|date',
            'password' => 'required|string|min:8',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
    
        // Handle image upload
        if ($request->hasFile('profile_image')) {
            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
        }
    
        // Create client
        $client = Client::create([
            'fullname' => $validated['fullname'],
            'nickname' => $validated['nickname'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'password' => Hash::make($validated['password']), // Hashing the password
            'profile_image' => $imagePath ?? null,
        ]);
    
        return response()->json(['message' => 'Client registered successfully', 'client' => $client], 201);
    }
    
    public function profile(Request $request)
    {
        $client = $request->user(); // Assuming Sanctum for authentication
        return response()->json([
            'id' => $client->id, // Include the client's ID
            'fullname' => $client->fullname,  
            'nickname' => $client->nickname,
            'profile_image' => $client->profile_image,
            'phone_number' => $client->phone_number,
            'email' => $client->email,
        ]);
    }
    
    

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        return response()->json($client);
    }
    
    // For handling updates (PUT)
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'nickname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clients,email,' . $client->id,
            'phone_number' => 'required|string|max:20',
            'gender' => 'required|string|in:male,female,other',
            'date_of_birth' => 'required|date',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
    
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($client->profile_image) {
                Storage::disk('public')->delete($client->profile_image);
            }
            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            $validated['profile_image'] = $imagePath;
        }
    
        $client->update($validated);
    
        return redirect()->route('clients.index')->with('success', 'Client updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        //
    }

    
}

