<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Category;
use App\Models\Appointment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\DayOffRequest;
use App\Models\StaffSchedule;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{   

    
    public function index(Request $request)
    {
        $query = Staff::with('categories');

        // Handle search
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('username', 'like', '%' . $request->search . '%');
            });
        }

        $staff = $query->get();
        $categories = Category::all(); // Fetch all categories

        return view('admin.staff.index', compact('staff', 'categories'));
    }

    public function create()
    {
        $categories = Category::all(); // Fetch all categories
        return view('admin.staff.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'first_name' => 'required',
            'email' => 'required|email|unique:staff',
            'username' => 'required|unique:staff',
            'password' => 'required',
            'categories' => 'required|array',
        ]);

        $profilePicturePath = $request->hasFile('profile_picture')
            ? $request->file('profile_picture')->store('profile_pictures', 'public')
            : null;

        // Create staff without categories first
        $staff = Staff::create([
            'profile_picture' => $profilePicturePath,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'username' => $request->username,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'password' => bcrypt($request->password),
        ]);

        // Attach categories
        $staff->categories()->attach($request->categories);

        return redirect()->route('staff.index')->with('success', 'Staff added successfully');
    }

    public function edit($id)
    {
        $staff = Staff::with('categories')->findOrFail($id);
        $categories = Category::all(); // Fetch all categories for the edit view

        // Ensure the `staff` object contains all necessary attributes
        if (!$staff) {
            return redirect()->route('staff.index')->with('error', 'Staff not found.');
        }

        return response()->json([
            'staff' => $staff,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required',
            'email' => 'required|email|unique:staff,email,' . $id,
            'username' => 'required|unique:staff,username,' . $id,
            'categories' => 'required|array',
        ]);

        $staff = Staff::findOrFail($id);

        $staff->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'username' => $request->username,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
        ]);

        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
            $staff->update(['profile_picture' => $profilePicturePath]);
        }

        // Sync categories
        $staff->categories()->sync($request->categories);

        return redirect()->route('staff.index')->with('success', 'Staff updated successfully');
    }

    public function destroy($id)
    {
        try {
            $staff = Staff::findOrFail($id);
            
            // Delete profile picture if exists
            if ($staff->profile_picture) {
                Storage::disk('public')->delete($staff->profile_picture);
            }
            
            // Detach categories first
            $staff->categories()->detach();
            
            // Delete the staff
            $staff->delete();
            
            return redirect()->route('staff.index')->with('success', 'Staff deleted successfully');
        } catch (\Exception $e) {
            \Log::error('Error deleting staff: ' . $e->getMessage());
            return redirect()->route('staff.index')->with('error', 'An error occurred while deleting the staff member.');
        }
    }

    
    
    
    
    // Manager's methods

    public function managerIndex(Request $request)
    {
        $query = Staff::with('categories');
    
        // Handle search
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('username', 'like', '%' . $request->search . '%');
            });
        }
    
        $staff = $query->get();
        $categories = Category::all();
    
        return view('manager.staff', compact('staff', 'categories'));
    }
    

    public function managerCreate()
    {
        $categories = Category::all(); // Fetch all categories

        if (auth()->guard('manager')->check()) {
            return view('manager.staff', compact('categories'));
        } else {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }
    }

    public function managerStore(Request $request)
    {
        $request->validate([
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'first_name' => 'required',
            'email' => 'required|email|unique:staff',
            'username' => 'required|unique:staff',
            'password' => 'required',
            'categories' => 'required|array',
        ]);

        $profilePicturePath = $request->hasFile('profile_picture')
            ? $request->file('profile_picture')->store('profile_pictures', 'public')
            : null;

        // Create staff without categories first
        $staff = Staff::create([
            'profile_picture' => $profilePicturePath,
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'username' => $request->username,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'password' => bcrypt($request->password),
        ]);

        // Attach categories
        $staff->categories()->attach($request->categories);

        return redirect()->route('manager.staff')->with('success', 'Staff added successfully by manager');
    }

    public function managerEdit($id)
    {
        $staff = Staff::with('categories')->findOrFail($id);
        $categories = Category::all(); // Fetch all categories

        if (auth()->guard('manager')->check()) {
            return view('manager.staff', compact('staff', 'categories'));
        } else {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }
    }

    public function managerUpdate(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required',
            'email' => 'required|email|unique:staff,email,' . $id,
            'username' => 'required|unique:staff,username,' . $id,
            'categories' => 'required|array',
        ]);

        $staff = Staff::findOrFail($id);

        $staff->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'username' => $request->username,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
        ]);

        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
            $staff->update(['profile_picture' => $profilePicturePath]);
        }

        // Sync categories
        $staff->categories()->sync($request->categories);

        return redirect()->route('manager.staff')->with('success', 'Staff updated successfully by manager');
    }

    public function managerDestroy($id)
    {
        $staff = Staff::findOrFail($id);
        $staff->categories()->detach(); // Detach categories first
        $staff->delete();

        return redirect()->route('manager.staff')->with('success', 'Staff deleted successfully by manager');
    }

    public function showLoginForm()
    {
        return view('staff.login'); // Ensure this view exists
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'username' => 'required',
                'password' => 'required'
            ]);

            $staff = Staff::where('username', $credentials['username'])->first();

            if (!$staff) {
                return back()->withErrors(['username' => 'Invalid credentials.']);
            }

            // Check if account is active
            if (!$staff->is_active) {
                return back()->withErrors(['username' => 'Your account has been deactivated. Please contact the administrator.']);
            }

            if (Hash::check($credentials['password'], $staff->password)) {
                auth()->guard('staff')->login($staff);
                $request->session()->regenerate();
                return redirect()->intended('/staff/dashboard');
            }

            return back()->withErrors(['username' => 'Invalid credentials.']);
        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return back()->withErrors(['username' => 'An error occurred during login. Please try again.']);
        }
    }
    
    

 // Add these methods to your StaffController or relevant controller

 public function dashboard()
 {
     try {
         $staff = auth()->guard('staff')->user();
         if (!$staff) {
             throw new \Exception('Staff not authenticated');
         }

         $now = now()->setTimezone('Asia/Manila');
         
         // Simplified query to get appointments
         $allAppointments = DB::table('appointments')
             ->where(function($query) use ($staff) {
                 $query->where('staff_id', $staff->id)
                     ->orWhereRaw("JSON_CONTAINS(staff_ids, ?)", [json_encode($staff->id)]);
             })
             ->whereIn('status', ['Accepted', 'in_service'])
             ->orderBy('appointment_date', 'desc')
             ->orderBy('appointment_time', 'desc')
             ->get();

         // Get total clients
         $totalClients = DB::table('appointments')
             ->where(function($query) use ($staff) {
                 $query->where('staff_id', $staff->id)
                     ->orWhereRaw("JSON_CONTAINS(staff_ids, ?)", [json_encode($staff->id)]);
             })
             ->whereIn('status', ['Accepted', 'in_service'])
             ->whereNotNull('client_id')
             ->distinct('client_id')
             ->count('client_id');
         
         // Get total appointments
         $totalAppointments = $allAppointments->count();
         
         // Get upcoming appointments
         $upcomingAppointments = $allAppointments->filter(function($appointment) use ($now) {
             $appointmentDateTime = $appointment->appointment_date . ' ' . $appointment->appointment_time;
             return strtotime($appointmentDateTime) > strtotime($now);
         })->sortBy(function($appointment) {
             return $appointment->appointment_date . ' ' . $appointment->appointment_time;
         })->values();

         // For debugging
         \Log::info('Staff Dashboard Data:', [
             'staff_id' => $staff->id,
             'total_appointments' => $totalAppointments,
             'total_clients' => $totalClients,
             'upcoming_appointments' => $upcomingAppointments->count()
         ]);
         
         return view('staff.dashboard', compact(
             'staff',
             'totalClients',
             'totalAppointments',
             'upcomingAppointments',
             'allAppointments'
         ));
         
     } catch (\Exception $e) {
         \Log::error('Staff Dashboard Error:', [
             'message' => $e->getMessage(),
             'file' => $e->getFile(),
             'line' => $e->getLine(),
             'trace' => $e->getTraceAsString()
         ]);
         
         return redirect()->back()->with('error', 'Unable to load dashboard. Please try again later.');
     }
 }


