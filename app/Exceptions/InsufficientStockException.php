<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientStockException extends Exception
{
    public function __construct(public Product $product, public int $requestedQuantity)
    {
        parent::__construct(
            "Stock insuficiente para el producto '{$product->name}'. ".
            "Disponible: {$product->stock}, solicitado: {$requestedQuantity}."
        );
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'product_id' => $this->product->id,
            'available_stock' => $this->product->stock,
            'requested_quantity' => $this->requestedQuantity,
        ], 422);
    }
}