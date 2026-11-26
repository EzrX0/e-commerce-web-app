<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function store(Request $request){
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:250',
            'slug' => 'required|string|unique:products,slug',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'attributes' => 'nullable|array',
        ]);

        $product = Product::create($validated);

        return response()->json($product, 201);

    }

    public function update(Request $request, int $id){
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'somteimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:product,slug,' . $id,
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'attributes' => 'nullable|array', 
        ]);

        $product->update($validated);

        return response()->json($product);

    }

    public function destroy(int $id){
        Product::findOrFail($id)->delete();

        return response()->json(['message' => 'Product deleted']);

    }

    public function storeVariant(Request $request, int $product){
        
        $product =Product::findOFail($product);

        $validated = $request->validate([
            'sku' => 'required|string|unique:product_variants,sku',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'variant_attributes' => 'nullable|array',
        ]);


        $variant = $product->variants()->create($validated);

        return response()->json($variant,201);
    }
}
