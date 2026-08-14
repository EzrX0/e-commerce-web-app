<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product; 

class ProductController extends Controller
{
    public function index(Request $request){

        $query = Product::with('variants', 'category');

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        $products = $query->paginate(12);

        return response()->json($products);
    }

     public function show(string $slug)
    {
        $product = Product::with('variants', 'category')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($products);
    }
}
