<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Payment Controller (Tenant)
 *
 * Tenant-level payment transaction management.
 */
class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get all payment transactions for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = Transaction::where('tenant_id', $tenant->id)
            ->where('type', 'payment');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        $transactions = $query->with(['invoice', 'subscription'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific payment transaction.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $transaction = Transaction::where('tenant_id', $tenant->id)
            ->where('type', 'payment')
            ->with(['invoice', 'subscription'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    /**
     * Create a payment transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'invoice_id' => 'nullable|exists:invoices,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'gateway' => 'required|string',
            'transaction_id' => 'nullable|string',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
            'payment_token' => 'nullable|string',
        ]);

        $transaction = $this->paymentService->createPayment($tenant, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => $transaction->load(['invoice', 'subscription']),
        ], 201);
    }

    /**
     * Process payment.
     */
    public function process(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $transaction = Transaction::where('tenant_id', $tenant->id)
            ->where('type', 'payment')
            ->findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Payment has already been processed',
            ], 400);
        }

        $result = $this->paymentService->processPayment($transaction);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => $result['payment'] ?? $transaction->fresh(),
        ]);
    }

    /**
     * Cancel payment.
     */
    public function cancel(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $transaction = Transaction::where('tenant_id', $tenant->id)
            ->where('type', 'payment')
            ->findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel processed payment',
            ], 400);
        }

        $transaction->update(['status' => 'failed']);

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled successfully',
            'data' => $transaction,
        ]);
    }

    /**
     * Get payment methods.
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        $user = $request->user();

        $methods = $this->paymentService->getUserPaymentMethods($user);

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Add payment method.
     */
    public function addPaymentMethod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'payment_token' => 'required|string',
            'is_default' => 'boolean',
        ]);

        $user = $request->user();

        $method = $this->paymentService->addPaymentMethod(
            $user,
            $validated['payment_method'],
            $validated['payment_token'],
            $validated['is_default'] ?? false
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment method added successfully',
            'data' => $method,
        ], 201);
    }

    /**
     * Remove payment method.
     */
    public function removePaymentMethod(Request $request, string $methodId): JsonResponse
    {
        $user = $request->user();

        $this->paymentService->removePaymentMethod($user, $methodId);

        return response()->json([
            'success' => true,
            'message' => 'Payment method removed successfully',
        ]);
    }

    /**
     * Set default payment method.
     */
    public function setDefaultPaymentMethod(Request $request, string $methodId): JsonResponse
    {
        $user = $request->user();

        $this->paymentService->setDefaultPaymentMethod($user, $methodId);

        return response()->json([
            'success' => true,
            'message' => 'Default payment method updated successfully',
        ]);
    }
}
