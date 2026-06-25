<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index()
    {
        $products = Cache::remember('products.index', now()->addMinutes(5), function () {
            return Product::orderBy('name')->get();
        });

        return ProductResource::collection($products);
    }
}