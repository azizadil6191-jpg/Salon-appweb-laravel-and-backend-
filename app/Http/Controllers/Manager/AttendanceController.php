<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use PDF;
use App\Models\Staff;

class AttendanceController extends Controller
{
    public function filter(Request $request)
    {
        $query = Attendance::with('staff');

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->orderBy('date', 'desc')->get();

        // Format total hours for each record
        $records->transform(function ($record) {
            if ($record->total_hours) {
                $totalMinutes = round($record->total_hours * 60);
                $hours = floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;
                $record->formatted_hours = ($hours > 0 ? $hours . 'h ' : '') . ($minutes > 0 ? $minutes . 'm' : '0m');
            } else {
                $record->formatted_hours = '-';
            }
            return $record;
        });

        return response()->json($records);
    }

    public function export(Request $request)
    {
        $query = Attendance::with('staff');

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->orderBy('date', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_report.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['Staff Name', 'Date', 'Time In', 'Time Out', 'Status', 'Total Hours']);
            
            // Add data
            foreach ($records as $record) {
                fputcsv($file, [
                    $record->staff->first_name . ' ' . $record->staff->last_name,
                    $record->date->format('M d, Y'),
                    $record->time_in ? $record->time_in->format('h:i A') : '-',
                    $record->time_out ? $record->time_out->format('h:i A') : '-',
                    ucfirst($record->status),
                    $record->formatted_hours
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function index()
    {
        $attendanceRecords = Attendance::with('staff')
            ->orderBy('date', 'desc')
            ->get();

        // Get today's attendance records for summary
        $todayAttendance = Attendance::with('staff')
            ->whereDate('date', today())
            ->get();

        // Format total hours for each record
        $attendanceRecords->transform(function ($record) {
            if ($record->total_hours) {
                $totalMinutes = round($record->total_hours * 60);
                $hours = floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;
                $record->formatted_hours = ($hours > 0 ? $hours . 'h ' : '') . ($minutes > 0 ? $minutes . 'm' : '0m');
            } else {
                $record->formatted_hours = '-';
            }
            return $record;
        });

        $staff = Staff::all();
        
        // Get IDs of staff who are present or late today
        $presentOrLateStaffIds = $todayAttendance
            ->whereIn('status', ['present', 'late'])
            ->pluck('staff_id')
            ->toArray();

        // Count attendance
        $totalPresent = $todayAttendance->where('status', 'present')->count();
        $totalLate = $todayAttendance->where('status', 'late')->count();
        
        // Count absent as all staff minus those who are present or late
        $totalAbsent = $staff->count() - count($presentOrLateStaffIds);

        // Calculate average hours for all records
        $totalHours = $attendanceRecords->where('status', 'present')->sum('total_hours');
        $presentCount = $attendanceRecords->where('status', 'present')->count();
        $averageHours = $presentCount > 0 ? number_format($totalHours / $presentCount, 2) : '0.00';

        return view('manager.attendance', compact('attendanceRecords', 'staff', 'totalPresent', 'totalAbsent', 'totalLate', 'averageHours'));
    }
} 