public function logout(Request $request)
{
    try {
        // Logout the staff user
        Auth::guard('staff')->logout();
        
        // Invalidate the session
        $request->session()->invalidate();
        
        // Regenerate the CSRF token
        $request->session()->regenerateToken();
        
        // Redirect to login page with success message
        return redirect()->route('staff.login')->with('success', 'You have been logged out successfully.');
    } catch (\Exception $e) {
        \Log::error('Error during staff logout: ' . $e->getMessage());
        return redirect()->back()->with('error', 'An error occurred during logout.');
    }
}

public function viewSchedule()
{
    $staff = Auth::guard('staff')->user();
    $schedule = $staff->schedules()
        ->orderByRaw("FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
        ->get();

    return view('staff.schedule', compact('schedule'));
}

public function requestDayOff()
{
    return view('staff.request_day_off');
}

public function storeDayOffRequest(Request $request)
{
    $request->validate([
        'requested_day' => 'required|string',
        'note' => 'nullable|string|max:500'
    ]);

    $staff = Auth::guard('staff')->user();

    // Check if there's already a pending request for this day
    $existingRequest = DayOffRequest::where('staff_id', $staff->id)
        ->where('requested_day', $request->requested_day)
        ->where('status', 'pending')
        ->first();

    if ($existingRequest) {
        return back()->withErrors(['requested_day' => 'You already have a pending request for this day.']);
    }

    DayOffRequest::create([
        'staff_id' => $staff->id,
        'requested_day' => $request->requested_day,
        'note' => $request->note,
        'status' => 'pending'
    ]);

    return redirect()->route('staff.dashboard')
        ->with('success', 'Your day off request has been submitted successfully.');
}

// Add new method to get staff schedule
public function getStaffSchedule($staffId, Request $request)
{
    try {
        $date = $request->query('date');
        if (!$date) {
            return response()->json([
                'status' => 'error',
                'message' => 'Date parameter is required'
            ], 400);
        }

        // Convert date to day of week
        $dayOfWeek = date('l', strtotime($date));

        // Get staff schedule for the day
        $schedule = StaffSchedule::where('staff_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'No schedule found for this day'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'day_of_week' => $schedule->day_of_week,
                'is_working_day' => $schedule->is_working_day,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Error fetching staff schedule: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while fetching the schedule'
        ], 500);
    }
}

