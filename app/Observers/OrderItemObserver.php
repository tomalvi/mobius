<?php

namespace App\Observers;

use App\Models\OrderItem;

class OrderItemObserver
{
    public function created(OrderItem $item): void
    {
        $this->recalculateTotal($item);
    }

    public function updated(OrderItem $item): void
    {
        $this->recalculateTotal($item);
    }

    public function deleted(OrderItem $item): void
    {
        $this->recalculateTotal($item);
    }

    private function recalculateTotal(OrderItem $item): void
    {
        $order = $item->order;
        $order->total = $order->items()->sum('subtotal');
        $order->saveQuietly();
    }
}