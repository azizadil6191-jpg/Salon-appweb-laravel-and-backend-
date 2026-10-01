<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        try {
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - No token provided'
                ], 401);
            }

            // Get the authenticated user's ID from the token
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - Invalid token'
                ], 401);
            }

            // Get appointments with all relevant statuses for the authenticated user only
            $appointments = Appointment::where('client_id', $user->id)
                ->whereIn('status', ['Pending', 'Accepted', 'Rejected', 'Reschedule'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Transform appointments into notification format
            $notifications = $appointments->map(function ($appointment) {
                // Parse services and staff
                $services = is_string($appointment->selected_services) 
                    ? json_decode($appointment->selected_services, true) 
                    : $appointment->selected_services;
                    
                $staff = is_string($appointment->selected_staff)
                    ? json_decode($appointment->selected_staff, true)
                    : $appointment->selected_staff;

                $baseNotification = [
                    'id' => $appointment->id,
                    'date' => $appointment->appointment_date,
                    'time' => $appointment->appointment_time,
                    'services' => $services,
                    'staff' => $staff,
                    'createdAt' => $appointment->created_at,
                    'updatedAt' => $appointment->updated_at,
                    'rejection_reason' => $appointment->reason,
                    'rescheduled_date' => $appointment->rescheduled_date,
                    'rescheduled_time' => $appointment->rescheduled_time,
                    'status' => $appointment->status
                ];

                // Return notification based on status
                switch ($appointment->status) {
                    case 'Rejected':
                        return [
                            ...$baseNotification,
                            'type' => 'rejected',
                            'title' => 'Appointment Declined',
                            'message' => $appointment->reason || 'Your appointment has been declined',
                            'icon' => 'times-circle',
                            'color' => '#F44336'
                        ];
                    case 'Reschedule':
                        return [
                            ...$baseNotification,
                            'type' => 'reschedule',
                            'title' => 'Appointment Rescheduled',
                            'message' => $appointment->reschedule_reason || 'Your appointment has been rescheduled',
                            'icon' => 'calendar',
                            'color' => '#FF9800'
                        ];
                    case 'Pending':
                        return [
                            ...$baseNotification,
                            'type' => 'pending',
                            'title' => 'Appointment Pending',
                            'message' => 'Your appointment is pending confirmation',
                            'icon' => 'clock-o',
                            'color' => '#2196F3'
                        ];
                    case 'Accepted':
                        return [
                            ...$baseNotification,
                            'type' => 'accepted',
                            'title' => 'Appointment Confirmed!',
                            'message' => 'Your appointment has been approved',
                            'icon' => 'check-circle',
                            'color' => '#4CAF50'
                        ];
                    default:
                        return $baseNotification;
                }
            });

            return response()->json([
                'status' => 'success',
                'notifications' => $notifications
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching notifications: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching notifications'
            ], 500);
        }
    }

    public function getUnreadCount(Request $request)
    {
        try {
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - No token provided'
                ], 401);
            }

            // Get the authenticated user's ID from the token
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - Invalid token'
                ], 401);
            }

            $lastViewedId = $request->header('Last-Viewed-Id');
            Log::info('Last viewed ID: ' . $lastViewedId);
            
            $query = Appointment::where('client_id', $user->id)
                ->whereIn('status', ['Accepted', 'Rejected', 'Reschedule']);
            
            if ($lastViewedId) {
                $query->where('id', '>', $lastViewedId);
            }
            
            $count = $query->count();
            Log::info('Unread count: ' . $count);

            return response()->json([
                'status' => 'success',
                'unreadCount' => $count
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting unread count: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get unread count'
            ], 500);
        }
    }

    public function confirmReschedule(Request $request, $appointmentId)
    {
        try {
            DB::beginTransaction();
            
            $appointment = Appointment::findOrFail($appointmentId);
            
            if ($appointment->status !== 'Reschedule') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid appointment status'
                ], 400);
            }

            // Update the appointment status to Pending
            $appointment->status = 'Pending';
            $appointment->reschedule_reason = null;
            $appointment->save();

            DB::commit();

            Log::info('Appointment status updated', [
                'appointment_id' => $appointmentId,
                'old_status' => 'Reschedule',
                'new_status' => 'Pending'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Appointment status updated to pending',
                'appointment' => $appointment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating appointment status: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update appointment status: ' . $e->getMessage()
            ], 500);
        }
    }
}