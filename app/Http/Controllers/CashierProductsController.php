<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class CashierProductsController extends Controller
{
    public function index()
    {
        $products = Product::where('category', '!=', 'Consumable')->get(); // Fetch only non-consumable products
        return view('cashier.products', compact('products')); // Cashier View
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'category' => 'required|in:Shampoo,Conditioner,Hair Toners,Hair Electrical,Hair Mask,Hair Care',
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

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('cashier.products')->with('success', 'Product deleted successfully!');
    }

    public function transaction()
    {
        $products = Product::where('category', '!=', 'Consumable')->get(); // Fetch only non-consumable products
        return view('cashier.transaction', compact('products'));
    }

    public function checkout(Request $request) {
        $request->validate([
            'order_id' => 'required|string|unique:orders,order_id',
            'total_amount' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);
    
        // Calculate change
        $change_amount = $request->amount_paid - $request->total_amount;
        if ($change_amount < 0) {
            return response()->json(['message' => 'Insufficient amount paid'], 400);
        }
    
        DB::beginTransaction();
        try {
            // Create Order with payment details
            $order = Order::create([
                'order_id' => $request->order_id,
                'total_amount' => $request->total_amount,
                'amount_paid' => $request->amount_paid,
                'change_amount' => $change_amount,
                'cashier_id' => auth()->guard('cashier')->id(), // Add cashier_id from authenticated cashier
            ]);
    
            // Process Each Ordered Product
            foreach ($request->products as $item) {
                $product = Product::findOrFail($item['id']);
    
                // Ensure enough stock
                if ($product->stocks < $item['quantity']) {
                    return response()->json(['message' => "Not enough stock for {$product->product_name}"], 400);
                }
    
                // Deduct stock
                $product->stocks -= $item['quantity'];
                $product->save();
    
                // Save Order Item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['quantity'] * $product->price,
                ]);
            }
    
            DB::commit();
            return response()->json([
                'message' => 'Order placed successfully!',
                'order_id' => $order->order_id,
                'total_amount' => $order->total_amount,
                'amount_paid' => $order->amount_paid,
                'change_amount' => $order->change_amount,
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Checkout failed', 'error' => $e->getMessage()], 500);
        }
    }
    

    public function history(Request $request)
    {
        $query = Order::with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10); // Limit to 10 most recent transactions
        
        // Date filtering
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('created_at', [
                $request->from . ' 00:00:00',
                $request->to . ' 23:59:59'
            ]);
        }
        
        $orders = $query->get();
        
        return view('cashier.history', compact('orders'));
    }

    /**
     * Print receipt (if you need it)
     */
    public function receipt($orderId)
    {
        $order = Order::with(['items.product'])
            ->where('order_id', $orderId)
            ->firstOrFail();
            
        return view('cashier.receipt', compact('order'));
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
            'added_stock' => $request->quantity, // Track the newly added amount
            'total_added_stock' => $product->total_added_stock + $request->quantity // Update lifetime total
        ]);
    
        return redirect()->route('cashier.products')->with('success', 'Stock added successfully!');
    }
public function stocksView()
{
    $products = Product::all(); // Fetch all products
    return view('cashier.stocks', compact('products'));
}

}