public function attendance()
{
    $staff = auth()->user();
    $today = Carbon::today();
    
    // Get today's attendance record
    $todayAttendance = Attendance::where('staff_id', $staff->id)
        ->whereDate('date', $today)
        ->first();
        
    // Get all attendance records for the current month
    $attendanceRecords = Attendance::where('staff_id', $staff->id)
        ->whereMonth('date', $today->month)
        ->whereYear('date', $today->year)
        ->orderBy('date', 'desc')
        ->get();

    // Get staff schedule to determine working days
    $schedule = StaffSchedule::where('staff_id', $staff->id)->get();
    $workingDays = $schedule->where('is_working_day', true)->pluck('day_of_week')->toArray();
    
    // Map day names to numbers (0 = Sunday, 6 = Saturday)
    $dayMap = [
        'Sunday' => 0,
        'Monday' => 1,
        'Tuesday' => 2,
        'Wednesday' => 3,
        'Thursday' => 4,
        'Friday' => 5,
        'Saturday' => 6
    ];
    
    // Convert working days to numbers
    $workingDayNumbers = array_map(function($day) use ($dayMap) {
        return $dayMap[$day] ?? null;
    }, $workingDays);
    
    // Create absent records for working days without attendance
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();
    
    for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
        // Skip if it's not a working day
        if (!in_array($date->dayOfWeek, $workingDayNumbers)) {
            continue;
        }
        
        // Skip if it's a future date
        if ($date->isFuture()) {
            continue;
        }
        
        // Skip if it's today (never mark today as absent)
        if ($date->isToday()) {
            continue;
        }
        
        // Check if attendance record exists for this date
        $existingRecord = Attendance::where('staff_id', $staff->id)
            ->whereDate('date', $date)
            ->first();
            
        // If no record exists and it's a working day, create absent record
        if (!$existingRecord) {
            Attendance::create([
                'staff_id' => $staff->id,
                'date' => $date->toDateString(),
                'status' => 'absent'
            ]);
        }
    }
    
    // Refresh attendance records after creating absent records
    $attendanceRecords = Attendance::where('staff_id', $staff->id)
        ->whereMonth('date', $today->month)
        ->whereYear('date', $today->year)
        ->orderBy('date', 'desc')
        ->get();
        
    return view('staff.attendance', [
        'hasTimeIn' => $todayAttendance && $todayAttendance->time_in,
        'hasTimeOut' => $todayAttendance && $todayAttendance->time_out,
        'attendanceRecords' => $attendanceRecords,
        'currentTime' => now()->format('h:i:s A')
    ]);
}

