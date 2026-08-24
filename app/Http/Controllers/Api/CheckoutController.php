<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(private PaymentGatewayService $paymentGateway) {}

    public function checkout(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)
            ->with('items.variant')
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        $order = DB::transaction(function () use ($cart, $request) {
            $subtotal = 0;

            // Lock each variant row before checking/decrementing stock —
            // prevents two simultaneous checkouts from overselling the same item
            foreach ($cart->items as $item) {
                $variant = $item->variant()->lockForUpdate()->first();

                if ($variant->stock_quantity < $item->quantity) {
                    throw new \Exception("Insufficient stock for {$variant->sku}");
                }

                $subtotal += $variant->price * $item->quantity;
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'address_id' => $request->address_id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => 0,
                'shipping_cost' => 0,
                'total' => $subtotal,
            ]);

            foreach ($cart->items as $item) {
                $variant = $item->variant()->lockForUpdate()->first();

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item->quantity,
                    'price_at_purchase' => $variant->price,
                ]);

                $variant->decrement('stock_quantity', $item->quantity);
            }

            // Clear the cart now that it's been converted into an order
            $cart->items()->delete();

            return $order;
        });

        // Create the Midtrans payment session (outside the DB transaction —
        // an external API call should never be inside a DB lock)
        $snapToken = $this->paymentGateway->createTransaction($order);

        $stripeResult = $this->paymentGateway->createTransaction($order);

        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'gateway_transaction_id' => $stripeResult['checkout_session_id'],
            'status' => 'pending',
            'amount' => $order->total,
        ]);

        return response()->json([
            'order' => $order->load('items.variant.product'),
            'payment' => [
                'checkout_url' => $stripeResult['checkout_url'],
            ],
        ], 201);
    }
}