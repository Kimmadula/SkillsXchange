<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TradeFeeSetting;

class TokenController extends Controller
{
    /**
     * Handle token purchase
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'total_amount' => 'required|numeric|min:100' // PayMongo Links require minimum ₱100
        ]);

        $user = Auth::user();
        $quantity = $request->quantity;
        $amount = $request->total_amount;

        // Verify amount calculation using configurable token price (default ₱5 per token)
        $configuredPrice = (float) (TradeFeeSetting::getFeeAmount('token_price') ?: 5);
        $expectedAmount = $quantity * $configuredPrice;
        if (abs($amount - $expectedAmount) > 0.01) {
            return redirect()->back()->withErrors(['amount' => 'Amount calculation mismatch']);
        }

        // Ensure minimum amount for PayMongo Links (₱100 minimum)
        if ($amount < 100) {
            return redirect()->back()->withErrors(['amount' => 'Minimum purchase amount is ₱100.00 for PayMongo payments']);
        }

        try {
            DB::beginTransaction();

            // Create PayMongo payment link
            $paymentLink = $this->createPayMongoPaymentIntent($amount, $user);

            if (!$paymentLink) {
                DB::rollback();
                return redirect()->back()->withErrors(['payment' => 'Failed to create payment link. Please try again.']);
            }

            // Create transaction record with reference number
            $transactionId = DB::table('token_transactions')->insertGetId([
                'user_id' => $user->id,
                'quantity' => $quantity,
                'amount' => $amount,
                'payment_method' => 'paymongo', // Default to PayMongo since user chooses method on their platform
                'payment_intent_id' => $paymentLink['reference_number'], // Store reference number in payment_intent_id field
                'status' => 'pending',
                'notes' => 'Payment link created',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            // Redirect to PayMongo checkout
            if (isset($paymentLink['checkout_url']) && !empty($paymentLink['checkout_url'])) {
                Log::info('Redirecting to PayMongo checkout', [
                    'checkout_url' => $paymentLink['checkout_url'],
                    'link_id' => $paymentLink['id']
                ]);
                return redirect($paymentLink['checkout_url'])
                    ->with('success', 'Redirecting to payment...');
            } else {
                // Log the issue for debugging
                Log::error('No checkout URL received from PayMongo', [
                    'link_id' => $paymentLink['id'] ?? 'unknown',
                    'payment_link' => $paymentLink,
                    'user_id' => $user->id
                ]);

                // If no checkout URL, show error message
                return redirect()->route('tokens.history')
                    ->withErrors(['payment' => 'Unable to create payment checkout. Please try again or contact support.']);
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
     * Handle premium subscription
     */
    public function subscribe(Request $request)
    {
        $user = Auth::user();

        // Allow premium users to renew their subscription (manual renewal)
        // Premium users can pay again to extend their subscription

        // Get premium price from settings (default ₱299/month)
        $premiumPrice = (float) (TradeFeeSetting::getFeeAmount('premium_price') ?: 299);

        // Ensure minimum amount for PayMongo Links (₱100 minimum)
        if ($premiumPrice < 100) {
            return redirect()->back()->withErrors(['amount' => 'Premium subscription price must be at least ₱100.00']);
        }

        try {
            DB::beginTransaction();

            // Create PayMongo payment link
            $paymentLink = $this->createPayMongoPaymentIntent($premiumPrice, $user);

            if (!$paymentLink) {
                DB::rollback();
                return redirect()->back()->withErrors(['payment' => 'Failed to create payment link. Please try again.']);
            }

            // Create transaction record for premium subscription
            $transactionId = DB::table('token_transactions')->insertGetId([
                'user_id' => $user->id,
                'quantity' => 0, // No tokens for premium subscription
                'amount' => $premiumPrice,
                'payment_method' => 'paymongo',
                'payment_intent_id' => $paymentLink['reference_number'],
                'status' => 'pending',
                'notes' => 'Premium subscription payment',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Store subscription info in transaction notes (we'll use this in webhook)
            DB::table('token_transactions')
                ->where('id', $transactionId)
                ->update(['notes' => json_encode([
                    'type' => 'premium_subscription',
                    'transaction_id' => $transactionId
                ])]);

            DB::commit();

            // Redirect to PayMongo checkout
            if (isset($paymentLink['checkout_url']) && !empty($paymentLink['checkout_url'])) {
                Log::info('Redirecting to PayMongo checkout for premium subscription', [
                    'checkout_url' => $paymentLink['checkout_url'],
                    'link_id' => $paymentLink['id'],
                    'user_id' => $user->id
                ]);
                return redirect($paymentLink['checkout_url'])
                    ->with('success', 'Redirecting to payment for Premium subscription...');
            } else {
                Log::error('No checkout URL received from PayMongo for premium subscription', [
                    'link_id' => $paymentLink['id'] ?? 'unknown',
                    'payment_link' => $paymentLink,
                    'user_id' => $user->id
                ]);

                return redirect()->route('tokens.history')
                    ->withErrors(['payment' => 'Unable to create payment checkout. Please try again or contact support.']);
            }

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Premium subscription failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->withErrors(['error' => 'Subscription failed. Please try again.']);
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

        // Extract event data based on event type
        $eventType = $payload['data']['attributes']['type'] ?? null;
        $referenceNumber = null;
        $status = null;

        if ($eventType === 'link.payment.paid' || $eventType === 'link.payment.unpaid' || $eventType === 'link.payment.canceled') {
            // Extract reference_number from link events
            $referenceNumber = $payload['data']['attributes']['data']['attributes']['reference_number'] ?? null;
            $status = $payload['data']['attributes']['data']['attributes']['status'] ?? null;
        } elseif ($eventType === 'payment.paid' || $eventType === 'payment.failed') {
            // Extract reference_number from payment events
            $referenceNumber = $payload['data']['attributes']['data']['attributes']['external_reference_number'] ?? null;
            $status = $payload['data']['attributes']['data']['attributes']['status'] ?? null;
        }

        if (!$referenceNumber || !$status) {
            Log::error('Invalid webhook payload', [
                'event_type' => $eventType,
                'payload' => $payload
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        Log::info('Processing webhook', [
            'event_type' => $eventType,
            'reference_number' => $referenceNumber,
            'status' => $status
        ]);

        try {
            DB::beginTransaction();

            // Find the transaction by reference_number (stored as payment_intent_id in our system)
            $transaction = DB::table('token_transactions')
                ->where('payment_intent_id', $referenceNumber)
                ->lockForUpdate() // Lock the row to prevent race conditions
                ->first();

            if (!$transaction) {
                Log::warning('Transaction not found', [
                    'reference_number' => $referenceNumber
                ]);
                DB::rollback();
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // ⭐ IDEMPOTENCY CHECK - Prevent duplicate processing
            if ($transaction->status === 'completed') {
                Log::info('Transaction already processed (idempotency check)', [
                    'reference_number' => $referenceNumber,
                    'status' => $transaction->status
                ]);
                DB::commit();
                return response()->json(['status' => 'success', 'message' => 'Already processed']);
            }

            // Handle different payment statuses for PayMongo Links
            switch ($status) {
                case 'paid':
                    $this->handlePaymentSuccess($transaction, $referenceNumber, $isTestMode);
                    break;
                case 'unpaid':
                case 'failed':
                    $this->handlePaymentFailure($transaction, $referenceNumber);
                    break;
                case 'canceled':
                case 'cancelled':
                    $this->handlePaymentCancellation($transaction, $referenceNumber);
                    break;
                default:
                    Log::info('Unhandled payment status', [
                        'status' => $status,
                        'reference_number' => $referenceNumber
                    ]);
            }

            DB::commit();
            Log::info('Webhook processed successfully', [
                'reference_number' => $referenceNumber,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Webhook processing failed', [
                'reference_number' => $referenceNumber,
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

        $user = DB::table('users')->where('id', $transaction->user_id)->first();

        // Check if this is a premium subscription
        $notes = $transaction->notes ?? '';
        $isPremiumSubscription = false;

        // Try to parse JSON notes
        if (!empty($notes)) {
            $notesData = json_decode($notes, true);
            if (is_array($notesData) && isset($notesData['type']) && $notesData['type'] === 'premium_subscription') {
                $isPremiumSubscription = true;
            } elseif (stripos($notes, 'premium') !== false && stripos($notes, 'subscription') !== false) {
                $isPremiumSubscription = true;
            }
        }

        if ($isPremiumSubscription) {
            // Get premium duration from settings (default 1 month)
            $durationMonths = (int) (TradeFeeSetting::getFeeAmount('premium_duration_months') ?: 1);
            if ($durationMonths < 1) {
                $durationMonths = 1; // Ensure at least 1 month
            }

            // Calculate expiration date
            $expiresAt = now()->addMonths($durationMonths);

            // If user already has premium, extend from current expiration date (if not expired)
            $currentUser = DB::table('users')->where('id', $transaction->user_id)->first();
            if ($currentUser && $currentUser->plan === 'premium' && $currentUser->premium_expires_at) {
                $currentExpiresAt = \Carbon\Carbon::parse($currentUser->premium_expires_at);
                // If current expiration is in the future, extend from there
                if ($currentExpiresAt->isFuture()) {
                    $expiresAt = $currentExpiresAt->addMonths($durationMonths);
                }
            }

            // Update user plan to premium with expiration date
            DB::table('users')
                ->where('id', $transaction->user_id)
                ->update([
                    'plan' => 'premium',
                    'premium_expires_at' => $expiresAt
                ]);

            Log::info('Premium subscription activated', [
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'duration_months' => $durationMonths,
                'expires_at' => $expiresAt->toDateTimeString(),
                'test_mode' => $isTestMode,
                'payment_intent_id' => $paymentIntentId
            ]);
        } else {
            // Add tokens to user's account (regular token purchase)
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
     * Create PayMongo Link for redirect-based payment
     * This is the correct approach for PayMongo redirect payments
     */
    private function createPayMongoPaymentIntent($amount, $user)
    {
        $paymongoSecretKey = config('services.paymongo.secret_key');
        $baseUrl = config('services.paymongo.base_url', 'https://api.paymongo.com');
        $isTestMode = config('services.paymongo.test_mode', true);

        // Use PayMongo Link API for redirect-based payments
        $appUrl = config('app.url', 'https://skillsxchange.site');

        $payload = [
            'data' => [
                'attributes' => [
                    'amount' => (int)($amount * 100), // Convert to centavos
                    'currency' => 'PHP',
                    'description' => 'Token Purchase - SkillsXchange',
                    'remarks' => 'SkillsXchange Token Purchase',
                    'redirect' => [
                        'success' => $appUrl . '/tokens/history?payment=success',
                        'failed' => $appUrl . '/tokens/history?payment=failed'
                    ],
                    'billing' => [
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'email' => $user->email
                    ]
                ]
            ]
        ];

        // PayMongo API headers
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
                ->post($baseUrl . '/v1/links', $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('PayMongo Link created successfully', [
                    'link_id' => $data['data']['id'],
                    'amount' => $data['data']['attributes']['amount'],
                    'checkout_url' => $data['data']['attributes']['checkout_url'],
                    'test_mode' => $isTestMode,
                    'full_response' => $data
                ]);

                return [
                    'id' => $data['data']['id'],
                    'reference_number' => $data['data']['attributes']['reference_number'],
                    'checkout_url' => $data['data']['attributes']['checkout_url'],
                    'status' => $data['data']['attributes']['status'] ?? null
                ];
            } else {
                Log::error('PayMongo Link creation failed', [
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
    public function history(Request $request)
    {
        $user = Auth::user();
        // Optional date filters
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $quick = $request->get('range'); // e.g., 1w, 1m

        // Apply quick range if provided
        if (!$fromDate && $quick) {
            if ($quick === '1w') {
                $fromDate = now()->subWeek()->toDateString();
            } elseif ($quick === '1m') {
                $fromDate = now()->subMonth()->toDateString();
            }
            $toDate = $toDate ?: now()->toDateString();
        }

        // If one date is provided without the other, return with error
        if (($fromDate && !$toDate) || ($toDate && !$fromDate)) {
            return redirect()->route('tokens.history')
                ->withErrors(['date_range' => 'Please select both From and To dates.']);
        }

        $query = DB::table('token_transactions')
            ->where('user_id', $user->id);

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // Paginate 5 per page and keep query string for filters
        $transactions = $query->orderBy('created_at', 'desc')->paginate(5)->withQueryString();

        // Handle payment redirect parameters
        $paymentStatus = $request->get('payment');
        $message = null;
        $messageType = null;

        if ($paymentStatus === 'success') {
            $message = 'Payment completed successfully! Your tokens have been added to your account.';
            $messageType = 'success';
        } elseif ($paymentStatus === 'failed') {
            $message = 'Payment failed. Please try again or contact support if the issue persists.';
            $messageType = 'error';
        }

        return view('tokens.history', compact('transactions', 'message', 'messageType', 'fromDate', 'toDate', 'quick'));
    }
}
