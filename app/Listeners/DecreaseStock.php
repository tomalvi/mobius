<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;

class DecreaseStock
{
    public function handle(OrderCreated $event): void
    {
        foreach ($event->order->items as $item) {
            $product = Product::where('id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if ($product->stock < $item->quantity) {
                throw new InsufficientStockException($product, $item->quantity);
            }

            $product->decrement('stock', $item->quantity);
        }
    }
}