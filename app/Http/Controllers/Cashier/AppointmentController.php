<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function getReceiptData($id)
    {
        try {
            $appointment = Appointment::with(['services', 'products'])->findOrFail($id);
            
            // Format the selected staff
            $selectedStaff = is_array($appointment->selected_staff) 
                ? implode(', ', $appointment->selected_staff)
                : $appointment->selected_staff;

            // Calculate total duration
            $totalDuration = $appointment->services->sum('duration');
            $hours = floor($totalDuration / 60);
            $minutes = $totalDuration % 60;
            $durationText = '';
            if ($hours > 0) {
                $durationText .= $hours . ' hr ';
            }
            if ($minutes > 0) {
                $durationText .= $minutes . ' min';
            }

            return response()->json([
                'full_name' => $appointment->full_name,
                'selected_staff' => $selectedStaff,
                'appointment_time' => $appointment->appointment_time,
                'appointment_date' => $appointment->appointment_date,
                'total_price' => $appointment->total_price,
                'services' => $appointment->services->map(function($service) {
                    return [
                        'name' => $service->name,
                        'price' => $service->price,
                        'duration' => $service->duration
                    ];
                }),
                'products' => $appointment->products->map(function($product) {
                    return [
                        'name' => $product->name,
                        'quantity' => $product->pivot->quantity,
                        'total_price' => $product->price * $product->pivot->quantity
                    ];
                }),
                'payment_method' => $appointment->payment_method,
                'payment_logo' => $appointment->payment_method === 'GCash' ? asset('images/gcash-logo.png') : null,
                'duration' => $durationText
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching receipt data: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load receipt data'], 500);
        }
    }
} 