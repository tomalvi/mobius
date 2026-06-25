<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            $quantitiesByProduct = [];
            foreach ($items as $item) {
                $productId = $item['product_id'] ?? null;
                $quantity = $item['quantity'] ?? 0;

                if ($productId === null) {
                    continue;
                }

                $quantitiesByProduct[$productId] = ($quantitiesByProduct[$productId] ?? 0) + (int) $quantity;
            }

            if (empty($quantitiesByProduct)) {
                return;
            }

            $products = Product::whereIn('id', array_keys($quantitiesByProduct))
                ->get()
                ->keyBy('id');

            foreach ($quantitiesByProduct as $productId => $requestedQuantity) {
                $product = $products->get($productId);

                if (! $product) {
                    continue; // ya lo cubrió la regla 'exists' de rules()
                }

                if ($product->stock < $requestedQuantity) {
                    $validator->errors()->add(
                        'items',
                        "Stock insuficiente para el producto '{$product->name}'. Disponible: {$product->stock}, solicitado: {$requestedQuantity}."
                    );
                }
            }
        });
    }
}