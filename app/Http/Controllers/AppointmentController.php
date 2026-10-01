<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Staff;
use App\Models\Service;
use App\Models\StaffSchedule;

class AppointmentController extends Controller
{   

public function index(Request $request)
    {
        try {
            $status = $request->query('status', 'Pending');
    
            // Validate the status input
            if (!in_array($status, ['Pending', 'Accepted', 'Rejected', 'Completed', 'Reschedule', 'Cancelled', 'no_show', 'in_service'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid status provided.',
                ], 400);
            }
    
            $now = now()->setTimezone('Asia/Manila');
    
            // Update logic (unchanged):
            Appointment::where('status', 'Accepted')
                ->whereRaw("TIMESTAMP(appointment_date, appointment_time) <= ?", [$now->format('Y-m-d H:i:s')])
                ->update(['status' => 'in_service']);
    
            Appointment::where('status', 'in_service')
                ->whereRaw("TIMESTAMP(appointment_date, appointment_time) <= ?", [$now->subMinutes(30)->format('Y-m-d H:i:s')])
                ->update(['status' => 'Completed']);
    
            // Fetch staff list (NEW)
            $staffList = Staff::all(); // Or User::where('role', 'staff')->get()
    
            // Determine if the request is for JSON
            $isAppRequest = $request->wantsJson();
    
            // Fetch appointments (unchanged)
            $appointments = $isAppRequest
                ? Appointment::where('status', $status)->orderBy('created_at', 'desc')->get()
                : Appointment::orderBy('created_at', 'desc')->paginate(10);
    
            // Format appointments (unchanged)
            $appointments->each(function ($appointment) {
                $appointment->appointment_date = date('Y/m/d', strtotime($appointment->appointment_date));
                $appointment->appointment_time = date('H:i', strtotime($appointment->appointment_time));
    
                if (is_string($appointment->selected_staff)) {
                    $appointment->selected_staff = json_decode($appointment->selected_staff, true);
                }
    
                if (is_array($appointment->selected_staff)) {
                    $appointment->selected_staff = array_map(function ($staff) {
                        return "{$staff['firstName']} {$staff['lastName']}";
                    }, $appointment->selected_staff);
                }
    
                if (is_string($appointment->selected_services)) {
                    $appointment->selected_services = json_decode($appointment->selected_services, true);
                }
            });
    
            // Return response (modified to include staffList for web view)
            return $isAppRequest
                ? response()->json([
                    'status' => 'success',
                    'appointments' => $appointments,
                ])
                : view('cashier.appointments', compact('appointments', 'staffList')); // Added staffList here
        } catch (\Exception $e) {
            \Log::error('Error fetching appointments: ' . $e->getMessage());
    
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred while fetching appointments.',
                    'error' => $e->getMessage(),
                ], 500);
            }
    
            return redirect()->back()->with('error', 'An error occurred while fetching appointments.');
        }
    }

