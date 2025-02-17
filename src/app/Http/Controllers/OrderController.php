<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CreateOrderRequest;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['customer', 'items.product'])->get();
        return response()->json($orders);
    }

    public function store(CreateOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            // Stok kontrolü
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product || $product->stock < $item['quantity']) {
                    throw new \Exception("Ürün {$item['product_id']} için yeterli stok bulunmamaktadır.");
                }
            }

            $order = Order::create([
                'customer_id' => $request->customer_id,
                'total' => collect($request->items)->sum('total')
            ]);
            
            foreach ($request->items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total']
                ]);

                Product::where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
            }

            DB::commit();
            return response()->json($order->load('items'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e->getMessage());
            return response()->json(['hata' => 'Sipariş oluşturulurken bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.'], 400);
        }
    }

    public function destroy(Order $order)
    {
        // Stokları geri yükle
        foreach ($order->items as $item) {
            Product::where('id', $item->product_id)
                ->increment('stock', $item->quantity);
        }

        $order->delete();
        return response()->json(null, 204);
    }
}