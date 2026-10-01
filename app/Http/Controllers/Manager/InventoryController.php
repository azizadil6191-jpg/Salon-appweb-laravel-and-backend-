<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return view('manager.inventory', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'category' => 'required|in:Retail,Consumable',
            'brand' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stocks' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = $request->all();

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/products', $imageName);
            $data['image'] = 'products/' . $imageName;
        }

        // Set initial values for stock tracking
        $data['added_stock'] = $request->stocks;
        $data['total_added_stock'] = $request->stocks;

        Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully!'
        ]);
    }

    public function addStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);
        
        $product->update([
            'stocks' => $product->stocks + $request->quantity,
            'added_stock' => $request->quantity,
            'total_added_stock' => $product->total_added_stock + $request->quantity
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock added successfully!'
        ]);
    }

    public function useStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check if product is consumable
        if ($product->category !== 'Consumable') {
            return response()->json([
                'success' => false,
                'message' => 'This product is not consumable!'
            ], 400);
        }

        // Check if enough stock is available
        if ($product->stocks < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough stock available!'
            ], 400);
        }

        // Update stock
        $product->update([
            'stocks' => $product->stocks - $request->quantity
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product used successfully!'
        ]);
    }
} 