<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Client; 
use App\Models\Service;
use App\Models\Appointment;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Models\Note;
use App\Models\Order;
use App\Models\AppointmentProduct;
use App\Models\Staff;


class AdminController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            // Authentication passed
            return redirect()->route('admin.dashboard');
        }

        // Authentication failed
        return back()->withErrors(['login_error' => 'Invalid username or password']);
    }

    public function dashboard()
    {
        // Get all notes from managers
        $notes = Note::with('manager')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get unread notes count
        $unreadCount = Note::where('is_read', false)->count();

        // Get other dashboard data
        $clientCount = Client::count();
        $serviceCount = Service::count();
        $appointmentCount = Appointment::count();
        $upcomingAppointmentsCount = Appointment::whereDate('appointment_date', '>=', now()->toDateString())
            ->where('status', 'Pending')
            ->count();

        // Get completed appointments
        $completedAppointments = Appointment::where('status', 'Completed')->get();
        $now = now();

        // Calculate service sales
        $calculateSales = function ($appointments) {
            return $appointments->reduce(function ($total, $appointment) {
                $services = $appointment->selected_services;
                if (is_string($services)) {
                    $services = json_decode($services, true);
                }
                $serviceTotal = collect($services)->sum('price');
                return $total + $serviceTotal;
            }, 0);
        };

        // Today's Sales
        $todaysSales = $calculateSales(
            $completedAppointments->filter(function ($appointment) use ($now) {
                return $appointment->appointment_date === $now->toDateString();
            })
        );

        // Weekly Sales
        $weeklySales = $calculateSales(
            $completedAppointments->filter(function ($appointment) use ($now) {
                return $appointment->appointment_date >= $now->startOfWeek()->toDateString() &&
                    $appointment->appointment_date <= $now->endOfWeek()->toDateString();
            })
        );

        // Monthly Sales
        $monthlySales = $calculateSales(
            $completedAppointments->filter(function ($appointment) {
                $now = new \DateTime();
                $startOfMonth = new \DateTime($now->format('Y-m-01'));
                $endOfMonth = new \DateTime($now->format('Y-m-t 23:59:59'));
        
                // Special handling for January 2025
                if ($startOfMonth->format('Y-m') === '2025-01') {
                    $startOfMonth = new \DateTime('2025-01-07');
                }
        
                $appointmentDate = new \DateTime($appointment->appointment_date);
                return $appointmentDate >= $startOfMonth && $appointmentDate <= $endOfMonth;
            })
        );

        // Get product sales data
        $frontDeskSales = OrderItem::select(
            'products.product_name',
            'products.category',
            'products.brand',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.subtotal) as total_sales')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', '2025-05-09')
            ->groupBy('products.id', 'products.product_name', 'products.category', 'products.brand')
            ->orderBy('total_sales', 'desc')
            ->limit(5)
            ->get();

        $appSales = AppointmentProduct::select(
            'products.product_name',
            'products.category',
            'products.brand',
            DB::raw('SUM(appointment_products.quantity) as total_quantity'),
            DB::raw('SUM(appointment_products.subtotal) as total_sales')
        )
            ->join('products', 'appointment_products.product_id', '=', 'products.id')
            ->join('appointments', 'appointment_products.appointment_id', '=', 'appointments.id')
            ->where('appointments.status', 'Completed')
            ->groupBy('products.id', 'products.product_name', 'products.category', 'products.brand')
            ->orderBy('total_sales', 'desc')
            ->limit(5)
            ->get();

        // Calculate total sales for both channels
        $totalFrontDeskSales = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', '2025-05-09')
            ->sum('order_items.subtotal');

        $totalAppSales = AppointmentProduct::join('appointments', 'appointment_products.appointment_id', '=', 'appointments.id')
            ->where('appointments.status', 'Completed')
            ->sum('appointment_products.subtotal');

        // Get staff data grouped by categories
        $staff = Staff::with('categories')->get();
        $groupedStaff = $staff->groupBy(function($staff) {
            return $staff->categories->pluck('name')->join(', ');
        });

        // Total Sales (including both service and product sales)
        $totalServiceSales = $calculateSales($completedAppointments);
        $totalSales = $totalServiceSales + $totalFrontDeskSales + $totalAppSales;

        return view('admin.dashboard', compact(
            'notes',
            'unreadCount',
            'clientCount',
            'serviceCount',
            'appointmentCount',
            'upcomingAppointmentsCount',
            'todaysSales',
            'weeklySales',
            'monthlySales',
            'totalSales',
            'totalFrontDeskSales',
            'totalAppSales',
            'frontDeskSales',
            'appSales',
            'groupedStaff'
        ));
    }

    public function getCategorySales()
    {
        // Get completed appointments with their services
        $appointments = Appointment::where('status', 'Completed')
            ->get();

        // Initialize sales array for each category
        $categorySales = [
            1 => 0, // Hair
            2 => 0, // Nail
            3 => 0, // Waxing
            4 => 0  // Eyelash
        ];

        // Calculate sales for each category
        foreach ($appointments as $appointment) {
            $services = $appointment->selected_services;
            if (is_string($services)) {
                $services = json_decode($services, true);
            }
            
            if (is_array($services)) {
                foreach ($services as $service) {
                    if (isset($service['price']) && isset($appointment->category_id)) {
                        $categorySales[$appointment->category_id] += floatval($service['price']);
                    }
                }
            }
        }

        // Map category IDs to category names
        $categories = [
            1 => 'Hair',
            2 => 'Nail',
            3 => 'Waxing',
            4 => 'Eyelash',
        ];

        // Prepare the data for the chart
        $data = [
            'labels' => array_values($categories),
            'sales' => array_values($categorySales)
        ];

        return response()->json($data);
    }

    public function getMonthlyRevenue()
    {
        // Get today's date
        $today = new \DateTime();
    
        // Determine the start of the current month
        $startOfMonth = new \DateTime($today->format('Y-m-01'));
    
        // Find the first Monday of the month
        $startOfMonth->modify('this week monday');
        if ($startOfMonth->format('n') != $today->format('n')) {
            // If the first Monday is in the previous month, move to the next Monday
            $startOfMonth->modify('+1 week');
        }
    
        // Determine the end of the current month
        $endOfMonth = (new \DateTime($today->format('Y-m-t')))->setTime(23, 59, 59);
    
        // Fetch all completed appointments for the month
        $completedAppointments = Appointment::whereBetween('appointment_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->where('status', 'Completed')
            ->get();
    
        // Helper function to calculate sales from appointments
        $calculateSales = function ($appointments) {
            return $appointments->reduce(function ($total, $appointment) {
                $services = $appointment->selected_services;
    
                // Decode selected services if JSON
                if (is_string($services)) {
                    $services = json_decode($services, true);
                }
    
                // Ensure it's an array before summing
                $serviceTotal = collect($services)->sum('price');
                return $total + $serviceTotal;
            }, 0);
        };
    
        $revenue = [];
        $labels = [];
        $weekNumber = 1;
    
        // Iterate through weeks starting from the first Monday of the month
        $currentWeekStart = clone $startOfMonth;
        while ($currentWeekStart <= $endOfMonth) {
            // Calculate the end of the current week
            $currentWeekEnd = clone $currentWeekStart;
            $currentWeekEnd->modify('+6 days');
    
            // Adjust the end date if it exceeds the end of the month
            if ($currentWeekEnd > $endOfMonth) {
                $currentWeekEnd = $endOfMonth;
            }
    
            // Calculate weekly revenue
            $weeklyRevenue = $calculateSales(
                $completedAppointments->filter(function ($appointment) use ($currentWeekStart, $currentWeekEnd) {
                    $appointmentDate = new \DateTime($appointment->appointment_date);
                    return $appointmentDate >= $currentWeekStart && $appointmentDate <= $currentWeekEnd;
                })
            );
    
            $revenue[] = $weeklyRevenue;
            $labels[] = "Week $weekNumber";
            $weekNumber++;
    
            // Move to the next week
            $currentWeekStart->modify('+1 week');
        }
    
        return response()->json([
            'labels' => $labels,
            'revenue' => $revenue,
        ]);
    }
    

    public function getWeeklyPerformance()
    {
        // Get today's date
        $today = new \DateTime();
        
        // Determine the current week's Monday
        $startOfWeek = clone $today;
        $startOfWeek->modify('this week monday')->setTime(0, 0, 0);
    
        // Determine the current week's Sunday
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days')->setTime(23, 59, 59);
    
        // Fetch completed appointments for the week
        $completedAppointments = Appointment::whereBetween('appointment_date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->where('status', 'Completed')
            ->get();
    
        // Calculate daily performance
        $dailyPerformance = [];
        for ($i = 0; $i < 7; $i++) {
            $dayStart = (clone $startOfWeek)->modify("+{$i} days")->setTime(0, 0, 0);
            $dayEnd = (clone $dayStart)->setTime(23, 59, 59);
    
            // Filter appointments for this specific day
            $appointmentsForDay = $completedAppointments->filter(function ($appointment) use ($dayStart, $dayEnd) {
                $appointmentDate = new \DateTime($appointment->appointment_date);
                return $appointmentDate >= $dayStart && $appointmentDate <= $dayEnd;
            });
    
            // Calculate total revenue for the day (default to 0 if no appointments)
            $dailyPerformance[] = $appointmentsForDay->reduce(function ($total, $appointment) {
                $services = $appointment->selected_services;
    
                // Decode selected services if JSON
                if (is_string($services)) {
                    $services = json_decode($services, true);
                }
    
                // Sum up prices
                $serviceTotal = collect($services)->sum('price');
                return $total + $serviceTotal;
            }, 0);
        }
    
        // Return the data as JSON
        return response()->json([
            'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'performance' => $dailyPerformance,
        ]);
    }

    public function getProductSalesOverTime()
    {
        // Get sales data grouped by day for the last 7 days
        $salesData = OrderItem::select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('SUM(order_items.subtotal) as total_sales'),
                DB::raw('COUNT(DISTINCT orders.id) as transactions')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing days with 0 sales
        $dates = [];
        $sales = [];
        $transactions = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $found = $salesData->firstWhere('date', $date);
            
            $dates[] = now()->subDays($i)->format('D'); // Day name (Mon, Tue, etc.)
            $sales[] = $found ? (float)$found->total_sales : 0;
            $transactions[] = $found ? $found->transactions : 0;
        }

        return response()->json([
            'labels' => $dates,
            'sales' => $sales,
            'transactions' => $transactions
        ]);
    }

    public function getSalesComparison()
    {
        // Get the last 6 months
        $startDate = now()->startOfMonth()->subMonths(5); // Start from 6 months ago
        $endDate = now()->endOfDay();

        // Get appointment products sales
        $appointmentProducts = AppointmentProduct::select(
                DB::raw('DATE_FORMAT(appointments.appointment_date, "%Y-%m") as month'),
                DB::raw('COALESCE(SUM(appointment_products.subtotal), 0) as total_sales')
            )
            ->join('appointments', 'appointment_products.appointment_id', '=', 'appointments.id')
            ->where('appointments.status', 'Completed')
            ->whereBetween('appointments.appointment_date', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Get order items sales
        $orderItems = OrderItem::select(
                DB::raw('DATE_FORMAT(orders.created_at, "%Y-%m") as month'),
                DB::raw('COALESCE(SUM(order_items.subtotal), 0) as total_sales')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Prepare data for the last 6 months
        $labels = [];
        $appointmentData = [];
        $orderData = [];

        for ($i = 0; $i < 6; $i++) {
            $date = $startDate->copy()->addMonths($i);
            $monthKey = $date->format('Y-m');
            $labels[] = $date->format('M Y'); // Month name and year (e.g., "Jan 2024")

            // Get appointment products sales for this month
            $appointmentSale = $appointmentProducts->firstWhere('month', $monthKey);
            $appointmentData[] = $appointmentSale ? (float)$appointmentSale->total_sales : 0;

            // Get order items sales for this month
            $orderSale = $orderItems->firstWhere('month', $monthKey);
            $orderData[] = $orderSale ? (float)$orderSale->total_sales : 0;
        }

        return response()->json([
            'labels' => $labels,
            'appointmentProducts' => $appointmentData,
            'orderItems' => $orderData
        ]);
    }
    
    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect()->route('admin.login');
    }

    public function blockClient($id)
    {
        try {
            $client = Client::findOrFail($id);
            $client->is_blocked = true;
            $client->save();
            return redirect()->route('clients.index')->with('success', 'Client has been blocked successfully.');
        } catch (\Exception $e) {
            return redirect()->route('clients.index')->with('error', 'Failed to block client: ' . $e->getMessage());
        }
    }

    public function unblockClient($id)
    {
        try {
            $client = Client::findOrFail($id);
            $client->is_blocked = false;
            $client->save();
            return redirect()->route('clients.index')->with('success', 'Client has been unblocked successfully.');
        } catch (\Exception $e) {
            return redirect()->route('clients.index')->with('error', 'Failed to unblock client: ' . $e->getMessage());
        }
    }

    public function viewStaffSchedule($staffId)
    {
        $staff = Staff::with(['categories', 'schedules' => function($query) {
            $query->orderBy('day_of_week')
                  ->orderBy('start_time');
        }])->findOrFail($staffId);

        // Get all services for the staff member's categories
        $categoryIds = $staff->categories->pluck('id');
        $services = Service::whereHas('category', function($query) use ($categoryIds) {
            $query->whereIn('categories.id', $categoryIds);
        })->get();

        // Get the current week's schedule
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        // Get appointments for the current week
        $appointments = Appointment::where('staff_id', $staffId)
            ->whereBetween('appointment_date', [$startOfWeek, $endOfWeek])
            ->where('status', '!=', 'Cancelled')
            ->get()
            ->groupBy(function($appointment) {
                return \Carbon\Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            });

        // Get all staff members for reference
        $allStaff = Staff::with('categories')->get();

        // Get the staff's regular schedule
        $schedule = $staff->schedules->groupBy('day_of_week');

        // Days of the week
        $days = [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 7
        ];

        return view('admin.staff-schedule', compact(
            'staff',
            'services',
            'appointments',
            'allStaff',
            'schedule',
            'days',
            'startOfWeek',
            'endOfWeek'
        ));
    }

    public function updateStaffSchedule(Request $request, $staffId)
    {
        $staff = Staff::findOrFail($staffId);
        
        // Validate the request
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|integer|between:1,7',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.is_working_day' => 'required|boolean'
        ]);

        // Delete existing schedules
        $staff->schedules()->delete();

        // Create new schedules
        foreach ($request->schedules as $schedule) {
            $staff->schedules()->create([
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['is_working_day'] ? $schedule['start_time'] : null,
                'end_time' => $schedule['is_working_day'] ? $schedule['end_time'] : null,
                'is_working_day' => $schedule['is_working_day']
            ]);
        }

        return redirect()->back()->with('success', 'Schedule updated successfully');
    }

    public function history()
    {
        $orders = Order::with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.history', compact('orders'));
    }
}
