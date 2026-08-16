<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request){

        $cart = $this-> getOrCreateCart($request);
        return response()->json($cart->load('item.varient.product'));
    }

    public function addItem(Request $request){

        $request->validate([
            'product_variant_id'=> 'required|exists:product_variants,id',
            'quantity'=> 'required|integer|min:1',
        ]);

        $cart = $this->getOrCreateCart($request);

        $variant= ProductVariant::FindOrfail($request->product_variant_id);

        if ($variant->stock_quantity < $request->quantity){
            return response()->json(['message'=> 'Not enough stock'], 422);

        }

        $existingItem = $cart->items()->where('product_variant_id', $variant->id)->first();

        if ($existingItem){
            $existingItem->increment('quantity', $request->quantity);
        } else{
            $cart->items()->create([
                'product_variant_id' => $variant->id, 
                'quantity' => $request->quantity,
            ]);
        }

        return response()->json($cart->load('items.variant.product'), 201);
    }

    public function updateItem(Request $request, int $itemId){

        $request->validate([
            'quantity' => 'required|integer|min:1',

        ]);

        $cart = $this->getOrCreateCart($request);
        $item = $cart->items()->findOrFail($itemId);
        $item->update(['quantity' => $request->quantity]);

        return response()->json($cart->load('items.variant.product'));

    }

    public function removeItem(Request $request, int $itemId){
        $cart = $this->getOrCreateCart($request);
        $cart->items()->FindOrFail($itemId)->delete();

        return response()->json(['message' => 'Item removed']);
    }

    private function getOrCreateCart(Request $request): Cart {
        return Cart::firstOrCreate(['user_id' => $request->user()->id]);
    }
}