public function managerIndex(Request $request)
{
    try {
        $status = $request->query('status', 'all'); // Default to 'all' if no status is provided
        $search = $request->query('search');

        // Convert status to proper case format
        $status = strtolower($status);
        if ($status !== 'all') {
            $status = ucfirst($status);
        }

        // Validate the status input
        $validStatuses = ['all', 'Pending', 'Accepted', 'Rejected', 'Completed', 'Reschedule', 'Cancelled', 'No_show', 'In_service'];
        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid status provided.',
            ], 400);
        }

        $now = now()->setTimezone('Asia/Manila'); // Use local timezone for accurate comparison

        // Automatically update "Accepted" appointments to "Completed" if conditions are met
        Appointment::where('status', 'Accepted')
            ->whereRaw("TIMESTAMP(appointment_date, appointment_time) <= ?", [$now->subMinutes(30)->format('Y-m-d H:i:s')])
            ->update(['status' => 'Completed']);

        // Determine if the request is for JSON
        $isAppRequest = $request->wantsJson();

        // Build the query
        $query = Appointment::query();

        // Apply status filter only if status is not 'all'
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Apply search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('service_name', 'like', "%{$search}%")
                  ->orWhereHas('primary_staff', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('all_staff', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Fetch appointments based on request type
        $appointments = $isAppRequest
            ? $query->orderBy('created_at', 'desc')->get()
            : $query->orderBy('created_at', 'desc')->paginate(10);

        // Get all appointments for calendar view (without pagination)
        $allAppointments = Appointment::orderBy('created_at', 'desc')->get();

        // Format appointments for display
        $appointments->each(function ($appointment) {
            $appointment->appointment_date = date('Y/m/d', strtotime($appointment->appointment_date));
            $appointment->appointment_time = date('H:i', strtotime($appointment->appointment_time)); // Use 24-hour format

            if (is_string($appointment->selected_staff)) {
                $appointment->selected_staff = json_decode($appointment->selected_staff, true);
            }

            if (is_array($appointment->selected_staff)) {
                $appointment->selected_staff = array_map(function ($staff) {
                    // Handle both snake_case and camelCase
                    $first = $staff['first_name'] ?? $staff['firstName'] ?? '';
                    $last = $staff['last_name'] ?? $staff['lastName'] ?? '';
                    return trim("$first $last");
                }, $appointment->selected_staff);
            }

            if (is_string($appointment->selected_services)) {
                $appointment->selected_services = json_decode($appointment->selected_services, true);
            }

            // Handle staff_ids
            if (is_string($appointment->staff_ids)) {
                $appointment->staff_ids = json_decode($appointment->staff_ids, true);
            }

            // Load the primary staff member
            if ($appointment->staff_id) {
                $appointment->primary_staff = Staff::find($appointment->staff_id);
            }

            // Load all staff members if staff_ids exists
            if (!empty($appointment->staff_ids)) {
                $appointment->all_staff = Staff::whereIn('id', $appointment->staff_ids)->get();
            }
        });

        // Format all appointments for calendar view
        $allAppointments->each(function ($appointment) {
            $appointment->appointment_date = date('Y/m/d', strtotime($appointment->appointment_date));
            $appointment->appointment_time = date('H:i', strtotime($appointment->appointment_time));

            if (is_string($appointment->selected_staff)) {
                $appointment->selected_staff = json_decode($appointment->selected_staff, true);
            }

            if (is_array($appointment->selected_staff)) {
                $appointment->selected_staff = array_map(function ($staff) {
                    $first = $staff['first_name'] ?? $staff['firstName'] ?? '';
                    $last = $staff['last_name'] ?? $staff['lastName'] ?? '';
                    return trim("$first $last");
                }, $appointment->selected_staff);
            }

            if (is_string($appointment->selected_services)) {
                $appointment->selected_services = json_decode($appointment->selected_services, true);
            }

            if (is_string($appointment->staff_ids)) {
                $appointment->staff_ids = json_decode($appointment->staff_ids, true);
            }

            if ($appointment->staff_id) {
                $appointment->primary_staff = Staff::find($appointment->staff_id);
            }

            if (!empty($appointment->staff_ids)) {
                $appointment->all_staff = Staff::whereIn('id', $appointment->staff_ids)->get();
            }
        });

        // Return admin-specific view (with Accept/Decline functionality)
        return $isAppRequest
            ? response()->json([
                'status' => 'success',
                'appointments' => $appointments,
            ])
            : view('manager.appointments', compact('appointments', 'allAppointments'));
    } catch (\Exception $e) {
        \Log::error('Error fetching appointments: ' . $e->getMessage());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching appointments.',
                'error' => $e->getMessage(),
            ], 500);
        }

        return redirect()->back()->with('error', 'An error occurred while fetching appointments.');
    }
}


