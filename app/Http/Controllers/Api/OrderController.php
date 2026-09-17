<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request){
        
        $orders = Order::where('user_id', $request->user()->id)
            ->with('items.variant.product', 'payment')
            ->latest()
            ->paginate(10);
             
        return response()->json($orders);
    }

    public function show(Request $request, int $id ){

        $order = Order::where('user_id', $request->user()->id)
            ->with('items.variant.product', 'payment', 'address')
            ->findOrfail($id);

        return response()->json($order);
    }
}
