<?php

namespace App\Http\Controllers;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


use App\Models\Client;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Models\AppointmentProduct;

class OwnerController extends Controller
{
    public function index()
    {
        $owners = Owner::all();  // Retrieve all owners
        return view('admin.owners.index', compact('owners')); // Ensure this view exists

        
    }

    // Other methods can be added as needed
    public function create()
    {
        // Logic to show a form for creating a new owner
    }

    public function store(Request $request)
    {
           // Validate the request
           $request->validate([
            
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:owners',
            'username' => 'required|string|max:255|unique:owners', // Validate unique username
            'number' => 'required|string|max:15',
            'date_of_birth' => 'required|date',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'required|string|min:6',
        ]);
    
        // Handle profile picture upload
        $profilePicturePath = null;
        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
        }
    
        // Create a new owner instance
        $owner = new Owner();
        $owner->fullname = $request->input('fullname');
        $owner->email = $request->input('email');
        $owner->username = $request->input('username'); // Save the username
        $owner->number = $request->input('number');
        $owner->date_of_birth = $request->input('date_of_birth');
        $owner->profile_picture = $profilePicturePath;
        $owner->password = bcrypt($request->input('password')); // Hash the password
        $owner->save(); // Save the owner to the database
    
        // Redirect or return response
        return redirect()->route('owners.index')->with('success', 'Owner added successfully!');
    }



    public function showLoginForm()
    {
        return view('owner.login'); // Ensure this view exists
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required', // This should match the input name
            'password' => 'required',
        ]);
    
        // Identify if login is email or username
        $fieldType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    
        // Debugging logs
        \Log::info("Attempting login using {$fieldType}: " . $credentials['login']);
    
        if (Auth::guard('owner')->attempt([$fieldType => $credentials['login'], 'password' => $credentials['password']], $request->remember)) {
            \Log::info("Login successful for: " . $credentials['login']);
            return redirect()->route('owner.dashboard');
        }
    
        \Log::warning("Login failed for: " . $credentials['login']);
        return back()->withErrors(['login' => 'Invalid login credentials'])->withInput();
    }
    
    
    

    public function logout()
    {
        Auth::guard('owner')->logout();
        return redirect()->route('owner.login');
    }

    public function dashboard()
    {
        if (Auth::guard('owner')->check()) {
            $owner = Auth::guard('owner')->user();

            $clientCount = Client::count();
            $serviceCount = Service::count();
            $appointmentCount = Appointment::count();
            $completedAppointments = Appointment::where('status', 'Completed')->get();
            $now = now();

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


            // Calculate service sales
            $calculateSales = function ($appointments) {
                return $appointments->reduce(function ($total, $appointment) {
                    $services = $appointment->selected_services;
    
                    // Decode only if it's a JSON string
                    if (is_string($services)) {
                        $services = json_decode($services, true);
                    }
    
                    // Ensure it's an array before proceeding
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
    
            // Total Sales (including both service and product sales)
                   // Total Sales (including both service and product sales)
                   $totalServiceSales = $calculateSales($completedAppointments);
                   $totalSales = $totalServiceSales + $totalFrontDeskSales + $totalAppSales;

            return view('owner.dashboard', [
                'clientCount' => $clientCount,
                'serviceCount' => $serviceCount,
                'appointmentCount' => $appointmentCount,
                'todaysTotalSales' => $todaysSales,
                'weeklyTotalSales' => $weeklySales,
                'monthlyTotalSales' => $monthlySales,
                'totalSales' => $totalSales,
                'owner' => $owner,
                'totalFrontDeskSales' => $totalFrontDeskSales,
                'totalAppSales' => $totalAppSales,
                'frontDeskSales' => $frontDeskSales,
                'appSales' => $appSales
            ]);
        }

        return redirect()->route('owner.login');
    }

    public function getCategorySales()
    {
        $categorySales = Appointment::selectRaw('category_id, COUNT(*) as total_sales')
            ->groupBy('category_id')
            ->pluck('total_sales', 'category_id');

        $categories = [
            1 => 'Hair',
            2 => 'Nail',
            3 => 'Waxing',
            4 => 'Eyelash',
        ];

        return response()->json([
            'labels' => array_values($categories),
            'sales' => array_map(fn($id) => $categorySales[$id] ?? 0, array_keys($categories)),
        ]);
    }

    public function getMonthlyRevenue()
    {
        $today = now();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $completedAppointments = Appointment::whereBetween('appointment_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'Completed')
            ->get();

        $calculateSales = function ($appointments) {
            return $appointments->reduce(fn($total, $appointment) =>
                $total + collect(json_decode($appointment->selected_services, true) ?? [])->sum('price'), 0);
        };

        $revenue = [];
        $labels = [];
        $currentWeekStart = clone $startOfMonth;
        while ($currentWeekStart <= $endOfMonth) {
            $currentWeekEnd = clone $currentWeekStart;
            $currentWeekEnd->modify('+6 days');
            if ($currentWeekEnd > $endOfMonth) $currentWeekEnd = $endOfMonth;

            $revenue[] = $calculateSales($completedAppointments->whereBetween('appointment_date', [$currentWeekStart, $currentWeekEnd]));
            $labels[] = "Week " . count($labels) + 1;
            $currentWeekStart->modify('+1 week');
        }

        return response()->json(['labels' => $labels, 'revenue' => $revenue]);
    }

    public function getWeeklyPerformance()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $completedAppointments = Appointment::whereBetween('appointment_date', [$startOfWeek, $endOfWeek])
            ->where('status', 'Completed')
            ->get();

        $dailyPerformance = collect(range(0, 6))->map(fn($i) =>
            $completedAppointments->where('appointment_date', $startOfWeek->copy()->addDays($i)->toDateString())
                ->reduce(fn($total, $appointment) =>
                    $total + collect(json_decode($appointment->selected_services, true) ?? [])->sum('price'), 0)
        );

        return response()->json([
            'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'performance' => $dailyPerformance,
        ]);
    }

    public function edit($id)
    {
        // Logic to show a form for editing a specific owner
    }

    public function update(Request $request, $id)
    {
        // Logic to update a specific owner
    }

    public function destroy($id)
    {
        // Logic to delete a specific owner
    }

    // Add new method for product sales chart
    public function getProductSales()
    {
        $salesData = OrderItem::select(
            DB::raw('DATE(orders.created_at) as date'),
            DB::raw('SUM(order_items.subtotal) as walk_in_sales'),
            DB::raw('COUNT(DISTINCT orders.id) as walk_in_transactions')
        )
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.created_at', '>=', now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Get appointment product sales
        $appointmentProductSales = AppointmentProduct::select(
            DB::raw('DATE(appointments.appointment_date) as date'),
            DB::raw('SUM(appointment_products.subtotal) as app_sales')
        )
        ->join('appointments', 'appointment_products.appointment_id', '=', 'appointments.id')
        ->where('appointments.status', 'Completed')
        ->where('appointments.appointment_date', '>=', now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Combine the data
        $dates = [];
        $walkInSales = [];
        $appSales = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $walkInData = $salesData->firstWhere('date', $date);
            $appData = $appointmentProductSales->firstWhere('date', $date);
            
            $dates[] = now()->subDays($i)->format('D');
            $walkInSales[] = $walkInData ? (float)$walkInData->walk_in_sales : 0;
            $appSales[] = $appData ? (float)$appData->app_sales : 0;
        }

        return response()->json([
            'labels' => $dates,
            'walkInSales' => $walkInSales,
            'appSales' => $appSales
        ]);
    }

    public function staffSchedules()
    {
        if (!Auth::guard('owner')->check()) {
            return redirect()->route('owner.login');
        }

        $owner = Auth::guard('owner')->user();

        $staffSchedules = DB::table('staff')
            ->leftJoin('staff_schedules', function($join) {
                $join->on('staff.id', '=', 'staff_schedules.staff_id')
                    ->where('staff_schedules.day_of_week', '=', now()->format('l'));
            })
            ->leftJoin('appointments', function($join) {
                $join->on('staff.id', '=', 'appointments.staff_id')
                    ->where('appointments.status', '!=', 'Cancelled')
                    ->where('appointments.appointment_date', '>=', now()->toDateString());
            })
            ->leftJoin('category_staff', 'staff.id', '=', 'category_staff.staff_id')
            ->leftJoin('categories', 'category_staff.category_id', '=', 'categories.id')
            ->select(
                'staff.id',
                'staff.first_name',
                'staff.middle_name',
                'staff.last_name',
                'staff.profile_picture',
                'staff_schedules.start_time',
                'staff_schedules.end_time',
                'categories.name as position',
                DB::raw('COUNT(appointments.id) as appointment_count')
            )
            ->groupBy(
                'staff.id',
                'staff.first_name',
                'staff.middle_name',
                'staff.last_name',
                'staff.profile_picture',
                'staff_schedules.start_time',
                'staff_schedules.end_time',
                'categories.name'
            )
            ->get()
            ->map(function($staff) {
                $staff->today_schedule = (object)[
                    'start_time' => $staff->start_time,
                    'end_time' => $staff->end_time,
                    'is_available' => !$staff->appointment_count // Consider staff available if they have no appointments
                ];
                
                $staff->upcoming_appointments = DB::table('appointments')
                    ->join('clients', 'appointments.client_id', '=', 'clients.id')
                    ->where('appointments.staff_id', $staff->id)
                    ->where('appointments.status', '!=', 'Cancelled')
                    ->where('appointments.appointment_date', '>=', now()->toDateString())
                    ->select('appointments.appointment_date', 'clients.fullname as client_name')
                    ->orderBy('appointments.appointment_date')
                    ->limit(3)
                    ->get();
                
                // Combine first, middle, and last name
                $staff->name = trim($staff->first_name . ' ' . ($staff->middle_name ? $staff->middle_name . ' ' : '') . $staff->last_name);
                return $staff;
            });

        // Merge staff with multiple categories
        $mergedStaff = [];
        foreach ($staffSchedules as $staff) {
            $id = $staff->id;
            if (!isset($mergedStaff[$id])) {
                $mergedStaff[$id] = $staff;
                $mergedStaff[$id]->categories = [$staff->position];
            } else {
                if (!in_array($staff->position, $mergedStaff[$id]->categories)) {
                    $mergedStaff[$id]->categories[] = $staff->position;
                }
            }
        }
        $mergedStaff = array_values($mergedStaff);

        return view('owner.staff', ['staffSchedules' => $mergedStaff, 'owner' => $owner]);
    }

    public function inventory()
    {
        if (!Auth::guard('owner')->check()) {
            return redirect()->route('owner.login');
        }

        $owner = Auth::guard('owner')->user();
        $products = \App\Models\Product::select('id', 'product_name', 'category', 'brand', 'stocks', 'price', 'updated_at')
            ->orderBy('updated_at', 'desc')->get();

        return view('owner.inventory', compact('products', 'owner'));
    }

    public function salesReport(Request $request)
    {
        $startDate = $request->input('start_date') 
            ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('start_date'))->startOfDay()
            : \Carbon\Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date')
            ? \Carbon\Carbon::createFromFormat('Y-m-d', $request->input('end_date'))->endOfDay()
            : \Carbon\Carbon::now()->endOfMonth();

        // Front Desk Sales
        $frontDeskSales = \App\Models\OrderItem::select(
            'products.product_name',
            'products.category',
            'products.brand',
            \DB::raw('SUM(order_items.quantity) as total_quantity'),
            \DB::raw('SUM(order_items.subtotal) as total_sales'),
            'cashiers.first_name',
            'cashiers.last_name',
            \DB::raw('COUNT(DISTINCT orders.id) as order_count')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('cashiers', 'orders.cashier_id', '=', 'cashiers.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.product_name', 'products.category', 'products.brand', 'cashiers.first_name', 'cashiers.last_name')
            ->orderBy('total_sales', 'desc')
            ->get();

        // App Sales
        $appSales = \App\Models\AppointmentProduct::select(
            'products.product_name',
            'products.category',
            'products.brand',
            \DB::raw('SUM(appointment_products.quantity) as total_quantity'),
            \DB::raw('SUM(appointment_products.subtotal) as total_sales'),
            \DB::raw('COUNT(DISTINCT appointments.id) as appointment_count')
        )
            ->join('products', 'appointment_products.product_id', '=', 'products.id')
            ->join('appointments', 'appointment_products.appointment_id', '=', 'appointments.id')
            ->where('appointments.status', 'Completed')
            ->whereBetween('appointments.appointment_date', [$startDate, $endDate])
            ->groupBy('products.id', 'products.product_name', 'products.category', 'products.brand')
            ->orderBy('total_sales', 'desc')
            ->get();

        $totalFrontDeskSales = $frontDeskSales->sum('total_sales');
        $totalAppSales = $appSales->sum('total_sales');
        $totalSales = $totalFrontDeskSales + $totalAppSales;
        $totalProductsSold = $frontDeskSales->sum('total_quantity') + $appSales->sum('total_quantity');
        $averageSalePrice = $totalProductsSold > 0 ? $totalSales / $totalProductsSold : 0;

        $cashierPerformance = \App\Models\OrderItem::select(
            'cashiers.first_name',
            'cashiers.last_name',
            \DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
            \DB::raw('SUM(order_items.subtotal) as total_sales')
        )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('cashiers', 'orders.cashier_id', '=', 'cashiers.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('cashiers.id', 'cashiers.first_name', 'cashiers.last_name')
            ->orderBy('total_sales', 'desc')
            ->get();

        $owner = Auth::guard('owner')->user();
        return view('owner.sales-report', compact(
            'frontDeskSales',
            'appSales',
            'totalFrontDeskSales',
            'totalAppSales',
            'totalSales',
            'totalProductsSold',
            'averageSalePrice',
            'startDate',
            'endDate',
            'cashierPerformance',
            'owner'
        ));
    }

    public function appointmentreports(Request $request)
    {
        // Get the authenticated owner
        $owner = Auth::guard('owner')->user();

        // Get all active staff members for the filter dropdown
        $staff = \App\Models\Staff::where('is_active', true)->get();

        $query = \App\Models\Appointment::with(['staff'])
            ->where('status', 'Completed')
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        // Apply staff filter if provided
        if ($request->filled('staff_filter')) {
            $staffId = $request->staff_filter;
            $query->where(function($q) use ($staffId) {
                $q->where('staff_id', $staffId)
                  ->orWhereJsonContains('staff_ids', $staffId);
            });
        }

        // Apply date range filters if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            try {
                // Convert MM/DD/YYYY to YYYY-MM-DD for database query
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->start_date)->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', $request->end_date)->endOfDay();
                
                $query->whereBetween('appointment_date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                ]);
            } catch (\Exception $e) {
                // If date conversion fails, log the error and continue without date filter
                \Log::error('Date conversion error: ' . $e->getMessage());
            }
        }
        // Apply date filters if provided
        else if ($request->has('date_filter')) {
            $today = \Carbon\Carbon::today();
            
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('appointment_date', $today);
                    break;
                case 'this_week':
                    $query->whereBetween('appointment_date', [
                        $today->startOfWeek(),
                        $today->endOfWeek()
                    ]);
                    break;
                case 'this_month':
                    $query->whereMonth('appointment_date', $today->month)
                          ->whereYear('appointment_date', $today->year);
                    break;
            }
        }

        // Apply search filter if provided
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('service_name', 'like', "%{$search}%")
                  ->orWhereHas('staff', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $appointments = $query->get();

        // Calculate summary statistics
        $totalRevenue = $appointments->sum(function($appointment) {
            $services = is_string($appointment->selected_services) 
                ? json_decode($appointment->selected_services, true) 
                : $appointment->selected_services;
            return collect($services)->sum('price');
        });

        $totalAppointments = $appointments->count();
        $averageRevenue = $totalAppointments > 0 ? $totalRevenue / $totalAppointments : 0;

        return view('owner.appointmentreports', compact('appointments', 'totalRevenue', 'totalAppointments', 'averageRevenue', 'staff', 'owner'));
    }
}