public function dashboard()
    {
        $user = auth()->user();

        $appointments = $user->appointments()->get();
        $upcomingAppointments = $user->appointments()
            ->whereDate('appointment_date', '>=', now()->toDateString())
            ->get();

            if (auth()->guard('manager')->check()) {
                return view('manager.clients', compact('appointments')); // Manager-specific view
            }

        return view('admin.dashboard', compact('appointments', 'upcomingAppointments'));
     }

     public function store(Request $request)
     {
         try {
             $validated = $request->validate([
                 'full_name' => 'required|string|max:255',
                 'phone_number' => 'required|string|max:20',
                 'appointment_date' => 'required|date',
                 'appointment_time' => 'required|date_format:H:i',
                 'selected_staff' => 'required|array',
                 'selected_services' => 'required|array',
                 'service_name' => 'required|string|max:255',
                 'payment_method' => 'nullable|string|max:50',
                 'category_id' => 'nullable|integer',
                 'client_id' => 'required|integer|exists:clients,id',
                 'service_id' => 'required|integer|exists:services,id',
                 'staff_id' => 'required|integer|exists:staff,id',
                 'upload_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                 'staff_ids' => 'nullable|array',
                 'staff_ids.*' => 'integer|exists:staff,id'
             ]);
     
             // Handle file upload
             if ($request->hasFile('upload_picture')) {
                 $imagePath = $request->file('upload_picture')->store('uploads', 'public');
                 $validated['upload_picture'] = $imagePath;
             }
     
             // Handle staff_ids
             if (isset($validated['staff_ids'])) {
                 // Ensure the primary staff_id is included in staff_ids
                 if (!in_array($validated['staff_id'], $validated['staff_ids'])) {
                     $validated['staff_ids'][] = $validated['staff_id'];
                 }
                 // Remove duplicates and sort
                 $validated['staff_ids'] = array_unique($validated['staff_ids']);
                 sort($validated['staff_ids']);
                 // Convert to JSON
                 $validated['staff_ids'] = json_encode($validated['staff_ids']);
             } else {
                 // If no staff_ids provided, create array with just the primary staff_id
                 $validated['staff_ids'] = json_encode([$validated['staff_id']]);
             }
     
             $appointment = Appointment::create($validated);
     
             return response()->json([
                 'status' => 'success',
                 'message' => 'Appointment created successfully.',
                 'data' => $appointment,
             ]);
         } catch (\Illuminate\Validation\ValidationException $e) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Validation failed.',
                 'errors' => $e->errors(),
             ], 422);
         } catch (\Exception $e) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'An error occurred while creating the appointment.',
                 'error' => $e->getMessage(),
             ], 500);
         }
     }
     
    

public function updateStatus(Request $request, $id)
{
    $validated = $request->validate([
        'status' => 'required|string|in:Pending,Accepted,Rejected,Completed,Reschedule,Cancelled,no_show,in_service',
    ]);

    try {
        $appointment = Appointment::findOrFail($id);

        // For API requests, verify ownership
        if ($request->wantsJson() && $appointment->client_id != auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this appointment'
            ], 403);
        }

        // Additional validation for cancellation
        if ($validated['status'] === 'Cancelled' && $appointment->status !== 'Pending') {
            $message = 'Only pending appointments can be cancelled';
            return $request->wantsJson() 
                ? response()->json(['success' => false, 'message' => $message], 400)
                : redirect()->back()->with('error', $message);
        }

        $updateData = ['status' => $validated['status']];

        // Add cancellation metadata if cancelling
        if ($validated['status'] === 'Cancelled') {
            $updateData['cancelled_at'] = now();
            $updateData['cancelled_by'] = auth()->id();
        }

        $appointment->update($updateData);

        return $request->wantsJson()
            ? response()->json([
                'success' => true,
                'message' => 'Appointment status updated successfully',
                'appointment' => $appointment
            ])
            : redirect()->route('cashier.appointments')->with('success', 'Appointment status updated successfully.');

    } catch (\Exception $e) {
        return $request->wantsJson()
            ? response()->json([
                'success' => false,
                'message' => 'Failed to update appointment status',
                'error' => $e->getMessage()
            ], 500)
            : redirect()->route('cashier.appointments')->with('error', 'Failed to update appointment status.');
    }
}

public function reject(Request $request, $id)
{
    // Validate the request data
    $validated = $request->validate([
        'reason' => 'required|string|max:1000'
    ]);

    try {
        // Find the appointment
        $appointment = Appointment::findOrFail($id);

        // Handle rejection
        $appointment->update([
            'status' => 'Rejected',
            'reason' => $validated['reason'],
            'rejected_at' => now()
        ]);

        return redirect()->route('cashier.appointments')
            ->with('success', 'Appointment rejected successfully.');

    } catch (\Exception $e) {
        Log::error('Error processing appointment rejection: ' . $e->getMessage());
        return redirect()->back()
            ->with('error', 'Failed to reject appointment. Please try again.')
            ->withInput();
    }
}


