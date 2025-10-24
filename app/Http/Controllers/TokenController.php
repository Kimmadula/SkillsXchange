<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TokenController extends Controller
{
    /**
     * Handle token purchase
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'total_amount' => 'required|numeric|min:5'
        ]);

        $user = Auth::user();
        $quantity = $request->quantity;
        $amount = $request->total_amount;

        // Verify amount calculation
        $expectedAmount = $quantity * 5.00;
        if (abs($amount - $expectedAmount) > 0.01) {
            return redirect()->back()->withErrors(['amount' => 'Amount calculation mismatch']);
        }

        try {
            DB::beginTransaction();

            // Create PayMongo payment intent
            $paymentIntent = $this->createPayMongoPaymentIntent($amount, $user);

            if (!$paymentIntent) {
                DB::rollback();
                return redirect()->back()->withErrors(['payment' => 'Failed to create payment intent. Please try again.']);
            }

            // Create transaction record with real payment intent ID
            $transactionId = DB::table('token_transactions')->insertGetId([
                'user_id' => $user->id,
                'quantity' => $quantity,
                'amount' => $amount,
                'payment_method' => 'paymongo', // Default to PayMongo since user chooses method on their platform
                'payment_intent_id' => $paymentIntent['id'],
                'status' => 'pending',
                'notes' => 'Payment intent created',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            // Redirect to PayMongo checkout
            if (isset($paymentIntent['checkout_url'])) {
                return redirect($paymentIntent['checkout_url'])
                    ->with('success', 'Redirecting to payment...');
            } else {
                // If no checkout URL, show success message with payment intent ID
                return redirect()->route('tokens.history')
                    ->with('success', 'Payment intent created. Payment Intent ID: ' . $paymentIntent['id']);
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Token purchase failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->withErrors(['error' => 'Purchase failed. Please try again.']);
        }
    }


    /**
     * Handle PayMongo webhook for payment confirmation
     * Based on best practices from PayMongo webhook implementations
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        $isTestMode = config('services.paymongo.test_mode', true);

        // Log incoming webhook for debugging
        Log::info('PayMongo webhook received', [
            'test_mode' => $isTestMode,
            'headers' => $request->headers->all(),
            'payload' => $payload
        ]);

        // Verify webhook signature for security
        if (!$this->verifyPayMongoWebhook($request)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Extract payment intent data
        $paymentIntentId = $payload['data']['id'] ?? null;
        $status = $payload['data']['attributes']['status'] ?? null;
        $eventType = $payload['type'] ?? null;

        if (!$paymentIntentId || !$status) {
            Log::error('Invalid webhook payload', ['payload' => $payload]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info('Processing webhook', [
            'event_type' => $eventType,
            'payment_intent_id' => $paymentIntentId,
            'status' => $status
        ]);

        try {
            DB::beginTransaction();

            // Find the transaction
            $transaction = DB::table('token_transactions')
                ->where('payment_intent_id', $paymentIntentId)
                ->first();

            if (!$transaction) {
                Log::warning('Transaction not found for payment intent', [
                    'payment_intent_id' => $paymentIntentId
                ]);
                throw new \Exception('Transaction not found');
            }

            // Handle different payment statuses
            switch ($status) {
                case 'succeeded':
                    $this->handlePaymentSuccess($transaction, $paymentIntentId, $isTestMode);
                    break;
                case 'payment_failed':
                case 'failed':
                    $this->handlePaymentFailure($transaction, $paymentIntentId);
                    break;
                case 'canceled':
                case 'cancelled':
                    $this->handlePaymentCancellation($transaction, $paymentIntentId);
                    break;
                default:
                    Log::info('Unhandled payment status', [
                        'status' => $status,
                        'payment_intent_id' => $paymentIntentId
                    ]);
            }

            DB::commit();
            Log::info('Webhook processed successfully', [
                'payment_intent_id' => $paymentIntentId,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Webhook processing failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSuccess($transaction, $paymentIntentId, $isTestMode)
    {
        // Update transaction status
        DB::table('token_transactions')
            ->where('id', $transaction->id)
            ->update([
                'status' => 'completed',
                'updated_at' => now()
            ]);

        // Add tokens to user's account
        $user = DB::table('users')->where('id', $transaction->user_id)->first();
        $newBalance = ($user->token_balance ?? 0) + $transaction->quantity;

        DB::table('users')
            ->where('id', $transaction->user_id)
            ->update(['token_balance' => $newBalance]);

        Log::info('Token purchase completed', [
            'user_id' => $transaction->user_id,
            'quantity' => $transaction->quantity,
            'amount' => $transaction->amount,
            'new_balance' => $newBalance,
            'test_mode' => $isTestMode,
            'payment_intent_id' => $paymentIntentId
        ]);
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailure($transaction, $paymentIntentId)
    {
        DB::table('token_transactions')
            ->where('id', $transaction->id)
            ->update([
                'status' => 'failed',
                'updated_at' => now()
            ]);

        Log::info('Payment failed', [
            'transaction_id' => $transaction->id,
            'payment_intent_id' => $paymentIntentId
        ]);
    }

    /**
     * Handle cancelled payment
     */
    private function handlePaymentCancellation($transaction, $paymentIntentId)
    {
        DB::table('token_transactions')
            ->where('id', $transaction->id)
            ->update([
                'status' => 'cancelled',
                'updated_at' => now()
            ]);

        Log::info('Payment cancelled', [
            'transaction_id' => $transaction->id,
            'payment_intent_id' => $paymentIntentId
        ]);
    }

    /**
     * Create PayMongo payment intent
     * Based on official PayMongo API documentation
     */
    private function createPayMongoPaymentIntent($amount, $user)
    {
        $paymongoSecretKey = config('services.paymongo.secret_key');
        $baseUrl = config('services.paymongo.base_url', 'https://api.paymongo.com');
        $isTestMode = config('services.paymongo.test_mode', true);

        // PayMongo API payload structure (exactly as per their documentation)
        $payload = [
            'data' => [
                'attributes' => [
                    'amount' => (int)($amount * 100), // Convert to centavos (₱5.00 = 500)
                    'currency' => 'PHP',
                    'payment_method_allowed' => [
                        'qrph', 'card', 'dob', 'paymaya', 'billease', 'gcash', 'grab_pay'
                    ],
                    'payment_method_options' => [
                        'card' => [
                            'request_three_d_secure' => 'any'
                        ]
                    ],
                    'capture_type' => 'automatic',
                    'description' => 'Token Purchase - SkillsXchange',
                    'statement_descriptor' => 'SkillsXchange Tokens',
                    'metadata' => [
                        'user_id' => (string)$user->id,
                        'user_email' => $user->email,
                        'purchase_type' => 'tokens',
                        'test_mode' => $isTestMode ? 'true' : 'false'
                    ]
                ]
            ]
        ];

        // PayMongo API headers (exactly as per their documentation)
        $headers = [
            'accept' => 'application/json',
            'authorization' => 'Basic ' . base64_encode($paymongoSecretKey . ':'),
            'content-type' => 'application/json'
        ];

        try {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => false, // Disable SSL verification for local development
                    'timeout' => 30
                ])
                ->post($baseUrl . '/v1/payment_intents', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('PayMongo payment intent created successfully', [
                    'payment_intent_id' => $data['data']['id'],
                    'amount' => $data['data']['attributes']['amount'],
                    'status' => $data['data']['attributes']['status'],
                    'test_mode' => $isTestMode
                ]);

                return [
                    'id' => $data['data']['id'],
                    'checkout_url' => $data['data']['attributes']['next_action']['redirect']['url'] ?? null,
                    'client_key' => $data['data']['attributes']['client_key'] ?? null
                ];
            } else {
                Log::error('PayMongo payment intent creation failed', [
                    'response' => $response->body(),
                    'status' => $response->status(),
                    'test_mode' => $isTestMode
                ]);
            }

        } catch (\Exception $e) {
            Log::error('PayMongo API call failed', [
                'error' => $e->getMessage(),
                'test_mode' => $isTestMode
            ]);
        }

        return null;
    }

    /**
     * Retrieve PayMongo payment intent by ID
     * Based on official PayMongo API documentation
     */
    private function getPayMongoPaymentIntent($paymentIntentId)
    {
        $paymongoSecretKey = config('services.paymongo.secret_key');
        $baseUrl = config('services.paymongo.base_url', 'https://api.paymongo.com');
        $isTestMode = config('services.paymongo.test_mode', true);

        // PayMongo API headers (exactly as per their documentation)
        $headers = [
            'accept' => 'application/json',
            'authorization' => 'Basic ' . base64_encode($paymongoSecretKey . ':')
        ];

        try {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => false, // Disable SSL verification for local development
                    'timeout' => 30
                ])
                ->get($baseUrl . '/v1/payment_intents/' . $paymentIntentId);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('PayMongo payment intent retrieved successfully', [
                    'payment_intent_id' => $paymentIntentId,
                    'status' => $data['data']['attributes']['status'],
                    'amount' => $data['data']['attributes']['amount'],
                    'test_mode' => $isTestMode
                ]);

                return $data;
            } else {
                Log::error('PayMongo payment intent retrieval failed', [
                    'payment_intent_id' => $paymentIntentId,
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('PayMongo API call failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Check payment status manually (for debugging/testing)
     */
    public function checkPaymentStatus($paymentIntentId)
    {
        $paymentIntent = $this->getPayMongoPaymentIntent($paymentIntentId);

        if (!$paymentIntent) {
            return response()->json(['error' => 'Payment intent not found'], 404);
        }

        $status = $paymentIntent['data']['attributes']['status'];
        $amount = $paymentIntent['data']['attributes']['amount'];
        $livemode = $paymentIntent['data']['attributes']['livemode'];

        return response()->json([
            'payment_intent_id' => $paymentIntentId,
            'status' => $status,
            'amount' => $amount,
            'livemode' => $livemode,
            'test_mode' => !$livemode
        ]);
    }

    /**
     * Attach payment method to PayMongo payment intent
     * Based on official PayMongo API documentation
     */
    private function attachPayMongoPaymentMethod($paymentIntentId, $paymentMethodId)
    {
        $paymongoSecretKey = config('services.paymongo.secret_key');
        $baseUrl = config('services.paymongo.base_url', 'https://api.paymongo.com');
        $isTestMode = config('services.paymongo.test_mode', true);

        // PayMongo API payload for attaching payment method
        $payload = [
            'data' => [
                'attributes' => [
                    'payment_method' => $paymentMethodId
                ]
            ]
        ];

        // PayMongo API headers (exactly as per their documentation)
        $headers = [
            'accept' => 'application/json',
            'authorization' => 'Basic ' . base64_encode($paymongoSecretKey . ':'),
            'content-type' => 'application/json'
        ];

        try {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => false, // Disable SSL verification for local development
                    'timeout' => 30
                ])
                ->post($baseUrl . '/v1/payment_intents/' . $paymentIntentId . '/attach', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('PayMongo payment method attached successfully', [
                    'payment_intent_id' => $paymentIntentId,
                    'payment_method_id' => $paymentMethodId,
                    'status' => $data['data']['attributes']['status'],
                    'test_mode' => $isTestMode
                ]);

                return $data;
            } else {
                Log::error('PayMongo payment method attachment failed', [
                    'payment_intent_id' => $paymentIntentId,
                    'payment_method_id' => $paymentMethodId,
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('PayMongo payment method attachment failed', [
                'payment_intent_id' => $paymentIntentId,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }



    /**
     * Verify PayMongo webhook signature
     * Based on PayMongo's webhook signature verification requirements
     */
    private function verifyPayMongoWebhook(Request $request)
    {
        $webhookSecret = config('services.paymongo.webhook_secret');
        $signature = $request->header('PayMongo-Signature');

        if (!$webhookSecret || !$signature) {
            Log::warning('Missing webhook secret or signature', [
                'has_secret' => !empty($webhookSecret),
                'has_signature' => !empty($signature)
            ]);
            return false;
        }

        // For test mode, use simplified verification
        if (config('services.paymongo.test_mode', true)) {
            Log::info('Test mode: Skipping signature verification');
            return true;
        }

        // Parse signature header (format: t=timestamp,v1=signature)
        $signatureData = [];
        foreach (explode(',', $signature) as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                $signatureData[$parts[0]] = $parts[1];
            }
        }

        $timestamp = $signatureData['t'] ?? null;
        $signatureHash = $signatureData['v1'] ?? null;

        if (!$timestamp || !$signatureHash) {
            Log::warning('Invalid signature format', ['signature' => $signature]);
            return false;
        }

        // Check timestamp (prevent replay attacks)
        $currentTime = time();
        $timestampAge = $currentTime - (int)$timestamp;

        if ($timestampAge > 300) { // 5 minutes
            Log::warning('Webhook timestamp too old', [
                'timestamp' => $timestamp,
                'age' => $timestampAge
            ]);
            return false;
        }

        // Create expected signature
        $payload = $request->getContent();
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

        // Compare signatures
        $isValid = hash_equals($expectedSignature, $signatureHash);

        if (!$isValid) {
            Log::warning('Webhook signature verification failed', [
                'expected' => $expectedSignature,
                'received' => $signatureHash
            ]);
        }

        return $isValid;
    }

    /**
     * Get user token balance
     */
    public function getBalance()
    {
        $user = Auth::user();
        return response()->json([
            'balance' => $user->token_balance ?? 0
        ]);
    }

    /**
     * Show token purchase history
     */
    public function history()
    {
        $user = Auth::user();
        $transactions = DB::table('token_transactions')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tokens.history', compact('transactions'));
    }
}
