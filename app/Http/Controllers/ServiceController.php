<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
    
        // Fetch services with pagination and search functionality
        $services = Service::when($search, function ($query) use ($search) {
            return $query->where('service_name', 'like', "%{$search}%");
        })->with('category')->paginate(10); // Limit to 10 per page
    
        $categories = Category::all();
        
        return view('admin.services.index', compact('services', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'service_name' => 'required',
            'hair_length' => 'nullable|numeric',
            'duration' => 'required|numeric|min:1',
            'price' => 'nullable|numeric',
            'price_men' => 'nullable|numeric',
            'price_women' => 'nullable|numeric',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        if ($request->hasFile('profile_image')) {
            $imagePath = $request->file('profile_image')->store('services_images', 'public');
        } else {
            $imagePath = null;
        }
    
        Service::create([
            'category_id' => $request->category_id,
            'service_name' => $request->service_name,
            'hair_length' => $request->hair_length,
            'duration' => $request->duration,
            'price' => $request->price,
            'price_men' => $request->price_men,
            'price_women' => $request->price_women,
            'profile_image' => $imagePath,
        ]);
        \Log::info($request->all());


        return redirect()->route('services.index')->with('success', 'Service added successfully.');
    }

    public function edit($id)
    {
        $service = Service::findOrFail($id);
        $categories = Category::all();
        
        return view('services.edit', compact('service', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $validatedData = $request->validate([
            'category_id' => 'required',
            'service_name' => 'required|string|max:255',
            'hair_length' => 'nullable|string|max:255',
            'duration' => 'required|numeric|min:1',
            'price' => 'nullable|numeric',
            'price_men' => 'nullable|numeric',
            'price_women' => 'nullable|numeric',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            if ($service->profile_image) {
                Storage::delete('public/' . $service->profile_image);
            }
            $imagePath = $request->file('profile_image')->store('services', 'public');
            $validatedData['profile_image'] = $imagePath;
        }

        $service->update($validatedData);

        return redirect()->route('services.index')->with('success', 'Service updated successfully.');
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return redirect()->route('services.index')->with('success', 'Service deleted successfully.');
    }

    public function getServicesByCategory(Request $request)
    {
        $categoryId = $request->query('category_id');

        $services = Service::where('category_id', $categoryId)
            ->select('id', 'service_name', 'profile_image', 'duration')
            ->groupBy('id', 'service_name', 'profile_image', 'duration')
            ->distinct()
            ->get();
            
        return response()->json($services);
    }

    public function getServiceDetails(Request $request)
    {
        $categoryId = $request->query('category_id');
        $serviceName = $request->query('service_name');
        
        if (!$categoryId || !$serviceName) {
            return response()->json(['error' => 'Missing category_id or service_name'], 400);
        }
    
        $serviceDetails = Service::where('category_id', $categoryId)
            ->where('service_name', $serviceName)
            ->get(['id', 'hair_length', 'duration', 'price', 'price_men', 'price_women']);
    
        if ($serviceDetails->isEmpty()) {
            return response()->json(['error' => 'Service not found for this category'], 404);
        }
    
        return response()->json($serviceDetails);
    }

    public function checkOverlappingAppointments(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required|date_format:H:i',
            'staff_id' => 'required|exists:staff,id'
        ]);

        $service = Service::findOrFail($request->service_id);
        $appointmentDate = $request->appointment_date;
        $appointmentTime = $request->appointment_time;
        $staffId = $request->staff_id;

        // Get the service duration
        $duration = $service->duration ?? 60; // Default to 60 minutes if not set

        // Convert appointment time to Carbon instance
        $startTime = \Carbon\Carbon::parse($appointmentDate . ' ' . $appointmentTime);
        $endTime = $startTime->copy()->addMinutes($duration);

        // Check for overlapping appointments
        $overlappingAppointments = \App\Models\Appointment::where('staff_id', $staffId)
            ->whereDate('appointment_date', $appointmentDate)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // Check if existing appointment overlaps with new appointment
                    $q->where(function ($q) use ($startTime, $endTime) {
                        $q->where('appointment_time', '<=', $startTime->format('H:i:s'))
                          ->whereRaw('TIME_ADD(appointment_time, INTERVAL duration MINUTE) >= ?', [$startTime->format('H:i:s')]);
                    })->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('appointment_time', '<=', $endTime->format('H:i:s'))
                          ->whereRaw('TIME_ADD(appointment_time, INTERVAL duration MINUTE) >= ?', [$endTime->format('H:i:s')]);
                    });
                });
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_overlap' => $overlappingAppointments->isNotEmpty(),
                'overlapping_appointments' => $overlappingAppointments->map(function ($appointment) {
                    return [
                        'start_time' => $appointment->appointment_time,
                        'end_time' => \Carbon\Carbon::parse($appointment->appointment_time)
                            ->addMinutes($appointment->duration)
                            ->format('H:i:s'),
                        'duration' => $appointment->duration
                    ];
                })
            ]
        ]);
    }

    public function managerIndex(Request $request)
    {
        $search = $request->input('search');
        
        $services = Service::when($search, function ($query) use ($search) {
            return $query->where('service_name', 'like', "%{$search}%");
        })->with('category')->paginate(10);
    
        $categories = Category::all();
        $serviceCount = Service::count();
    
        if (auth()->guard('manager')->check()) {
            return view('manager.services', compact('services', 'categories', 'serviceCount'));
        }
    }
    public function managerStore(Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'service_name' => 'required',
            'duration' => 'required|numeric|min:1',
            'price' => 'nullable|numeric',
            'price_men' => 'nullable|numeric',
            'price_women' => 'nullable|numeric',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
    
        $imagePath = $request->hasFile('profile_image')
            ? $request->file('profile_image')->store('services_images', 'public')
            : null;
    
        Service::create([
            'category_id' => $request->category_id,
            'service_name' => $request->service_name,
            'hair_length' => $request->hair_length,
            'duration' => $request->duration,
            'price' => $request->price,
            'price_men' => $request->price_men,
            'price_women' => $request->price_women,
            'profile_image' => $imagePath,
        ]);

        return redirect()->route('manager.services')->with('success', 'Service added successfully by manager.');
    }

    public function managerEdit($id)
    {
        $service = Service::findOrFail($id);
        $categories = Category::all();
        
        return view('manager.services.edit', compact('service', 'categories'));
    }

    public function managerUpdate(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $validatedData = $request->validate([
            'category_id' => 'required',
            'service_name' => 'required|string|max:255',
            'hair_length' => 'nullable|string|max:255',
            'duration' => 'required|numeric|min:1',
            'price' => 'nullable|numeric',
            'price_men' => 'nullable|numeric',
            'price_women' => 'nullable|numeric',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            if ($service->profile_image) {
                Storage::delete('public/' . $service->profile_image);
            }
            $validatedData['profile_image'] = $request->file('profile_image')->store('services', 'public');
        }

        $service->update($validatedData);

        return redirect()->route('manager.services')->with('success', 'Service updated successfully by manager.');
    }

    public function managerDestroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return redirect()->route('manager.services')->with('success', 'Service deleted successfully by manager.');
    }
    
    public function getServicesByName(Request $request)
    {
        $serviceName = trim($request->query('service_name')); // Trim leading/trailing spaces
    
        if (!$serviceName) {
            return response()->json(['error' => 'Missing service_name'], 400);
        }
    
        $services = Service::where('service_name', $serviceName)
            ->select('id', 'service_name', 'profile_image')
            ->groupBy('id', 'service_name', 'profile_image')
            ->distinct()
            ->get();
    
        if ($services->isEmpty()) {
            return response()->json(['error' => 'No services found for the given service_name'], 404);
        }
    
        return response()->json($services);
    }
    
    
    
}
