<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TokenController extends Controller
{
    /**
     * Process token purchase with PayMongo integration
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'payment_method' => 'required|string|in:gcash',
            'total_amount' => 'required|numeric|min:5'
        ]);

        $user = Auth::user();
        $quantity = $request->quantity;
        $totalAmount = $request->total_amount;

        // Verify the calculation
        $expectedTotal = $quantity * 5.00; // 1 token = 5 pesos
        if (abs($totalAmount - $expectedTotal) > 0.01) {
            return redirect()->back()
                ->with('error', 'Invalid amount calculation. Please try again.');
        }

        try {
            DB::beginTransaction();

            // Create PayMongo payment intent
            $paymentIntent = $this->createPayMongoPaymentIntent($totalAmount, $user);

            if (!$paymentIntent) {
                throw new \Exception('Failed to create payment intent');
            }

            // Store pending transaction
            $transaction = DB::table('token_transactions')->insertGetId([
                'user_id' => $user->id,
                'quantity' => $quantity,
                'amount' => $totalAmount,
                'payment_method' => 'gcash',
                'payment_intent_id' => $paymentIntent['id'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            // Redirect to PayMongo checkout
            return redirect($paymentIntent['checkout_url']);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Token purchase failed', [
                'user_id' => $user->id,
                'quantity' => $quantity,
                'amount' => $totalAmount,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Purchase failed. Please try again.');
        }
    }

    /**
     * Handle PayMongo webhook for payment confirmation
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();

        // Verify webhook signature (implement proper verification)
        if (!$this->verifyPayMongoWebhook($request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $paymentIntentId = $payload['data']['id'] ?? null;
        $status = $payload['data']['attributes']['status'] ?? null;

        if (!$paymentIntentId || !$status) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        try {
            DB::beginTransaction();

            // Find the transaction
            $transaction = DB::table('token_transactions')
                ->where('payment_intent_id', $paymentIntentId)
                ->first();

            if (!$transaction) {
                throw new \Exception('Transaction not found');
            }

            if ($status === 'succeeded') {
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
                    'new_balance' => $newBalance
                ]);
            } else {
                // Payment failed
                DB::table('token_transactions')
                    ->where('id', $transaction->id)
                    ->update([
                        'status' => 'failed',
                        'updated_at' => now()
                    ]);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Webhook processing failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Create PayMongo payment intent
     */
    private function createPayMongoPaymentIntent($amount, $user)
    {
        $paymongoSecretKey = config('services.paymongo.secret_key');
        $baseUrl = config('services.paymongo.base_url', 'https://api.paymongo.com');

        $payload = [
            'data' => [
                'attributes' => [
                    'amount' => (int)($amount * 100), // Convert to centavos
                    'currency' => 'PHP',
                    'description' => 'Token Purchase - SkillsXchange',
                    'statement_descriptor' => 'SkillsXchange Tokens',
                    'metadata' => [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'purchase_type' => 'tokens'
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($paymongoSecretKey . ':'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post($baseUrl . '/v1/payment_intents', $payload);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'id' => $data['data']['id'],
                'checkout_url' => $data['data']['attributes']['next_action']['redirect']['url']
            ];
        }

        Log::error('PayMongo payment intent creation failed', [
            'response' => $response->body(),
            'status' => $response->status()
        ]);

        return null;
    }

    /**
     * Verify PayMongo webhook signature
     */
    private function verifyPayMongoWebhook(Request $request)
    {
        $webhookSecret = config('services.paymongo.webhook_secret');
        $signature = $request->header('PayMongo-Signature');

        if (!$signature || !$webhookSecret) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get user's current token balance (API endpoint)
     */
    public function getBalance()
    {
        $user = Auth::user();
        return response()->json([
            'balance' => $user->token_balance ?? 0
        ]);
    }

    /**
     * Display user's token history
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
