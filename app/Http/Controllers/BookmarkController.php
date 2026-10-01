<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bookmark;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookmarkController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            $bookmarks = Bookmark::with(['service', 'service.category'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($bookmark) {
                    return [
                        'id' => $bookmark->id,
                        'service_id' => $bookmark->service_id,
                        'service_name' => $bookmark->service->name,
                        'category_id' => $bookmark->service->category_id,
                        'category_name' => $bookmark->service->category->name,
                        'profile_image' => $bookmark->service->profile_image,
                        'date_added' => $bookmark->created_at,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $bookmarks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch bookmarks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_id' => 'required|exists:services,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            
            // Check if bookmark already exists
            $existingBookmark = Bookmark::where('user_id', $user->id)
                ->where('service_id', $request->service_id)
                ->first();

            if ($existingBookmark) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service is already bookmarked'
                ], 400);
            }

            $bookmark = Bookmark::create([
                'user_id' => $user->id,
                'service_id' => $request->service_id,
            ]);

            $service = Service::with('category')->find($request->service_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Service bookmarked successfully',
                'data' => [
                    'id' => $bookmark->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'category_id' => $service->category_id,
                    'category_name' => $service->category->name,
                    'profile_image' => $service->profile_image,
                    'date_added' => $bookmark->created_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to bookmark service',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $bookmark = Bookmark::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$bookmark) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Bookmark not found'
                ], 404);
            }

            $bookmark->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Bookmark removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove bookmark',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function check($serviceId)
    {
        try {
            $user = Auth::user();
            $isBookmarked = Bookmark::where('user_id', $user->id)
                ->where('service_id', $serviceId)
                ->exists();

            return response()->json([
                'status' => 'success',
                'is_bookmarked' => $isBookmarked
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check bookmark status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}