public function reschedule(Request $request, $id)
{
    $validated = $request->validate([
        'appointment_date' => 'required|date',
        'appointment_time' => [
            'required',
            'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/' // HH:MM format
        ],
        'reason' => 'required|string|max:1000'
    ]);

    // Format time to ensure consistency
    $time = \Carbon\Carbon::createFromFormat('H:i', $validated['appointment_time'])
                  ->format('H:i:s'); // Convert to full time format if needed

    $appointment = Appointment::findOrFail($id);
    
    $appointment->update([
        'appointment_date' => $validated['appointment_date'],
        'appointment_time' => $time,
        'status' => 'Reschedule',
        'reschedule_reason' => $validated['reason']
    ]);

    return redirect()->route('cashier.appointments')
       ->with('success', 'Appointment Reschedule successfully.');
}
    

 public function getAppointments(Request $request)
    {
        try {
            $staffId = $request->query('staff_id');
            $appointmentDate = $request->query('appointment_date');
            $status = $request->query('status', 'Accepted'); // Default to Accepted appointments

            $query = Appointment::query();

            if ($staffId) {
                $query->where('staff_id', $staffId);
            }

            if ($appointmentDate) {
                $query->whereDate('appointment_date', $appointmentDate);
            }

            if ($status) {
                $query->where('status', $status);
            }

            $appointments = $query->orderBy('appointment_time', 'asc')->get();

            // Get all accepted appointments for the staff on the same day to check availability
            $staffBusySlots = [];
            if ($staffId && $appointmentDate) {
                $busyAppointments = Appointment::where('staff_id', $staffId)
                    ->whereDate('appointment_date', $appointmentDate)
                    ->where('status', 'Accepted', 'Pending')
                    ->with('service') // Eager load service to get duration
                    ->get();

                foreach ($busyAppointments as $busyApp) {
                    $startTime = strtotime($busyApp->appointment_time);
                    $duration = $busyApp->service->duration ?? 60; // Default to 60 minutes if no duration set
                    $endTime = strtotime("+{$duration} minutes", $startTime);
                    
                    $staffBusySlots[] = [
                        'start' => date('H:i', $startTime),
                        'end' => date('H:i', $endTime)
                    ];
                }
            }

            // Format the appointments for the frontend
            $appointments->each(function ($appointment) use ($staffBusySlots) {
                $appointment->appointment_date = date('Y-m-d', strtotime($appointment->appointment_date));
                
                if ($appointment->status === 'Accepted') {
                    $appointment->appointment_time = date('H:i', strtotime($appointment->appointment_time));
                } else {
                    // Check if the time slot is within any busy period
                    $isTimeSlotBusy = false;
                    $appointmentTime = date('H:i', strtotime($appointment->appointment_time));
                    
                    foreach ($staffBusySlots as $busySlot) {
                        if ($appointmentTime >= $busySlot['start'] && $appointmentTime < $busySlot['end']) {
                            $isTimeSlotBusy = true;
                            break;
                        }
                    }
                    
                    // If the time slot is busy, show "Pending", otherwise show the actual time
                    $appointment->appointment_time = $isTimeSlotBusy ? 'Pending' : date('H:i', strtotime($appointment->appointment_time));
                }
            });

            return response()->json([
                'status' => 'success',
                'appointments' => $appointments
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching appointments: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching appointments'
            ], 500);
        }
    }

