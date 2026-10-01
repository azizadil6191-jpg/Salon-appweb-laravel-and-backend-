<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category; // Import the Category model

class CategoryController extends Controller
{
    public function getCategories()
    {
        $categories = Category::all();
        return response()->json(['categories' => $categories]);
    }

    public function getStaffByCategory(Request $request)
    {
        $categoryId = $request->query('category_id');  // Pass the category ID here

        // Fetch the category along with its staff including profile_picture
        $category = Category::with('staff')->find($categoryId);
    
        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }
    
        // Map staff data to include profile_picture
        $staffWithProfilePicture = $category->staff->map(function ($staff) {
            return [
                'id' => $staff->id,
                'first_name' => $staff->first_name, 
                'last_name' =>$staff->last_name, 
                'email' =>$staff->email,
                'profile_picture_url' => $staff->profile_picture ? asset('storage/' . $staff->profile_picture) : null,
                // Add other fields as necessary
            ];
        });
    
        return response()->json($staffWithProfilePicture); // Returns all staff members assigned to this category
    }
}
