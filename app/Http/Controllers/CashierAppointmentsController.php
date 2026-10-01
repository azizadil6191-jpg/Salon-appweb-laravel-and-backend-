<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CashierAppointmentsController extends Controller
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

            // Update logic for appointment statuses
            Appointment::where('status', 'Accepted')
                ->whereRaw("TIMESTAMP(appointment_date, appointment_time) <= ?", [$now->format('Y-m-d H:i:s')])
                ->update(['status' => 'in_service']);

            Appointment::where('status', 'in_service')
                ->whereRaw("TIMESTAMP(appointment_date, appointment_time) <= ?", [$now->subMinutes(30)->format('Y-m-d H:i:s')])
                ->update(['status' => 'Completed']);

            // Fetch appointments with service and category relationships
            $appointments = $request->wantsJson()
                ? Appointment::with(['service.category'])->where('status', $status)->orderBy('created_at', 'desc')->get()
                : Appointment::whereIn('status', ['Pending', 'Accepted'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

            // Format appointments
            $appointments->each(function ($appointment) {
                $appointment->appointment_date = date('Y/m/d', strtotime($appointment->appointment_date));
                $appointment->appointment_time = date('H:i', strtotime($appointment->appointment_time));

                // Debug selected_staff before processing
                \Log::info('Selected staff before processing:', [
                    'appointment_id' => $appointment->id,
                    'selected_staff' => $appointment->selected_staff
                ]);

                if (is_string($appointment->selected_staff)) {
                    $appointment->selected_staff = json_decode($appointment->selected_staff, true);
                    \Log::info('After json_decode:', [
                        'appointment_id' => $appointment->id,
                        'selected_staff' => $appointment->selected_staff
                    ]);
                }

                if (is_array($appointment->selected_staff)) {
                    $appointment->selected_staff = array_map(function ($staff) {
                        // Handle both snake_case and camelCase
                        $first = $staff['first_name'] ?? $staff['firstName'] ?? '';
                        $last = $staff['last_name'] ?? $staff['lastName'] ?? '';
                        $name = trim("$first $last");
                        \Log::info('Staff name processed:', [
                            'staff' => $staff,
                            'processed_name' => $name
                        ]);
                        return $name;
                    }, $appointment->selected_staff);
                    \Log::info('After array_map:', [
                        'appointment_id' => $appointment->id,
                        'selected_staff' => $appointment->selected_staff
                    ]);
                }

                if (is_string($appointment->selected_services)) {
                    $appointment->selected_services = json_decode($appointment->selected_services, true);
                }
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'appointments' => $appointments,
                ]);
            }

            // Add cache control headers for the view response
            return response()->view('cashier.appointments', compact('appointments'))
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

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

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Pending,Accepted,Rejected,Completed,Reschedule,Cancelled,no_show,in_service',
        ]);

        try {
            $appointment = Appointment::findOrFail($id);

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
        try {
            $appointment = Appointment::findOrFail($id);
            
            $appointment->update([
                'status' => 'Rejected',
                'reason' => $request->reason
            ]);

            return redirect()->route('cashier.appointments')
                ->with('success', 'Appointment rejected successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to reject appointment. Please try again.');
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
            'selected_staff' => 'required|array',
            'selected_staff.*' => 'required|string',
            'reason' => 'required|string|max:1000'
        ]);

        // Format time to ensure consistency
        $time = \Carbon\Carbon::createFromFormat('H:i', $validated['appointment_time'])
                      ->format('H:i:s'); // Convert to full time format if needed

        $appointment = Appointment::findOrFail($id);
        
        // Process selected staff
        $selectedStaff = array_map(function($staffJson) {
            return json_decode($staffJson, true);
        }, $validated['selected_staff']);
        
        $appointment->update([
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $time,
            'status' => 'Reschedule',
            'reschedule_reason' => $validated['reason'],
            'selected_staff' => $selectedStaff
        ]);

        return redirect()->route('cashier.appointments')
           ->with('success', 'Appointment Reschedule successfully.');
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

    public function getStaffByService($serviceId)
    {
        try {
            $service = Service::with('category.staff')->findOrFail($serviceId);
            $staff = $service->category->staff;
            
            return response()->json([
                'status' => 'success',
                'staff' => $staff
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch staff members'
            ], 500);
        }
    }

    public function checkAvailability(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'staff_id' => 'required|exists:staff,id',
                'appointment_date' => 'required|date|after_or_equal:today',
                'appointment_time' => 'required|date_format:H:i',
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
            $appointmentId = $request->appointment_id;

            // Check if the time slot is within business hours (9 AM to 9 PM)
            $appointmentTime = \Carbon\Carbon::createFromFormat('H:i', $time);
            $openingTime = \Carbon\Carbon::createFromFormat('H:i', '09:00');
            $closingTime = \Carbon\Carbon::createFromFormat('H:i', '21:00');

            if ($appointmentTime->lt($openingTime) || $appointmentTime->gt($closingTime)) {
                return response()->json([
                    'available' => false,
                    'message' => 'Time slot is outside business hours (9 AM - 9 PM)'
                ]);
            }

            // Check if the staff is available at this time
            $query = Appointment::where('staff_id', $staffId)
                ->where('appointment_date', $date)
                ->where('appointment_time', $time)
                ->whereIn('status', ['Accepted', 'Reschedule']);

            // Exclude current appointment if rescheduling
            if ($appointmentId) {
                $query->where('id', '!=', $appointmentId);
            }

            $isAvailable = !$query->exists();

            return response()->json([
                'available' => $isAvailable,
                'message' => $isAvailable ? 'Time slot available' : 'Time slot already booked'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error checking availability: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while checking availability'
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            $appointment->delete();
            
            return redirect()->route('cashier.appointments')
                ->with('success', 'Appointment deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete appointment. Please try again.');
        }
    }

      public function generateReceipt($id)
    {
        try {
            $appointment = Appointment::with(['service'])->findOrFail($id);
            
            // Generate unique invoice and transaction numbers
            $invoiceNumber = 'INV-' . str_pad($appointment->id, 6, '0', STR_PAD_LEFT);
            $txnNumber = 'TXN-' . str_pad($appointment->id, 6, '0', STR_PAD_LEFT);
            
            // Get salon information (you might want to store this in a settings table)
            $salonInfo = [
                'name' => 'KING SALON II',
                'address' => 'ANGATAN TABUC-TUBIG, DUMAGUETE CITY',
                'tin' => '314-007-068-00',
                'email' => 'info@kingsalon@gmail.com',
                'facebook' => 'KING SALON II'
            ];
            
            // Calculate total price from selected services
            $selectedServices = is_string($appointment->selected_services) 
                ? json_decode($appointment->selected_services, true) 
                : $appointment->selected_services;
            
            $totalPrice = collect($selectedServices)->sum('price');
            
            return view('cashier.receipt', [
                'appointment' => $appointment,
                'invoiceNumber' => $invoiceNumber,
                'txnNumber' => $txnNumber,
                'salonInfo' => $salonInfo,
                'totalPrice' => $totalPrice
            ]);
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to generate receipt. Please try again.');
        }
    }

    public function getEReceipt($id)
    {
        try {
            // Fetch the appointment with its relationships
            $appointment = Appointment::with(['service'])->findOrFail($id);
            
            // Format the date and time
            $formattedDate = \Carbon\Carbon::parse($appointment->appointment_date)->format('M d, Y');
            $formattedTime = \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A');
            
            // Process selected staff
            $selectedStaff = is_string($appointment->selected_staff) 
                ? json_decode($appointment->selected_staff, true) 
                : $appointment->selected_staff;
            
            $staffNames = [];
            if (is_array($selectedStaff)) {
                foreach ($selectedStaff as $staff) {
                    $firstName = $staff['first_name'] ?? $staff['firstName'] ?? '';
                    $lastName = $staff['last_name'] ?? $staff['lastName'] ?? '';
                    $staffNames[] = trim("$firstName $lastName");
                }
            }
            
            // Process services
            $selectedServices = is_string($appointment->selected_services) 
                ? json_decode($appointment->selected_services, true) 
                : $appointment->selected_services;
            
            $services = [];
            $totalAmount = 0;
            if (is_array($selectedServices)) {
                foreach ($selectedServices as $service) {
                    $services[] = [
                        'name' => $service['name'] ?? $service['service_name'] ?? 'Unknown Service',
                        'price' => floatval($service['price'] ?? 0)
                    ];
                    $totalAmount += floatval($service['price'] ?? 0);
                }
            }
            
            // Fetch products if any
            $products = \DB::table('appointment_products')
                ->where('appointment_id', $id)
                ->join('products', 'appointment_products.product_id', '=', 'products.id')
                ->select(
                    'products.product_name as name',
                    'appointment_products.quantity',
                    'appointment_products.price',
                    \DB::raw('CAST(appointment_products.quantity * appointment_products.price AS DECIMAL(10,2)) as total_price')
                )
                ->get()
                ->map(function ($product) {
                    $product->total_price = (float) $product->total_price;
                    return $product;
                });
            
            // Calculate total duration (assuming 1 hour per service)
            $duration = count($services) . ' hour' . (count($services) > 1 ? 's' : '');
            
            return response()->json([
                'client_name' => $appointment->full_name,
                'stylist' => implode(', ', $staffNames),
                'date' => $formattedDate,
                'time' => $formattedTime,
                'services' => $services,
                'products' => $products,
                'duration' => $duration,
                'total_amount' => $totalAmount,
                'payment_method' => $appointment->payment_method
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error generating e-receipt: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to generate receipt',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllAppointments()
    {
        try {
            $appointments = Appointment::whereIn('status', ['Pending', 'Accepted'])
                ->select('id', 'full_name', 'phone_number', 'selected_staff', 'service_name', 
                        'appointment_date', 'appointment_time', 'status')
                ->get()
                ->map(function ($appointment) {
                    // Format the date and time
                    $appointment->appointment_date = date('Y-m-d', strtotime($appointment->appointment_date));
                    $appointment->appointment_time = date('H:i', strtotime($appointment->appointment_time));
                    
                    // Handle selected_staff if it's a JSON string
                    if (is_string($appointment->selected_staff)) {
                        $appointment->selected_staff = json_decode($appointment->selected_staff, true);
                    }
                    
                    return $appointment;
                });
            
            return response()->json($appointments);
        } catch (\Exception $e) {
            \Log::error('Error fetching appointments: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching appointments'
            ], 500);
        }
    }
}  


