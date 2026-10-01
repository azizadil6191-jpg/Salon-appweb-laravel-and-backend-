<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Manager;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\StaffSchedule;
use Illuminate\Support\Facades\DB;
use App\Models\DayOffRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\AppointmentProduct;
use App\Models\OrderItem;
use App\Models\Note;

class ManagerController extends Controller
{
    public function index()
    {
        $managers = Manager::all();  // Retrieve all managers
        return view('admin.managers.index', compact('managers')); // Ensure this view exists
    }

    public function store(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:owners',
            'nickname' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'dateofbirth' => 'required|date',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'required|string|min:6',
        ]);
    
        // Handle profile picture upload
        $profilePicturePath = null;
        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
        }
    
        // Create a new manager instance
        $manager = new Manager(); // Ensure this reflects the correct model
        $manager->fullname = $request->input('fullname');
        $manager->email = $request->input('email');
        $manager->nickname= $request->input('nickname');
        $manager->phone = $request->input('phone');
        $manager->dateofbirth = $request->input('dateofbirth');
        $manager->profile_picture = $profilePicturePath;
        $manager->password = bcrypt($request->input('password')); // Hash the password
        $manager->save();
    
        return redirect()->route('managers.index')->with('success', 'Manager added successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:managers,email,' . $id,
            'nickname' => 'nullable|string|max:255',
            'phone' => 'required|string|max:15',
            'dateofbirth' => 'required|date',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $manager = Manager::findOrFail($id);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($manager->profile_picture) {
                Storage::disk('public')->delete($manager->profile_picture);
            }
            $profilePicturePath = $request->file('profile_picture')->store('profile_pictures', 'public');
            $manager->profile_picture = $profilePicturePath;
        }

        // Update manager details
        $manager->fullname = $request->input('fullname');
        $manager->email = $request->input('email');
        $manager->nickname = $request->input('nickname');
        $manager->phone = $request->input('phone');
        $manager->dateofbirth = $request->input('dateofbirth');
        $manager->save();

        return redirect()->route('managers.index')->with('success', 'Manager updated successfully!');
    }

    // Authentication Methods
    public function showLoginForm()
    {
        return view('manager.login'); // Ensure this view exists
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);
    
        if (Auth::guard('manager')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/manager/dashboard');
        }
    
        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ]);
    }
    
    public function dashboard()
    {
        if (Auth::guard('manager')->check()) {
            $manager = Auth::guard('manager')->user();
            $notes = $manager->notes()->orderBy('created_at', 'desc')->get();

            $clientCount = Client::count();
            $serviceCount = Service::count();
            $appointmentCount = Appointment::where('status', 'Completed')->count();
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

            // Get staff data grouped by categories
            $staff = Staff::with('categories')->get();
            $groupedStaff = $staff->groupBy(function($staff) {
                return $staff->categories->pluck('name')->join(', ');
            });
    
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
                    $startOfMonth = new \DateTime($now->format('Y-m-01')); // Start of the current month
                    $endOfMonth = new \DateTime($now->format('Y-m-t 23:59:59')); // End of the current month
            
                    // Ensure January 2025 starts from the 7th
                    if ($startOfMonth->format('Y-m') === '2025-01') {
                        $startOfMonth = new \DateTime('2025-01-07');
                    }
            
                    $appointmentDate = new \DateTime($appointment->appointment_date);
            
                    // Check if the appointment date is within the start and end of the month
                    return $appointmentDate >= $startOfMonth && $appointmentDate <= $endOfMonth;
                })
            );
    
            // Total Sales (including both service and product sales)
            $totalServiceSales = $calculateSales($completedAppointments);
            $totalSales = $totalServiceSales + $totalFrontDeskSales + $totalAppSales;
            
            // Get unread notes count
            $unreadCount = Note::where('is_read', false)->count();
            
            // Pass all variables to the view
            return view(
                'manager.dashboard', 
                compact(
                    'clientCount', 
                    'serviceCount',
                    'appointmentCount',
                    'todaysSales',
                    'weeklySales',
                    'monthlySales',
                    'totalSales',
                    'manager',
                    'groupedStaff',
                    'frontDeskSales',
                    'appSales',
                    'totalFrontDeskSales',
                    'totalAppSales',
                    'notes',
                    'unreadCount'
                )
            );
        }

        // Redirect to login if not authenticated
        return redirect()->route('manager.login');
    }
    public function getCategorySales()
    {
        // Query appointments to get total sales grouped by category
        $categorySales = Appointment::selectRaw('category_id, COUNT(*) as total_sales')
            ->groupBy('category_id')
            ->pluck('total_sales', 'category_id'); // Result: [1 => 15, 2 => 10, ...]
    
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
            'sales' => array_map(function ($id) use ($categorySales) {
                return $categorySales[$id] ?? 0; // Default to 0 if no data
            }, array_keys($categories)),
        ];
    
        return response()->json($data); // Return data in JSON format
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

    public function getSalesComparison()
    {
        // Get the last 6 months
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('M Y'));
        }

        // Get appointment product sales for each month
        $appointmentProducts = $months->map(function ($month) {
            $date = Carbon::createFromFormat('M Y', $month);
            return AppointmentProduct::join('appointments', 'appointment_products.appointment_id', '=', 'appointments.id')
                ->whereMonth('appointments.appointment_date', $date->month)
                ->whereYear('appointments.appointment_date', $date->year)
                ->where('appointments.status', 'Completed')
                ->sum('appointment_products.subtotal');
        })->values();

        // Get front desk sales for each month
        $orderItems = $months->map(function ($month) {
            $date = Carbon::createFromFormat('M Y', $month);
            return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereMonth('orders.created_at', $date->month)
                ->whereYear('orders.created_at', $date->year)
                ->sum('order_items.subtotal');
        })->values();

        return response()->json([
            'labels' => $months->values(),
            'appointmentProducts' => $appointmentProducts,
            'orderItems' => $orderItems
        ]);
    }

    public function logout()
    {
        Auth::guard('manager')->logout();
        return redirect()->route('manager.login');
    }
    
    public function schedule()
    {
        $staff = Staff::with('categories')->get();
        $groupedStaff = $staff->groupBy(function($staff) {
            return $staff->categories->pluck('name')->join(', ');
        });
        return view('manager.schedule', compact('staff', 'groupedStaff'));
    }


    public function storeSchedule(Request $request)
    {
        \Log::info('Schedule submission received:', $request->all());
    
        $validatedData = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'schedules' => 'required|array|size:7',
            'schedules.*.day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'schedules.*.start_time' => 'nullable|date_format:H:i',
            'schedules.*.end_time' => 'nullable|date_format:H:i',
        ]);
    
        try {
            $staff = Staff::with('categories')->findOrFail($request->staff_id);
            \Log::info('Found staff member:', ['staff' => $staff->toArray()]);
            
            $staffCategories = $staff->categories->pluck('id');
            \Log::info('Staff categories:', ['categories' => $staffCategories->toArray()]);
    
            $conflicts = [];
    
            foreach ($request->schedules as $schedule) {
                $day = $schedule['day_of_week'];
                $isWorking = filter_var($schedule['is_working_day'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
                if (!$isWorking) {
                    $staffWithDayOff = Staff::whereHas('categories', function ($query) use ($staffCategories) {
                        $query->whereIn('categories.id', $staffCategories);
                    })
                    ->whereHas('schedules', function ($query) use ($day) {
                        $query->where('day_of_week', $day)
                              ->where('is_working_day', false);
                    })
                    ->count();
    
                    $totalStaffInCategory = Staff::whereHas('categories', function ($query) use ($staffCategories) {
                        $query->whereIn('categories.id', $staffCategories);
                    })->count();
    
                    if ($totalStaffInCategory > 0 && ($staffWithDayOff / $totalStaffInCategory) > 0.5) {
                        $conflicts[] = "More than 50% of staff in the same categories already have {$day} off.";
                    }
                }
            }
    
            if (!empty($conflicts)) {
                \Log::warning('Schedule conflicts detected:', $conflicts);
                return back()->withInput()->withErrors(['schedules' => $conflicts]);
            }
    
            DB::beginTransaction();
    
            StaffSchedule::where('staff_id', $request->staff_id)->delete();
    
            foreach ($request->schedules as $schedule) {
                $isWorking = filter_var($schedule['is_working_day'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $startTime = null;
                $endTime = null;
    
                if ($isWorking) {
                    $startTime = !empty($schedule['start_time']) ? date('H:i:s', strtotime($schedule['start_time'])) : null;
                    $endTime = !empty($schedule['end_time']) ? date('H:i:s', strtotime($schedule['end_time'])) : null;
    
                    if ($startTime && $endTime && $startTime >= $endTime) {
                        throw new \Exception("End time must be after start time for {$schedule['day_of_week']}");
                    }
                }
    
                StaffSchedule::create([
                    'staff_id' => $request->staff_id,
                    'day_of_week' => $schedule['day_of_week'],
                    'is_working_day' => $isWorking,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);
            }
    
            DB::commit();
            \Log::info('Successfully saved all schedules');
            return redirect()->route('manager.dashboard')->with('success', 'Staff schedule has been updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving schedule:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->withErrors(['error' => 'An error occurred while saving the schedule. Please try again.']);
        }
    }
    
    public function checkDayOffConflicts($staffId, $day)
    {
        $staff = Staff::findOrFail($staffId);
        $staffCategories = $staff->categories->pluck('id');

        // Get count of staff with the same categories who have this day off
        $staffWithDayOff = Staff::whereHas('categories', function ($query) use ($staffCategories) {
            $query->whereIn('categories.id', $staffCategories);
        })
        ->whereHas('schedules', function ($query) use ($day) {
            $query->where('day_of_week', $day)
                  ->where('is_working_day', false);
        })
        ->count();

        // If more than 50% of staff in the same categories have this day off, suggest alternative
        $totalStaffInCategory = Staff::whereHas('categories', function ($query) use ($staffCategories) {
            $query->whereIn('categories.id', $staffCategories);
        })->count();

        $hasConflict = ($staffWithDayOff / $totalStaffInCategory) > 0.5;
        $alternativeDay = $day === 'Tuesday' ? 'Wednesday' : 'Tuesday';

        return response()->json([
            'hasConflict' => $hasConflict,
            'alternativeDay' => $alternativeDay
        ]);
    }

    public function dayOffRequests()
    {
        $requests = DayOffRequest::with('staff')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('manager.day_off_requests', compact('requests'));
    }

    public function updateDayOffRequest(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,denied'
        ]);

        $dayOffRequest = DayOffRequest::findOrFail($id);
        $dayOffRequest->status = $request->status;
        $dayOffRequest->save();

        // If approved, update the staff schedule
        if ($request->status === 'approved') {
            $staffSchedule = StaffSchedule::where('staff_id', $dayOffRequest->staff_id)
                ->where('day_of_week', $dayOffRequest->requested_day)
                ->first();

            if ($staffSchedule) {
                $staffSchedule->update([
                    'is_working_day' => false,
                    'start_time' => null,
                    'end_time' => null
                ]);
            }
        }

        return redirect()->route('manager.day-off-requests')
            ->with('success', 'Day off request has been ' . $request->status);
    }

    public function viewWeeklySchedule()
    {
        // Get all staff with their schedules and categories
        $staff = Staff::with(['schedules', 'categories'])->get();
        $groupedStaff = $staff->groupBy(function($staff) {
            return $staff->categories->pluck('name')->join(', ');
        });

        // Define days of the week in order
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return view('manager.weekly_schedule', compact('groupedStaff', 'daysOfWeek'));
    }

    public function inventory()
    {
        // Get all products from the database
        $products = Product::all(); // Assuming you have a Product model
        
        // Get the authenticated manager
        $manager = auth()->user();
        
        return view('manager.inventory', compact('products', 'manager'));
    }

    // Product Management Methods
    public function storeProduct(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'category' => 'required|string|in:Retail,Consumable',
            'brand' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stocks' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $data = $request->except('image');

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $data['image'] = $imagePath;
            }

            Product::create($data);

            return redirect()->route('manager.inventory')
                ->with('success', 'Product added successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to add product: ' . $e->getMessage()]);
        }
    }

    public function editProduct($id)
    {
        try {
            $product = Product::findOrFail($id);
            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Product not found'], 404);
        }
    }

    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'category' => 'required|string|in:Retail,Consumable',
            'brand' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stocks' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $product = Product::findOrFail($id);
            $data = $request->except('image');

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $imagePath = $request->file('image')->store('products', 'public');
                $data['image'] = $imagePath;
            }

            $product->update($data);

            return redirect()->route('manager.inventory')
                ->with('success', 'Product updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    public function deleteProduct($id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // Delete product image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            
            $product->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete product'], 500);
        }
    }

    private function markAbsentForDate($date)
    {
        // Get all staff members
        $staff = Staff::all();
        
        foreach ($staff as $member) {
            // Check if there's already an attendance record for this staff member on this date
            $existingRecord = Attendance::where('staff_id', $member->id)
                ->whereDate('date', $date)
                ->first();
                
            // If no record exists, create an absent record
            if (!$existingRecord) {
                Attendance::create([
                    'staff_id' => $member->id,
                    'date' => $date,
                    'status' => 'absent',
                    'time_in' => null,
                    'time_out' => null,
                    'total_hours' => null
                ]);
            }
        }
    }

    private function backfillMissingAttendance($startDate, $endDate)
    {
        // Get all active staff members
        $staff = Staff::where('is_active', true)->get();
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        while ($currentDate <= $endDate) {
            foreach ($staff as $member) {
                // Check if there's already an attendance record for this staff member on this date
                $existingRecord = Attendance::where('staff_id', $member->id)
                    ->whereDate('date', $currentDate)
                    ->first();
                    
                // If no record exists, create an absent record
                if (!$existingRecord) {
                    Attendance::create([
                        'staff_id' => $member->id,
                        'date' => $currentDate->format('Y-m-d'),
                        'status' => 'absent',
                        'time_in' => null,
                        'time_out' => null,
                        'total_hours' => null
                    ]);
                }
            }
            $currentDate->addDay();
        }
    }

    private function ensureAttendanceRecords($startDate, $endDate)
    {
        $staff = Staff::all();
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        while ($currentDate <= $endDate) {
            foreach ($staff as $member) {
                // Check if record exists for this staff member on this date
                $record = Attendance::where('staff_id', $member->id)
                    ->whereDate('date', $currentDate)
                    ->first();

                // If no record exists, create an absent record
                if (!$record) {
                    DB::table('attendances')->insert([
                        'staff_id' => $member->id,
                        'date' => $currentDate->format('Y-m-d'),
                        'time_in' => null,
                        'time_out' => null,
                        'status' => 'absent',
                        'total_hours' => null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            $currentDate->addDay();
        }
    }

    public function attendance()
    {
        $today = Carbon::today();
        
        // Get all active staff members
        $staff = Staff::where('is_active', true)->get();
        
        // Mark staff as absent for today if they haven't clocked in
        foreach ($staff as $member) {
            $existingRecord = Attendance::where('staff_id', $member->id)
                ->whereDate('date', $today)
                ->first();
                
            if (!$existingRecord) {
                Attendance::create([
                    'staff_id' => $member->id,
                    'date' => $today,
                    'status' => 'absent',
                    'time_in' => null,
                    'time_out' => null,
                    'total_hours' => null
                ]);
            }
        }
        
        // Get today's attendance records with staff information
        $todayAttendance = Attendance::with(['staff' => function($query) {
                $query->select('id', 'first_name', 'last_name');
            }])
            ->whereDate('date', $today)
            ->get();
            
        // Calculate summary statistics
        $totalPresent = $todayAttendance->where('status', 'present')->count();
        $totalAbsent = $todayAttendance->where('status', 'absent')->count();
        $totalLate = $todayAttendance->where('status', 'late')->count();
        
        // Calculate average hours for the current month
        $monthlyAttendance = Attendance::whereMonth('date', $today->month)
            ->whereYear('date', $today->year)
            ->whereNotNull('total_hours')
            ->get();
            
        $averageHours = $monthlyAttendance->avg('total_hours') ?? 0;
        
        // Get all attendance records for the current month with staff information
        $attendanceRecords = Attendance::with(['staff' => function($query) {
                $query->select('id', 'first_name', 'last_name');
            }])
            ->whereMonth('date', $today->month)
            ->whereYear('date', $today->year)
            ->orderBy('date', 'desc')
            ->orderBy('time_in', 'desc')
            ->get()
            ->map(function ($record) {
                // Format the hours for display
                $record->formatted_hours = $record->total_hours ? 
                    number_format($record->total_hours, 2) . ' hrs' : '-';
                return $record;
            });
            
        return view('manager.attendance', compact(
            'staff',
            'attendanceRecords',
            'totalPresent',
            'totalAbsent',
            'totalLate',
            'averageHours'
        ));
    }

    public function filterAttendance(Request $request)
    {
        $query = Attendance::with('staff');
        
        if ($request->staff_id) {
            $query->where('staff_id', $request->staff_id);
        }
        
        if ($request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        $records = $query->orderBy('date', 'desc')
            ->orderBy('time_in', 'desc')
            ->get();
            
        return response()->json($records);
    }

    public function exportAttendance(Request $request)
    {
        $query = Attendance::with('staff');
        
        if ($request->staff_id) {
            $query->where('staff_id', $request->staff_id);
        }
        
        if ($request->start_date) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->end_date) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        $records = $query->orderBy('date', 'desc')
            ->orderBy('time_in', 'desc')
            ->get();
            
        $headers = [
            'Staff Name',
            'Date',
            'Time In',
            'Time Out',
            'Status',
            'Total Hours'
        ];
        
        $rows = $records->map(function ($record) {
            return [
                $record->staff->first_name . ' ' . $record->staff->last_name,
                $record->date->format('M d, Y'),
                $record->time_in ? $record->time_in->format('h:i A') : '-',
                $record->time_out ? $record->time_out->format('h:i A') : '-',
                ucfirst($record->status),
                $record->total_hours ?? '-'
            ];
        });
        
        $filename = 'attendance_' . date('Y-m-d') . '.csv';
        
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function history(Request $request)
    {
        $query = Order::with(['items.product'])
            ->orderBy('created_at', 'desc'); // Removed the limit(10)
        
        // Date filtering
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('created_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59'
            ]);
        }
        
        $orders = $query->get();
        
        return view('manager.history', compact('orders'));
    }

    public function getAllAppointments()
    {
        $appointments = Appointment::with(['primary_staff', 'all_staff'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'full_name' => $appointment->full_name,
                    'phone_number' => $appointment->phone_number,
                    'service_name' => $appointment->service_name,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'status' => $appointment->status,
                    'selected_staff' => $appointment->primary_staff ? [
                        'id' => $appointment->primary_staff->id,
                        'first_name' => $appointment->primary_staff->first_name,
                        'last_name' => $appointment->primary_staff->last_name
                    ] : ($appointment->all_staff ? $appointment->all_staff->map(function ($staff) {
                        return [
                            'id' => $staff->id,
                            'first_name' => $staff->first_name,
                            'last_name' => $staff->last_name
                        ];
                    })->toArray() : null)
                ];
            });

        return response()->json($appointments);
    }

    public function storeNote(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        auth()->guard('manager')->user()->notes()->create([
            'message' => $request->message
        ]);

        return redirect()->back()->with('success', 'Message sent to admin successfully!');
    }

    public function report(Request $request)
    {
        // Get all active staff members for the filter dropdown
        $staff = Staff::where('is_active', true)->get();

        $query = Appointment::with(['staff'])
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
                $startDate = Carbon::createFromFormat('m/d/Y', $request->start_date)->startOfDay();
                $endDate = Carbon::createFromFormat('m/d/Y', $request->end_date)->endOfDay();
                
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
            $today = Carbon::today();
            
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

        return view('manager.report', compact('appointments', 'totalRevenue', 'totalAppointments', 'averageRevenue', 'staff'));
    }

    public function productSalesReport(Request $request)
    {
        // Get date range from request or default to current month
        $startDate = $request->input('start_date') 
            ? Carbon::createFromFormat('Y-m-d', $request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();
            
        $endDate = $request->input('end_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

        // Get front desk sales with cashier information
        $frontDeskSales = OrderItem::select(
            'products.product_name',
            'products.category',
            'products.brand',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.subtotal) as total_sales'),
            'cashiers.first_name',
            'cashiers.last_name',
            DB::raw('COUNT(DISTINCT orders.id) as order_count')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('cashiers', 'orders.cashier_id', '=', 'cashiers.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.product_name', 'products.category', 'products.brand', 'cashiers.first_name', 'cashiers.last_name')
            ->orderBy('total_sales', 'desc')
            ->get();

        // Get appointment sales
        $appSales = AppointmentProduct::select(
            'products.product_name',
            'products.category',
            'products.brand',
            DB::raw('SUM(appointment_products.quantity) as total_quantity'),
            DB::raw('SUM(appointment_products.subtotal) as total_sales'),
            DB::raw('COUNT(DISTINCT appointments.id) as appointment_count')
        )
            ->join('products', 'appointment_products.product_id', '=', 'products.id')
            ->join('appointments', 'appointment_products.appointment_id', '=', 'appointments.id')
            ->where('appointments.status', 'Completed')
            ->whereBetween('appointments.appointment_date', [$startDate, $endDate])
            ->groupBy('products.id', 'products.product_name', 'products.category', 'products.brand')
            ->orderBy('total_sales', 'desc')
            ->get();

        // Calculate totals
        $totalFrontDeskSales = $frontDeskSales->sum('total_sales');
        $totalAppSales = $appSales->sum('total_sales');
        $totalSales = $totalFrontDeskSales + $totalAppSales;
        $totalProductsSold = $frontDeskSales->sum('total_quantity') + $appSales->sum('total_quantity');

        // Calculate summary statistics
        $averageSalePrice = $totalProductsSold > 0 ? $totalSales / $totalProductsSold : 0;

        // Get cashier performance summary
        $cashierPerformance = OrderItem::select(
            'cashiers.first_name',
            'cashiers.last_name',
            DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
            DB::raw('SUM(order_items.subtotal) as total_sales')
        )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('cashiers', 'orders.cashier_id', '=', 'cashiers.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('cashiers.id', 'cashiers.first_name', 'cashiers.last_name')
            ->orderBy('total_sales', 'desc')
            ->get();

        return view('manager.product_sales_report', compact(
            'frontDeskSales',
            'appSales',
            'totalFrontDeskSales',
            'totalAppSales',
            'totalSales',
            'totalProductsSold',
            'averageSalePrice',
            'startDate',
            'endDate',
            'cashierPerformance'
        ));
    }

    public function exportProductSalesCSV(Request $request)
    {
        // Get date range from request or default to current month
        $startDate = $request->input('start_date') 
            ? Carbon::createFromFormat('Y-m-d', $request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();
            
        $endDate = $request->input('end_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

        // Get front desk sales with cashier information
        $frontDeskSales = OrderItem::select(
            'products.product_name',
            'products.category',
            'products.brand',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.subtotal) as total_sales'),
            'cashiers.first_name',
            'cashiers.last_name',
            DB::raw('COUNT(DISTINCT orders.id) as order_count')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('cashiers', 'orders.cashier_id', '=', 'cashiers.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.product_name', 'products.category', 'products.brand', 'cashiers.first_name', 'cashiers.last_name')
            ->orderBy('total_sales', 'desc')
            ->get();

        // Get appointment sales
        $appSales = AppointmentProduct::select(
            'products.product_name',
            'products.category',
            'products.brand',
            DB::raw('SUM(appointment_products.quantity) as total_quantity'),
            DB::raw('SUM(appointment_products.subtotal) as total_sales'),
            DB::raw('COUNT(DISTINCT appointments.id) as appointment_count')
        )
            ->join('products', 'appointment_products.product_id', '=', 'products.id')
            ->join('appointments', 'appointment_products.appointment_id', '=', 'appointments.id')
            ->where('appointments.status', 'Completed')
            ->whereBetween('appointments.appointment_date', [$startDate, $endDate])
            ->groupBy('products.id', 'products.product_name', 'products.category', 'products.brand')
            ->orderBy('total_sales', 'desc')
            ->get();

        // Get cashier performance
        $cashierPerformance = OrderItem::select(
            'cashiers.first_name',
            'cashiers.last_name',
            DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
            DB::raw('SUM(order_items.subtotal) as total_sales')
        )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('cashiers', 'orders.cashier_id', '=', 'cashiers.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('cashiers.id', 'cashiers.first_name', 'cashiers.last_name')
            ->orderBy('total_sales', 'desc')
            ->get();

        // Calculate totals
        $totalFrontDeskSales = $frontDeskSales->sum('total_sales');
        $totalAppSales = $appSales->sum('total_sales');
        $totalSales = $totalFrontDeskSales + $totalAppSales;
        $totalProductsSold = $frontDeskSales->sum('total_quantity') + $appSales->sum('total_quantity');

        // Create CSV content
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product-sales-report.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($frontDeskSales, $appSales, $cashierPerformance, $totalSales, $totalFrontDeskSales, $totalAppSales, $totalProductsSold, $startDate, $endDate) {
            $file = fopen('php://output', 'w');

            // Add report header
            fputcsv($file, ['Product Sales Report']);
            fputcsv($file, ['Date Range:', $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')]);
            fputcsv($file, []);

            // Add summary
            fputcsv($file, ['Summary']);
            fputcsv($file, ['Total Sales', number_format($totalSales, 2)]);
            fputcsv($file, ['Front Desk Sales', number_format($totalFrontDeskSales, 2)]);
            fputcsv($file, ['App Sales', number_format($totalAppSales, 2)]);
            fputcsv($file, ['Total Products Sold', number_format($totalProductsSold)]);
            fputcsv($file, []);

            // Add Cashier Performance
            fputcsv($file, ['Cashier Performance']);
            fputcsv($file, ['Cashier Name', 'Total Orders', 'Total Sales', 'Average Sale per Order']);
            foreach ($cashierPerformance as $cashier) {
                fputcsv($file, [
                    $cashier->first_name . ' ' . $cashier->last_name,
                    $cashier->total_orders,
                    number_format($cashier->total_sales, 2),
                    number_format($cashier->total_sales / $cashier->total_orders, 2)
                ]);
            }
            fputcsv($file, []);

            // Add Front Desk Sales
            fputcsv($file, ['Front Desk Sales']);
            fputcsv($file, ['Product Name', 'Category', 'Brand', 'Quantity Sold', 'Total Sales', 'Processed By', 'Orders']);
            foreach ($frontDeskSales as $sale) {
                fputcsv($file, [
                    $sale->product_name,
                    $sale->category,
                    $sale->brand,
                    $sale->total_quantity,
                    number_format($sale->total_sales, 2),
                    $sale->first_name . ' ' . $sale->last_name,
                    $sale->order_count
                ]);
            }
            fputcsv($file, []);

            // Add App Sales
            fputcsv($file, ['Appointment Sales']);
            fputcsv($file, ['Product Name', 'Category', 'Brand', 'Quantity Sold', 'Total Sales', 'Appointments']);
            foreach ($appSales as $sale) {
                fputcsv($file, [
                    $sale->product_name,
                    $sale->category,
                    $sale->brand,
                    $sale->total_quantity,
                    number_format($sale->total_sales, 2),
                    $sale->appointment_count
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function inventoryReport(Request $request)
    {
        $query = Product::where('category', 'Consumable');
        
        // Get date range from request
        $startDate = $request->input('start_date') 
            ? Carbon::createFromFormat('Y-m-d', $request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();
            
        $endDate = $request->input('end_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();
        
        $products = $query->get();
        $totalProducts = $products->count();
        $totalValue = $products->sum(function($product) {
            return $product->price * $product->stocks;
        });
        
        // Calculate consumption statistics with date filtering
        $consumptionStats = $products->map(function($product) use ($startDate, $endDate) {
            $originalStock = 50; // Original stock level
            $currentStock = $product->stocks;
            $consumed = $originalStock - $currentStock;
            $consumptionPercentage = ($consumed / $originalStock) * 100;
            
            // Get consumable records within date range
            $consumableRecords = \App\Models\Consumable::where('product_id', $product->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate total consumed in date range
            $consumedInRange = $consumableRecords->sum('quantity_used');
            
            // Get last usage date
            $lastUsageDate = $consumableRecords->first() ? $consumableRecords->first()->created_at : null;
            
            return [
                'product' => $product,
                'original_stock' => $originalStock,
                'current_stock' => $currentStock,
                'consumed' => $consumed,
                'consumed_in_range' => $consumedInRange,
                'consumption_percentage' => $consumptionPercentage,
                'last_usage_date' => $lastUsageDate,
                'usage_records' => $consumableRecords
            ];
        });
        
        // Get low stock products (less than 10 remaining)
        $lowStockProducts = $consumptionStats->filter(function($stat) {
            return $stat['current_stock'] < 10;
        });
        
        // Get high consumption products (more than 70% consumed)
        $highConsumptionProducts = $consumptionStats->filter(function($stat) {
            return $stat['consumption_percentage'] > 70;
        });
        
        // Get monthly consumption statistics
        $monthlyStats = Product::where('category', 'Consumable')
            ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count, SUM(price * stocks) as total_value')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'month' => date('F Y', mktime(0, 0, 0, $item->month, 1, $item->year)),
                    'count' => $item->count,
                    'total_value' => $item->total_value
                ];
            });
        
        return view('manager.inventory_report', compact(
            'products',
            'totalProducts',
            'totalValue',
            'lowStockProducts',
            'highConsumptionProducts',
            'consumptionStats',
            'monthlyStats',
            'startDate',
            'endDate'
        ));
    }

    public function recordConsumable(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity_used' => 'required|integer|min:1'
            ]);

            // Create record in consumables table
            $consumable = new \App\Models\Consumable();
            $consumable->product_id = $request->product_id;
            $consumable->quantity_used = $request->quantity_used;
            $consumable->save();

            return response()->json([
                'success' => true,
                'message' => 'Consumable usage recorded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record consumable usage: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getConsumableHistory(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id'
            ]);

            $history = \App\Models\Consumable::where('product_id', $request->product_id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($history);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch consumable history: ' . $e->getMessage()
            ], 500);
        }
    }

    public function servicesReport(Request $request)
    {
        // Get all services grouped by category
        $services = Service::select(
            'services.service_name',
            'services.category_id',
            'categories.name as category_name',
            DB::raw('COUNT(DISTINCT appointments.id) as total_bookings'),
            DB::raw('SUM(CASE WHEN appointments.status = "Completed" THEN 1 ELSE 0 END) as completed_bookings'),
            DB::raw('SUM(CASE WHEN appointments.status = "Completed" THEN 
                CASE 
                    WHEN services.category_id = 3 THEN -- Waxing services
                        COALESCE(services.price_men, services.price_women, services.price)
                    ELSE services.price
                END
            ELSE 0 END) as total_revenue')
        )
        ->leftJoin('categories', 'services.category_id', '=', 'categories.id')
        ->leftJoin('appointments', function($join) {
            $join->on(function($query) {
                $query->on('appointments.service_id', '=', 'services.id')
                      ->orWhereRaw('JSON_CONTAINS(appointments.selected_services, JSON_OBJECT("id", services.id))')
                      ->orWhere('appointments.service_name', 'LIKE', DB::raw('CONCAT("%", services.service_name, "%")'));
            });
        })
        ->when($request->filled('category'), function($query) use ($request) {
            return $query->where('categories.id', $request->category);
        })
        ->when($request->filled(['start_date', 'end_date']), function($query) use ($request) {
            return $query->whereBetween('appointments.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        })
        ->groupBy('services.service_name', 'services.category_id', 'categories.name')
        ->get()
        ->map(function($service) {
            // Ensure numeric values
            $service->total_bookings = (int)$service->total_bookings;
            $service->completed_bookings = (int)$service->completed_bookings;
            $service->total_revenue = (float)$service->total_revenue;
            return $service;
        })
        ->groupBy('category_name');

        // Get all categories for the filter
        $categories = \App\Models\Category::all();

        // Calculate summary statistics
        $completedAppointments = Appointment::query()
            ->when($request->filled(['start_date', 'end_date']), function($query) use ($request) {
                return $query->whereBetween('created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            })
            ->where('status', 'Completed')
            ->get();
        
        $summary = [
            'total_services' => Service::select('service_name')->distinct()->count(),
            'total_bookings' => $completedAppointments->count(),
            'total_completed' => $completedAppointments->count(),
            'total_revenue' => $completedAppointments->sum(function($appointment) {
                $services = is_string($appointment->selected_services) 
                    ? json_decode($appointment->selected_services, true) 
                    : $appointment->selected_services;
                return collect($services)->sum(function($service) {
                    if (isset($service['gender'])) {
                        return $service['gender'] === 'men' ? $service['price_men'] : $service['price_women'];
                    }
                    return $service['price'];
                });
            }),
            'average_price' => Service::avg('price'),
            'most_popular' => Service::select(
                'services.service_name',
                'services.category_id',
                'categories.name as category_name',
                DB::raw('COUNT(DISTINCT appointments.id) as booking_count')
            )
                ->leftJoin('categories', 'services.category_id', '=', 'categories.id')
                ->leftJoin('appointments', function($join) {
                    $join->on(function($query) {
                        $query->on('appointments.service_id', '=', 'services.id')
                              ->orWhereRaw('JSON_CONTAINS(appointments.selected_services, JSON_OBJECT("id", services.id))')
                              ->orWhere('appointments.service_name', 'LIKE', DB::raw('CONCAT("%", services.service_name, "%")'));
                    });
                })
                ->when($request->filled(['start_date', 'end_date']), function($query) use ($request) {
                    return $query->whereBetween('appointments.created_at', [
                        $request->start_date . ' 00:00:00',
                        $request->end_date . ' 23:59:59'
                    ]);
                })
                ->where('appointments.status', 'Completed')
                ->groupBy('services.service_name', 'services.category_id', 'categories.name')
                ->orderBy('booking_count', 'desc')
                ->first()
        ];

        // Get the authenticated manager
        $manager = Auth::guard('manager')->user();

        return view('manager.services-report', compact(
            'services',
            'summary',
            'manager',
            'categories'
        ));
    }
}