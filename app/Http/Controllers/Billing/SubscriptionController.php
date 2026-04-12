<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Subscription Controller (Tenant)
 *
 * Tenant-level subscription management.
 */
class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get current subscription for user.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        if (! $subscription) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => null,
                    'plan' => null,
                    'status' => 'inactive',
                    'current_period_start' => null,
                    'current_period_end' => null,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $subscription->load('plan'),
        ]);
    }

    /**
     * Get all subscriptions for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;

        $query = Subscription::where('tenant_id', $tenant->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $subscriptions = $query->with(['plan', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $subscriptions->items(),
            'pagination' => [
                'total' => $subscriptions->total(),
                'per_page' => $subscriptions->perPage(),
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'payment_method' => 'required|string',
            'payment_token' => 'nullable|string',
            'billing_cycle' => 'required|in:monthly,yearly,quarterly',
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($validated['plan_id']);

        $subscription = $this->subscriptionService->create(
            $user,
            $plan,
            $validated['payment_method'],
            $validated['payment_token'] ?? null,
            $validated['billing_cycle']
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully',
            'data' => $subscription->load('plan'),
        ], 201);
    }

    /**
     * Update a subscription.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:active,cancelled,paused,expired',
        ]);

        $subscription->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subscription updated successfully',
            'data' => $subscription->load('plan'),
        ]);
    }

    /**
     * Upgrade subscription.
     */
    public function upgrade(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $subscription = Subscription::findOrFail($id);
        $newPlan = Plan::findOrFail($validated['plan_id']);

        $subscription = $this->subscriptionService->upgrade($subscription, $newPlan);

        return response()->json([
            'success' => true,
            'message' => 'Subscription upgraded successfully',
            'data' => $subscription->load('plan'),
        ]);
    }

    /**
     * Downgrade subscription.
     */
    public function downgrade(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $subscription = Subscription::findOrFail($id);
        $newPlan = Plan::findOrFail($validated['plan_id']);

        $subscription = $this->subscriptionService->downgrade($subscription, $newPlan);

        return response()->json([
            'success' => true,
            'message' => 'Subscription downgrade scheduled',
            'data' => $subscription->load('plan'),
        ]);
    }

    /**
     * Pause subscription.
     */
    public function pause(string $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);
        $subscription = $this->subscriptionService->pause($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Subscription paused successfully',
            'data' => $subscription->load('plan'),
        ]);
    }

    /**
     * Resume subscription.
     */
    public function resume(string $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);
        $subscription = $this->subscriptionService->resume($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Subscription resumed successfully',
            'data' => $subscription->load('plan'),
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $subscription = Subscription::findOrFail($id);
        $subscription = $this->subscriptionService->cancel($subscription, $validated['reason'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
            'data' => $subscription->load('plan'),
        ]);
    }

    /**
     * Renew subscription.
     */
    public function renew(string $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);
        $subscription = $this->subscriptionService->renew($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Subscription renewed successfully',
            'data' => $subscription->load('plan'),
        ]);
    }

    /**
     * Apply voucher to subscription.
     */
    public function applyVoucher(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $subscription = Subscription::findOrFail($id);
        $user = $request->user();
        $voucherService = app(VoucherService::class);

        $validation = $voucherService->validate($validated['code'], $user);

        if (! $validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
            ], 400);
        }

        $result = $voucherService->apply($validated['code'], $user, $subscription->plan->price_monthly, $subscription);

        return response()->json([
            'success' => true,
            'message' => 'Voucher applied successfully',
        ]);
    }
}
