<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CreateOrderRequest;
use App\Services\DiscountCalculatorService;

class OrderController extends Controller
{
    private $discountCalculator;

    public function __construct(DiscountCalculatorService $discountCalculator)
    {
        $this->discountCalculator = $discountCalculator;
    }

    public function index()
    {
        $orders = Order::with(['items'])->get();
        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        return new OrderResource($order->load('items'));
    }

    public function store(CreateOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            // Stok kontrolü
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);

                if (!$product || $product->stock < $item['quantity']) {
                    return response()->json(['hata' => "Ürün {$item['product_id']} için yeterli stok bulunmamaktadır."], 400);
                }
            }

            $order = Order::create([
                'customer_id' => $request->customer_id,
                'total' => collect($request->items)->sum(function($item) {
                    $product = Product::find($item['product_id']);
                    return $product ? $product->price * $item['quantity'] : 0; 
                })
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $unitPrice = $product ? $product->price : 0; 

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total' => $unitPrice * $item['quantity'] 
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

    public function calculateDiscounts(Order $order)
    {
        return response()->json(
            $this->discountCalculator->calculateDiscounts($order)
        );
    }

    public function update(CreateOrderRequest $request, Order $order)
    {
        try {
            DB::beginTransaction();

            // Stok kontrolü
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);

                if (!$product || $product->stock < $item['quantity']) {
                    return response()->json(['hata' => "Ürün {$item['product_id']} için yeterli stok bulunmamaktadır."], 400);
                }
            }

            // Mevcut siparişi güncelle
            $order->update([
                'customer_id' => $request->customer_id,
                'total' => collect($request->items)->sum(function($item) {
                    $product = Product::find($item['product_id']);
                    return $product ? $product->price * $item['quantity'] : 0; 
                })
            ]);

            // Siparişin öğelerini güncelle
            $order->items()->delete(); // Önce mevcut öğeleri sil
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $unitPrice = $product ? $product->price : 0; 

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total' => $unitPrice * $item['quantity'] 
                ]);

                Product::where('id', $item['product_id'])
                    ->decrement('stock', $item['quantity']);
            }

            DB::commit();
            return response()->json($order->load('items'), 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($e->getMessage());
            return response()->json(['hata' => 'Sipariş güncellenirken bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.'], 400);
        }
    }
}
