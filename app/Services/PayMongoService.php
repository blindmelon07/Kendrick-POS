<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayMongoService
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('paymongo.secret_key');
        $this->baseUrl   = config('paymongo.base_url');
    }

    /**
     * Create a PayMongo Checkout Session and return the checkout URL.
     * Returns null if the API call fails.
     */
    public function createCheckoutSession(Order $order): ?string
    {
        $order->loadMissing('items');

        $lineItems = $order->items->map(fn ($item) => [
            'name'        => $item->product_name,
            'quantity'    => max(1, (int) $item->quantity),
            'amount'      => (int) round($item->unit_price * 100),
            'currency'    => 'PHP',
            'description' => $item->product_name,
        ])->toArray();

        if ($order->delivery_fee > 0) {
            $lineItems[] = [
                'name'     => 'Delivery Fee',
                'quantity' => 1,
                'amount'   => (int) round($order->delivery_fee * 100),
                'currency' => 'PHP',
            ];
        }

        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => [
                        'name'  => $order->client_name,
                        'email' => $order->client_email,
                        'phone' => $order->client_phone,
                    ],
                    'line_items'           => $lineItems,
                    'payment_method_types' => config('paymongo.payment_methods'),
                    'success_url'          => route('payment.success', ['orderId' => $order->id]),
                    'cancel_url'           => route('payment.cancel',  ['orderId' => $order->id]),
                    'description'          => "Order {$order->reference_no}",
                    'reference_number'     => $order->reference_no,
                    'send_email_receipt'   => true,
                    'metadata'             => ['order_id' => $order->id],
                ],
            ],
        ];

        $response = $this->post('/checkout_sessions', $payload);

        if (! $response || $response->failed()) {
            Log::error('PayMongo: checkout session creation failed', [
                'order_id' => $order->id,
                'status'   => $response?->status(),
                'body'     => $response?->body(),
            ]);

            return null;
        }

        $data = $response->json('data');

        $order->update([
            'paymongo_checkout_id' => $data['id'],
        ]);

        return $data['attributes']['checkout_url'];
    }

    /**
     * Retrieve a checkout session by ID.
     */
    public function getCheckoutSession(string $sessionId): ?array
    {
        $response = $this->get("/checkout_sessions/{$sessionId}");

        return $response?->successful() ? $response->json('data') : null;
    }

    /**
     * Verify a webhook signature from PayMongo.
     * Header format: "t=<timestamp>,te=<test_hash>,li=<live_hash>"
     */
    public function verifyWebhookSignature(string $rawBody, string $signatureHeader): bool
    {
        $webhookSecret = config('paymongo.webhook_secret');

        if (empty($webhookSecret)) {
            return true; // Skip verification in local dev if no secret set
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $split = explode('=', $part, 2);
            if (count($split) === 2) {
                $parts[$split[0]] = $split[1];
            }
        }

        if (empty($parts['t'])) {
            return false;
        }

        $payload   = $parts['t'] . '.' . $rawBody;
        $computed  = hash_hmac('sha256', $payload, $webhookSecret);

        // Check against both test (te) and live (li) hashes
        return hash_equals($computed, $parts['te'] ?? '')
            || hash_equals($computed, $parts['li'] ?? '');
    }

    private function post(string $endpoint, array $payload): ?Response
    {
        try {
            return Http::withBasicAuth($this->secretKey, '')
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . $endpoint, $payload);
        } catch (\Throwable $e) {
            Log::error('PayMongo HTTP error', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function get(string $endpoint): ?Response
    {
        try {
            return Http::withBasicAuth($this->secretKey, '')
                ->get($this->baseUrl . $endpoint);
        } catch (\Throwable $e) {
            Log::error('PayMongo HTTP error', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
