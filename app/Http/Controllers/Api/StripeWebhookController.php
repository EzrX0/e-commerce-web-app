<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $payment = Payment::where('gateway_transaction_id', $session->id)->first();

            if ($payment && $payment->status !== 'success') {
                // Idempotency guard — only process this exact event once,
                // even if Stripe retries the webhook delivery
                $payment->update([
                    'status' => 'success',
                    'paid_at' => now(),
                ]);

                $payment->order->update(['status' => 'paid']);
            }
        }

        return response()->json(['received' => true]);
    }
}