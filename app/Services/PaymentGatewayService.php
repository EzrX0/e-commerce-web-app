<?php

namespace App\Services;

use App\Models\Order;
use Stripe\StripeClient;

class PaymentGatewayService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createTransaction(Order $order): array
    {
        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => (int) round($order->total * 100), // Stripe uses cents
                    'product_data' => [
                        'name' => 'Order #' . $order->id,
                    ],
                ],
                'quantity' => 1,
            ]],
            'customer_email' => $order->user->email,
            'success_url' => config('app.url') . '/api/checkout/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.url') . '/api/checkout/cancel',
            'metadata' => [
                'order_id' => $order->id,
            ],
        ]);

        return [
            'checkout_session_id' => $session->id,
            'checkout_url' => $session->url,
        ];
    }
}