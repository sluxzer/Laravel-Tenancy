<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Payment Controller (Tenant)
 *
 * Tenant-level payment management.
 */
class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get all payments for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = Payment::where('tenant_id', $tenant->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        $payments = $query->with(['invoice', 'subscription'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'pagination' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific payment.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $payment = Payment::where('tenant_id', $tenant->id)
            ->with(['invoice', 'subscription'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Create a payment.
     */
    public function store(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $validated = $request->validate([
            'invoice_id' => 'nullable|exists:invoices,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'amount' => 'required|numeric|min:0',
            'currency_code' => 'required|string|max:3',
            'payment_method' => 'required|string',
            'payment_token' => 'nullable|string',
            'gateway' => 'required|string',
            'transaction_id' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $payment = $this->paymentService->createPayment($tenant, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => $payment->load(['invoice', 'subscription']),
        ], 201);
    }

    /**
     * Process payment.
     */
    public function process(Request $request, string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $payment = Payment::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Payment has already been processed',
            ], 400);
        }

        $result = $this->paymentService->processPayment($payment);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => $result['payment'],
        ]);
    }

    /**
     * Cancel payment.
     */
    public function cancel(string $id): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $payment = Payment::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel processed payment',
            ], 400);
        }

        $payment->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled successfully',
            'data' => $payment,
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
