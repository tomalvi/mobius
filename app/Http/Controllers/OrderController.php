<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('items.product')
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function create(StoreOrderRequest $request)
    {
        try {
            $order = DB::transaction(function () use ($request) {
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'status' => 'pending',
                    'total' => 0,
                ]);

                foreach ($request->validated('items') as $item) {
                    $product = Product::where('id', $item['product_id'])
                        ->lockForUpdate()
                        ->first();

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'subtotal' => $product->price * $item['quantity'],
                    ]);
                }

                event(new OrderCreated($order));

                return $order;
            });

            return new OrderResource($order->fresh('items.product'));
        } catch (InsufficientStockException $e) {
            return $e->render();
        }
    }

    public function itemsUser(Request $request)
    {
        $order = $request->attributes->get('order');
        $order->load('items.product');

        return new OrderResource($order);
    }


    public function cancelOrder(Request $request)
    {
        $order = $request->attributes->get('order');

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => "No se puede cancelar un pedido en estado '{$order->status}'. Solo pedidos en estado 'pending' pueden cancelarse.",
            ], 422);
        }

        $order->update(['status' => 'cancelled']);

        $order->load('items.product');

       return new OrderResource($order);
    }
}