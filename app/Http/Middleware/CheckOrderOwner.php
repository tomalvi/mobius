<?php

namespace App\Http\Middleware;

use App\Models\Order;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOrderOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $order = Order::find($request->route('id'));

        if (!$order) {
            return response()->json([
                'message' => 'Pedido no encontrado.',
            ], 404);
        }

        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a este pedido.',
            ], 403);
        }

        $request->attributes->set('order', $order);

        return $next($request);
    }
}