<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index()
    {
        $products = Product::all(); // Fetch all products
        return view('admin.products.index', compact('products'));
    }
    

    /**
     * Store a new product (used for adding products).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'category' => 'required|in:Retail, Consumable',
            'brand' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stocks' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = $validated;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/products'), $imageName);
            $data['image'] = 'images/products/' . $imageName;
        }

        Product::create($data);

        return redirect()->route('cashier.products')->with('success', 'Product added successfully');
    }

    /**
     * Show the form for editing a product.
     */
    public function edit(Product $product)
    {
        return view('cashier.products.edit', compact('product'));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'product_name' => 'required|string|max:255',
            'category' => 'Retail, Consumable',
            'brand' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stocks' => 'required|integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('products', 'public');
            $product->image = $imagePath;
        }

        $product->update($request->except('image'));

        return redirect()->route('cashier.products')->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully!');
    }

    /**
     * Get products for mobile app
     */
    public function getProductsForMobile()
    {
        try {
            $products = Product::select('id', 'product_name', 'price', 'image', 'stocks', 'category')
                ->where('stocks', '>', 0)
                ->where('category', '!=', 'Consumable')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'product_name' => $product->product_name,
                        'price' => (float) $product->price,
                        'image' => $product->image,
                        'stocks' => (int) $product->stocks,
                        'category' => $product->category
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product stocks
     */
    public function updateStocks(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'stock_change' => 'required|integer',
            'notes' => 'nullable|string|max:255'
        ]);

        $product = Product::findOrFail($request->product_id);
        $newStock = $product->stocks + $request->stock_change;

        if ($newStock < 0) {
            return redirect()->back()->with('error', 'Cannot reduce stock below 0');
        }

        $product->stocks = $newStock;
        $product->save();

        // You might want to log this stock change in a separate table
        // StockLog::create([
        //     'product_id' => $product->id,
        //     'change' => $request->stock_change,
        //     'notes' => $request->notes,
        //     'previous_stock' => $product->stocks - $request->stock_change,
        //     'new_stock' => $newStock
        // ]);

        return redirect()->back()->with('success', 'Stock updated successfully');
    }
}
