<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayMongoWebhookController extends Controller
{
    public function __invoke(Request $request, PayMongoService $paymongo): \Illuminate\Http\Response
    {
        $rawBody  = $request->getContent();
        $sigHeader = $request->header('paymongo-signature', '');

        if (! $paymongo->verifyWebhookSignature($rawBody, $sigHeader)) {
            Log::warning('PayMongo: invalid webhook signature');

            return response('Unauthorized', 401);
        }

        $event = $request->json('data.attributes');
        $type  = $event['type'] ?? null;

        Log::info('PayMongo webhook received', ['type' => $type]);

        match ($type) {
            'checkout_session.payment.paid' => $this->handlePaymentPaid($event['data'] ?? []),
            default                         => null,
        };

        return response('OK', 200);
    }

    private function handlePaymentPaid(array $session): void
    {
        $checkoutId = $session['id'] ?? null;
        if (! $checkoutId) {
            return;
        }

        $order = Order::where('paymongo_checkout_id', $checkoutId)->first();
        if (! $order) {
            Log::warning('PayMongo: no order found for checkout session', ['checkout_id' => $checkoutId]);

            return;
        }

        $payments   = $session['attributes']['payments'] ?? [];
        $paymentId  = $payments[0]['id'] ?? null;
        $amountPaid = isset($payments[0]['attributes']['amount'])
            ? $payments[0]['attributes']['amount'] / 100
            : $order->total;

        $order->update([
            'payment_status'       => 'paid',
            'amount_paid'          => $amountPaid,
            'paymongo_payment_id'  => $paymentId,
            'status'               => 'confirmed',
        ]);

        Log::info('PayMongo: order marked as paid', [
            'order_id'   => $order->id,
            'reference'  => $order->reference_no,
            'payment_id' => $paymentId,
        ]);
    }
}
