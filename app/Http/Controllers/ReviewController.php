<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Staff;

class ReviewController extends Controller
{
    
    

    public function index()
    {
        // Fetch all reviews along with the related client
        $reviews = Review::with('client')->get();
    
        // Map profile images to reviews
        $reviews = $reviews->map(function ($review) {
            $review->profile_image = $review->client && $review->client->profile_image
            ? asset('storage/' . $review->client->profile_image)
            : asset('storage/default-profile.png');
        
        
            return $review;
        });
    
        return view('admin.reviews.index', compact('reviews'));
    }
    

    
     public function getCompletedAppointments(Request $request)
    {
        $client = $request->user();

        $appointments = Appointment::where('status', 'Completed')
            ->where('client_id', $client->id)
            ->get();

        return response()->json($appointments);
    }


    public function submitReview(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'full_name' => 'required|string|max:255',
            'service_name' => 'required|string|max:255',
            'selected_services' => 'nullable|array',
            'selected_staff' => 'required|array',
            'rating' => 'required|integer|min:1|max:5',
            'review_message' => 'required|string|max:1000',
        ]);
    
        $review = Review::create([
            'client_id' => $validated['client_id'],
            'full_name' => $validated['full_name'],
            'service_name' => $validated['service_name'],
            'selected_services' => $validated['selected_services'] ?? [],
            'selected_staff' => $validated['selected_staff'] ?? [],
            'rating' => $validated['rating'],
            'review_message' => $validated['review_message'],
        ]);

        // Attach multiple staff members to the review
        if (!empty($validated['selected_staff'])) {
            $staffIds = array_map(function($staff) {
                return $staff['id'];
            }, $validated['selected_staff']);
            
            $review->staff()->attach($staffIds);
        }
    
        return response()->json(['message' => 'Review submitted successfully', 'review' => $review]);
    }
    
    

    public function getReviews(Request $request)
    {
        $serviceName = $request->query('service_name');
    
        if (!$serviceName) {
            return response()->json(['error' => 'Service name is required'], 400);
        }
    
        // Fetch reviews filtered by service name
        $reviews = Review::where('service_name', $serviceName)->get();
    
        // Attach profile images
        $reviewsWithImages = $reviews->map(function ($review) {
            $client = Client::where('fullname', $review->full_name)->first();
            $review->profile_image = $client && $client->profile_image
                ? asset('storage/' . $client->profile_image)
                : asset('storage/default-profile.png');
            return $review;
        });
    
        return response()->json($reviewsWithImages);
    }
    
    
    
    
    public function getReviewforStaff()
    {
        // Fetch all reviews along with the related client
        $reviews = Review::with('client')->get();
    
        // Map profile images to reviews
        $reviews = $reviews->map(function ($review) {
            $review->profile_image = $review->client && $review->client->profile_image
            ? asset('storage/' . $review->client->profile_image)
            : asset('storage/default-profile.png');
        
        
            return $review;
        });
    
        return view('staff.reviews', compact('reviews'));
    }
    
    
    public function getReviewforManager()
    {
        // Fetch all reviews along with the related client
        $reviews = Review::with('client')->get();
    
        // Map profile images to reviews
        $reviews = $reviews->map(function ($review) {
            $review->profile_image = $review->client && $review->client->profile_image
            ? asset('storage/' . $review->client->profile_image)
            : asset('storage/default-profile.png');
        
        
            return $review;
        });
    
        return view('manager.reviews', compact('reviews'));
    }

    public function getReviewsByStaff(Request $request)
    {
        $staffId = $request->query('staff_id');
    
        if (!$staffId) {
            return response()->json(['error' => 'Staff ID is required'], 400);
        }
    
        // Fetch reviews for the specific staff member
        $reviews = Review::whereHas('staff', function($query) use ($staffId) {
            $query->where('staff.id', $staffId);
        })->with(['client', 'staff'])->get();
    
        // Attach profile images
        $reviewsWithImages = $reviews->map(function ($review) {
            $review->profile_image = $review->client && $review->client->profile_image
                ? asset('storage/' . $review->client->profile_image)
                : asset('storage/default-profile.png');
            return $review;
        });
    
        return response()->json($reviewsWithImages);
    }
}