public function timeIn(Request $request)
{
    try {
        $staff = Auth::guard('staff')->user();
        $today = Carbon::today();
        
        // Get the device time from the request
        $deviceTime = Carbon::parse($request->device_time);
        
        // Check if there's already a time-in record for today
        $existingRecord = Attendance::where('staff_id', $staff->id)
            ->whereDate('date', $today)
            ->first();

        if ($existingRecord) {
            if ($existingRecord->time_in) {
                return redirect()->back()->with('error', 'You have already timed in today.');
            }
            // Update existing record
            $existingRecord->time_in = $deviceTime;
            $existingRecord->status = 'present';
            $existingRecord->save();
        } else {
            // Create new record
            Attendance::create([
                'staff_id' => $staff->id,
                'date' => $today,
                'time_in' => $deviceTime,
                'status' => 'present'
            ]);
        }

        return redirect()->back()->with('success', 'Time in recorded successfully.');
    } catch (\Exception $e) {
        \Log::error('Time in error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Failed to record time in. Please try again.');
    }
}

public function timeOut(Request $request)
{
    try {
        $staff = Auth::guard('staff')->user();
        $today = Carbon::today();
        
        // Get the device time from the request
        $deviceTime = Carbon::parse($request->device_time);
        
        $record = Attendance::where('staff_id', $staff->id)
            ->whereDate('date', $today)
            ->first();

        if (!$record) {
            return redirect()->back()->with('error', 'No time-in record found for today.');
        }

        if ($record->time_out) {
            return redirect()->back()->with('error', 'You have already timed out today.');
        }

        $record->time_out = $deviceTime;
        
        // Calculate total hours if both time-in and time-out are present
        if ($record->time_in) {
            $timeIn = Carbon::parse($record->time_in);
            $timeOut = $deviceTime;
            $totalHours = $timeOut->diffInHours($timeIn) + ($timeOut->diffInMinutes($timeIn) % 60) / 60;
            $record->total_hours = round($totalHours, 2);
        }
        
        $record->save();

        return redirect()->back()->with('success', 'Time out recorded successfully.');
    } catch (\Exception $e) {
        \Log::error('Time out error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Failed to record time out. Please try again.');
    }
}

public function myAttendance()
{
    try {
        $staff = auth()->guard('staff')->user();
        if (!$staff) {
            return redirect()->route('staff.login')->with('error', 'Please login first.');
        }

        // Get current month and year
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Get attendance records for the current month
        $attendanceRecords = Attendance::where('staff_id', $staff->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->orderBy('date', 'desc')
            ->get();

        // Calculate statistics
        $totalPresent = $attendanceRecords->where('status', 'present')->count();
        $totalAbsent = $attendanceRecords->where('status', 'absent')->count();
        $totalLate = $attendanceRecords->where('status', 'late')->count();
        $totalHours = $attendanceRecords->sum('total_hours');
        
        // Calculate average hours per day
        $workingDays = $attendanceRecords->where('status', 'present')->count();
        $averageHours = $workingDays > 0 ? round($totalHours / $workingDays, 2) : 0;

        return view('staff.myattendance', compact(
            'attendanceRecords',
            'totalPresent',
            'totalAbsent',
            'totalLate',
            'totalHours',
            'averageHours'
        ));

    } catch (\Exception $e) {
        \Log::error('My Attendance Error: ' . $e->getMessage());
        return redirect()->back()->with('error', 'An error occurred while fetching attendance records.');
    }
}

public function toggleStatus(Staff $staff)
{
    try {
        $staff->is_active = !$staff->is_active;
        $staff->save();

        $status = $staff->is_active ? 'activated' : 'deactivated';
        return redirect()->route('staff.index')
            ->with('success', "Staff account has been {$status} successfully.");
    } catch (\Exception $e) {
        return redirect()->route('staff.index')
            ->with('error', 'Failed to update staff status: ' . $e->getMessage());
    }
}

public function resetPassword(Request $request, Staff $staff)
{
    $request->validate([
        'new_password' => 'required|min:8|confirmed',
    ]);

    try {
        $staff->password = Hash::make($request->new_password);
        $staff->save();

        return redirect()->route('staff.index')
            ->with('success', 'Staff password has been reset successfully.');
    } catch (\Exception $e) {
        return redirect()->route('staff.index')
            ->with('error', 'Failed to reset password: ' . $e->getMessage());
    }
}

}