public function destroy($id)
    {
        $appointment = auth()->user()->appointments()->findOrFail($id);
        $appointment->delete();

        return redirect()->back()->with('success', 'Appointment deleted successfully.');
    }




 public function updatePastAppointments()
    {
        try {
            $now = now();
    
            // Update all "Accepted" appointments that are past due time (30 minutes after their scheduled time)
            $updatedCount = Appointment::where('status', 'Accepted')
                ->where(function ($query) use ($now) {
                    $query->whereRaw("TIMESTAMP(appointment_date, appointment_time) <= ?", [$now->subMinutes(30)->format('Y-m-d H:i:s')]);
                })
                ->update(['status' => 'Completed']);
    
            return response()->json([
                'status' => 'success',
                'message' => "{$updatedCount} past appointments marked as Completed."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating past appointments.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
public function acceptOrDeclineAppointment(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Accepted,Rejected',
        ]);
    
        try {
            // Fetch the appointment
            $appointment = Appointment::findOrFail($id);
    
            // Ensure `selected_staff` is an array
            $selectedStaff = is_string($appointment->selected_staff)
                ? json_decode($appointment->selected_staff, true)
                : $appointment->selected_staff;
    
            // Check if the authenticated staff is part of the selected staff for this appointment
            $authStaff = auth()->guard('staff')->user();
    
            $isAuthorized = false;
    
            if (is_array($selectedStaff)) {
                foreach ($selectedStaff as $staff) {
                    if (isset($staff['id']) && $staff['id'] == $authStaff->id) {
                        $isAuthorized = true;
                        break;
                    }
                }
            }
    
            if (!$isAuthorized) {
                return redirect()->back()->with('error', 'You are not authorized to manage this appointment.');
            }
    
            // Update the status of the appointment
            $appointment->status = $validated['status'];
            $appointment->save();
    
            return redirect()->route('appointments.staff')->with('success', "Appointment has been {$validated['status']} successfully.");
        } catch (\Exception $e) {
            \Log::error('Error updating appointment status: ' . $e->getMessage());
    
            return redirect()->back()->with('error', 'Failed to update appointment status.');
        }
    }
    
public function getCompletedServices()
    {
        try {
            // Fetch completed appointments with service profile images
            $popularServices = Appointment::where('status', 'Completed')
                ->with('service') // Eager load the service relationship
                ->selectRaw('category_id, service_id, service_name, COUNT(*) as count')
                ->groupBy('category_id', 'service_id', 'service_name')
                ->get();
    
            // Map category IDs to their names
            $categories = [
                1 => 'Hair',
                2 => 'Nail',
                3 => 'Waxing',
                4 => 'Eyelash',
            ];
    
            // Group services by category_id and format the data
            $groupedServices = $popularServices->groupBy('category_id')->map(function ($services, $categoryId) use ($categories) {
                return [
                    'category' => $categories[$categoryId] ?? 'Unknown',
                    'services' => $services->map(function ($service) {
                        return [
                            'service_name' => $service->service_name,
                            'count' => $service->count,
                            'profile_image' => $service->service && $service->service->profile_image
                                ? asset('storage/' . $service->service->profile_image)
                                : asset('storage/default-service.png'),
                        ];
                    })->values(),
                ];
            });
    
            return response()->json([
                'status' => 'success',
                'data' => $groupedServices,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching completed services: ' . $e->getMessage(), ['exception' => $e]);
    
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch completed services.',
            ], 500);
        }
    }
    

    
public function acceptReschedule($id)
    {
        $appointment = Appointment::findOrFail($id);
        
        if ($appointment->status !== 'Reschedule') {
            return response()->json(['error' => 'Invalid appointment status'], 400);
        }
    
        $appointment->status = 'Accepted';
        $appointment->save();
    
        return response()->json(['message' => 'Appointment reschedule accepted.']);
    }

public function getStaffByService($id)
    {
    $service = Service::with('staff')->findOrFail($id);
    return response()->json($service->staff);
    }

    //In the APP
public function checkAvailability(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:staff,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'service_id' => 'required|exists:services,id',
            'appointment_id' => 'sometimes|exists:appointments,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $date = $request->appointment_date;
        $time = $request->appointment_time;
        $staffId = $request->staff_id;
        $serviceId = $request->service_id;
        $appointmentId = $request->appointment_id;

        // Get the service duration
        $service = Service::findOrFail($serviceId);
        $duration = $service->duration ?? 60; // Default to 60 minutes if not set

        // Convert appointment time to Carbon instance
        $appointmentTime = \Carbon\Carbon::createFromFormat('H:i', $time);
        $appointmentEndTime = $appointmentTime->copy()->addMinutes($duration);

        // Get the day of week for the appointment date
        $dayOfWeek = \Carbon\Carbon::parse($date)->format('l');

        // Check staff schedule
        $staffSchedule = StaffSchedule::where('staff_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$staffSchedule || !$staffSchedule->is_working_day) {
            return response()->json([
                'available' => false,
                'message' => 'Staff is not working on this day'
            ]);
        }

        // Convert staff schedule times to Carbon instances
        $scheduleStartTime = \Carbon\Carbon::parse($staffSchedule->start_time);
        $scheduleEndTime = \Carbon\Carbon::parse($staffSchedule->end_time);

        // Check if appointment time is within staff's working hours
        if ($appointmentTime->lt($scheduleStartTime) || $appointmentEndTime->gt($scheduleEndTime)) {
            return response()->json([
                'available' => false,
                'message' => 'Appointment time is outside staff working hours'
            ]);
        }

        // Check for overlapping appointments
        $overlappingAppointments = Appointment::where('staff_id', $staffId)
            ->where('appointment_date', $date)
            ->where(function ($query) use ($appointmentTime, $appointmentEndTime) {
                $query->where(function ($q) use ($appointmentTime, $appointmentEndTime) {
                    // Check if existing appointment overlaps with new appointment
                    $q->whereRaw('TIMESTAMP(appointment_date, appointment_time) <= ?', [$appointmentEndTime->format('Y-m-d H:i:s')])
                      ->whereRaw('TIMESTAMP(appointment_date, appointment_time) + INTERVAL (SELECT duration FROM services WHERE id = service_id) MINUTE >= ?', [$appointmentTime->format('Y-m-d H:i:s')]);
                });
            });

        if ($appointmentId) {
            $overlappingAppointments->where('id', '!=', $appointmentId);
        }

        if ($overlappingAppointments->exists()) {
            return response()->json([
                'available' => false,
                'message' => 'Time slot overlaps with existing appointment'
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Time slot available',
            'duration' => $duration
        ]);

    } catch (\Exception $e) {
        \Log::error('Error checking availability: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while checking availability'
        ], 500);
    }
}
    
public function clientReschedule(Appointment $appointment, Request $request)
{
    try {
        // Get the authenticated user
        $user = auth()->user();
        
        // Check if user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized - Please login'], 401);
        }

        // Log the authentication details for debugging
        \Log::info('Client Reschedule Auth Check', [
            'appointment_client_id' => $appointment->client_id,
            'authenticated_user_id' => $user->id,
            'appointment_id' => $appointment->id
        ]);

        // Verify ownership - check if the appointment belongs to the authenticated user
        if ($appointment->client_id != $user->id) {
            return response()->json([
                'message' => 'Unauthorized - You can only reschedule your own appointments',
                'details' => [
                    'appointment_client_id' => $appointment->client_id,
                    'authenticated_user_id' => $user->id
                ]
            ], 403);
        }

        // Basic validation
        $validated = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'staff_id' => 'required|exists:staff,id'
        ]);

        // Check if staff is available
        $conflictingAppointment = Appointment::where('staff_id', $validated['staff_id'])
            ->where('appointment_date', $validated['appointment_date'])
            ->where('appointment_time', $validated['appointment_time'])
            ->where('id', '!=', $appointment->id)
            ->whereIn('status', ['Accepted', 'Reschedule'])
            ->exists();

        if ($conflictingAppointment) {
            return response()->json(['message' => 'Time slot not available'], 409);
        }

        // Update the appointment
        $appointment->update([
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'],
            'staff_id' => $validated['staff_id'],
            'status' => 'Accepted',
            'reschedule_reason' => $request->input('reason', 'Client requested reschedule')
        ]);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Error in clientReschedule: ' . $e->getMessage(), [
            'appointment_id' => $appointment->id,
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);
        return response()->json([
            'message' => 'An error occurred while rescheduling the appointment'
        ], 500);
    }
}
private function isTimeSlotAvailable($staffId, $date, $time, $excludeAppointmentId = null)
    {
        $query = Appointment::where('staff_id', $staffId)
            ->where('appointment_date', $date)
            ->where('appointment_time', $time);
    
        if ($excludeAppointmentId) {
            $query->where('id', '!=', $excludeAppointmentId);
        }
    
        return !$query->exists();
    }
public function cancelAppointment($id)
    {
        $appointment = Appointment::find($id);
    
        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found.',
            ], 404);
        }
    
        if ($appointment->status !== 'Accepted') {
            return response()->json([
                'message' => 'Only pending appointments can be cancelled.',
            ], 403);
        }
    
        $appointment->status = 'Cancelled';
        $appointment->save();
    
        return response()->json([
            'message' => 'Appointment cancelled successfully.',
        ], 200);
    }

public function getStaffByCategory(Request $request)
{
    try {
        $categoryId = $request->query('category_id');
        
        if (!$categoryId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category ID is required'
            ], 400);
        }

        $staff = Staff::whereHas('services', function($query) use ($categoryId) {
            $query->where('category_id', $categoryId);
        })->get();

        return response()->json($staff);
    } catch (\Exception $e) {
        \Log::error('Error fetching staff by category: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch staff members'
        ], 500);
    }
}
}


