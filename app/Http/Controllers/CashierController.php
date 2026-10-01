<?php

namespace App\Http\Controllers;

use App\Models\Cashier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class CashierController extends Controller
{
    public function index()
    {
        $cashiers = Cashier::all();  // Retrieve all cashiers
        return view('admin.cashier.index', compact('cashiers')); // Ensure this view exists
    }

    public function create()
    {
        // Logic to show a form for creating a new cashier
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'username' => 'required|string|max:255|unique:cashiers',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
            'email' => 'required|string|email|max:255|unique:cashiers',
            'password' => 'required|string|min:6',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        // Handle profile image upload
        $profileImagePath = null;
        if ($request->hasFile('profile_image')) {
            $profileImagePath = $request->file('profile_image')->store('profile_images', 'public');
        }
    
        $cashier = new Cashier();
        $cashier->username = $validatedData['username'];
        $cashier->first_name = $validatedData['first_name'];
        $cashier->middle_name = $validatedData['middle_name'] ?? null;
        $cashier->last_name = $validatedData['last_name'];
        $cashier->gender = $validatedData['gender'];
        $cashier->date_of_birth = $validatedData['date_of_birth'];
        $cashier->email = $validatedData['email'];
        $cashier->password = Hash::make($validatedData['password']);
        $cashier->profile_image = $profileImagePath;
    
        if ($cashier->save()) {
            return redirect()->route('cashiers.index')->with('success', 'Cashier added successfully!');
        } else {
            dd("Failed to save cashier"); // Debug if saving fails
        }
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'username' => 'required|string|max:255|unique:cashiers,username,' . $id,
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
            'email' => 'required|string|email|max:255|unique:cashiers,email,' . $id,
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $cashier = Cashier::findOrFail($id);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old profile image if exists
            if ($cashier->profile_image) {
                Storage::disk('public')->delete($cashier->profile_image);
            }
            $profileImagePath = $request->file('profile_image')->store('profile_images', 'public');
            $cashier->profile_image = $profileImagePath;
        }

        // Update cashier details
        $cashier->username = $validatedData['username'];
        $cashier->first_name = $validatedData['first_name'];
        $cashier->middle_name = $validatedData['middle_name'] ?? null;
        $cashier->last_name = $validatedData['last_name'];
        $cashier->gender = $validatedData['gender'];
        $cashier->date_of_birth = $validatedData['date_of_birth'];
        $cashier->email = $validatedData['email'];
        $cashier->save();

        return redirect()->route('cashiers.index')->with('success', 'Cashier updated successfully!');
    }

    public function showLoginForm()
    {
        return view('cashier.login');
    }

    
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
    
        $credentials = $request->only('username', 'password');
    
        // ✅ Use the 'cashier' guard to check the correct table
        if (Auth::guard('cashier')->attempt($credentials)) {
            Auth::guard('cashier')->user(); // Ensure session is set
            return redirect()->route('cashier.dashboard'); // Redirect to cashier dashboard
        }
    
        return back()->withErrors(['login' => 'Invalid credentials'])->withInput();
    }
    
public function dashboard()
{
    // Get all products for POS
    $products = Product::all();

    // Get low stock products (less than 20 items)
    $lowStockProducts = Product::where('stocks', '<', 20)->get();

    // Get dates from today to next 7 days
    $today = now();
    $weekDays = [];
    for ($i = 0; $i < 7; $i++) {
        $date = $today->copy()->addDays($i);
        $weekDays[] = [
            'date' => $date->format('Y-m-d'),
            'short_day' => $date->format('D'),
            'formatted_date' => $date->format('M d, Y'),
            'is_today' => $i === 0
        ];
    }

    // Get appointments for the next 7 days
    $weeklyAppointments = Appointment::whereBetween('appointment_date', [
        $today->format('Y-m-d'),
        $today->copy()->addDays(6)->format('Y-m-d')
    ])
    ->whereIn('status', ['Pending', 'Accepted'])
    ->orderBy('appointment_date', 'asc')
    ->orderBy('appointment_time', 'asc')
    ->get()
    ->groupBy('appointment_date');

    return view('cashier.dashboard', compact('products', 'lowStockProducts', 'weekDays', 'weeklyAppointments'));
}

public function appointments()
{
    // Get current week's dates
    $startOfWeek = now()->startOfWeek();
    $weekDays = [];
    for ($i = 0; $i < 7; $i++) {
        $date = $startOfWeek->copy()->addDays($i);
        $weekDays[] = [
            'date' => $date->format('Y-m-d'),
            'short_day' => $date->format('D'),
            'formatted_date' => $date->format('M d, Y')
        ];
    }

    // Get appointments for the current week
    $weeklyAppointments = Appointment::whereBetween('appointment_date', [
        $startOfWeek->format('Y-m-d'),
        $startOfWeek->copy()->endOfWeek()->format('Y-m-d')
    ])
    ->whereIn('status', ['Pending', 'Accepted'])
    ->orderBy('appointment_time', 'asc')
    ->get()
    ->groupBy('appointment_date');

    return view('cashier.appointments', compact('weekDays', 'weeklyAppointments'));
}

public function markAsNoShow($id)
{
    $appointment = Appointment::findOrFail($id);
    $appointment->status = 'no_show';
    $appointment->save();

    return redirect()->back()->with('success', 'Appointment marked as no show.');
}

public function reschedule(Request $request, $id)
{
    $request->validate([
        'appointment_date' => 'required|date|after_or_equal:today',
        'appointment_time' => 'required',
        'reason' => 'required|string|max:255'
    ]);

    $appointment = Appointment::findOrFail($id);
    
    // Update appointment
    $appointment->appointment_date = $request->appointment_date;
    $appointment->appointment_time = $request->appointment_time;
    $appointment->status = 'Rescheduled';
    $appointment->reschedule_reason = $request->reason;
    $appointment->save();

    return redirect()->back()->with('success', 'Appointment rescheduled successfully.');
}

public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:Accepted,Rejected,in_service,Completed',
        'reason' => 'required_if:status,Rejected|string|max:255'
    ]);

    $appointment = Appointment::findOrFail($id);
    $appointment->status = $request->status;
    
    if ($request->status === 'Rejected') {
        $appointment->rejection_reason = $request->reason;
    }
    
    $appointment->save();

    return redirect()->back()->with('success', 'Appointment status updated successfully.');
}

public function logout(Request $request)
{
    Auth::guard('cashier')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('cashier.login');
}

}
