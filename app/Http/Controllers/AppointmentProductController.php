<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppointmentProductController extends Controller
{
    /**
     * Store products for an appointment
     */
    public function store(Request $request, $appointmentId)
    {
        try {
            // Validate appointment ID
            if (!$appointmentId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Appointment ID is required'
                ], 422);
            }

            $appointment = Appointment::find($appointmentId);
            
            if (!$appointment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Appointment not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'selected_products' => 'required|array',
                'selected_products.*.id' => 'required|exists:products,id',
                'selected_products.*.quantity' => 'required|integer|min:1',
                'selected_products.*.price' => 'required|numeric|min:0',
                'selected_products.*.name' => 'required|string',
                'selected_products.*.subtotal' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate products
            $productIds = collect($request->selected_products)->pluck('id');
            if ($productIds->count() !== $productIds->unique()->count()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Duplicate products found in request'
                ], 422);
            }

            DB::beginTransaction();

            // Check stock availability first
            foreach ($request->selected_products as $productData) {
                $product = Product::findOrFail($productData['id']);
                if ($product->stocks < $productData['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Insufficient stock for product: ' . $product->name
                    ], 422);
                }
            }

            foreach ($request->selected_products as $productData) {
                if (!isset($productData['id']) || !isset($productData['quantity']) || !isset($productData['price'])) {
                    DB::rollBack();
                    throw new \Exception('Missing required product data');
                }

                $product = Product::findOrFail($productData['id']);
                
                // Verify product price matches
                if ($product->price != $productData['price']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Product price mismatch for product ID: ' . $product->id
                    ], 422);
                }
                
                // Check if product is already in the appointment
                $existingProduct = $appointment->products()
                    ->where('product_id', $product->id)
                    ->first();

                if ($existingProduct) {
                    // Update existing product
                    $existingProduct->pivot->update([
                        'quantity' => $productData['quantity'],
                        'price' => $product->price,
                        'subtotal' => $product->price * $productData['quantity']
                    ]);
                } else {
                    // Add new product
                    $appointment->products()->attach($product->id, [
                        'quantity' => $productData['quantity'],
                        'price' => $product->price,
                        'subtotal' => $product->price * $productData['quantity']
                    ]);
                }

                // Update product stock
                $product->decrement('stocks', $productData['quantity']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Products added to appointment successfully',
                'appointment' => $appointment->load('products')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in AppointmentProductController@store: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add products to appointment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a product from an appointment
     */
    public function destroy(Appointment $appointment, Product $product)
    {
        try {
            DB::beginTransaction();

            // Get the quantity before removing
            $appointmentProduct = $appointment->products()
                ->where('product_id', $product->id)
                ->first();

            if ($appointmentProduct) {
                // Return the quantity to stock
                $product->increment('stocks', $appointmentProduct->pivot->quantity);

                // Remove the product from appointment
                $appointment->products()->detach($product->id);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product removed from appointment successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove product from appointment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product quantity in an appointment
     */
    public function update(Request $request, Appointment $appointment, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $appointmentProduct = $appointment->products()
                ->where('product_id', $product->id)
                ->first();

            if (!$appointmentProduct) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found in appointment'
                ], 404);
            }

            $oldQuantity = $appointmentProduct->pivot->quantity;
            $newQuantity = $request->quantity;
            $quantityDiff = $newQuantity - $oldQuantity;

            // Update stock
            if ($quantityDiff > 0) {
                // Check if we have enough stock
                if ($product->stocks < $quantityDiff) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Not enough stock available'
                    ], 422);
                }
                $product->decrement('stocks', $quantityDiff);
            } else {
                $product->increment('stocks', abs($quantityDiff));
            }

            // Update appointment product
            $appointmentProduct->pivot->update([
                'quantity' => $newQuantity,
                'subtotal' => $product->price * $newQuantity
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product quantity updated successfully',
                'appointment' => $appointment->load('products')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product quantity',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 