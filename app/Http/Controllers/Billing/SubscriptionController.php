<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\ApplyVoucherRequest;
use App\Http\Requests\Billing\CancelSubscriptionRequest;
use App\Http\Requests\Billing\CreateSubscriptionRequest;
use App\Http\Requests\Billing\DowngradeSubscriptionRequest;
use App\Http\Requests\Billing\PauseSubscriptionRequest;
use App\Http\Requests\Billing\RenewSubscriptionRequest;
use App\Http\Requests\Billing\ResumeSubscriptionRequest;
use App\Http\Requests\Billing\UpdateSubscriptionRequest;
use App\Http\Requests\Billing\UpgradeSubscriptionRequest;
use App\Http\Resources\Billing\SubscriptionResource;
use App\Http\Resources\JsonResourceCollection;
use App\Models\Subscription;
use App\Services\Contracts\SubscriptionServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Subscription Controller (Tenant)
 *
 * Thin controller handling HTTP concerns for tenant subscription management.
 * All business logic is delegated to SubscriptionServiceInterface.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService
    ) {}

    /**
     * Get all subscriptions for tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;
        $status = $request->input('status');
        $perPage = (int) $request->input('per_page', 20);

        $subscriptions = $this->subscriptionService->getForTenant(
            $tenant->id,
            $status,
            $perPage
        );

        return response()->json(
            JsonResourceCollection::paginated($subscriptions, SubscriptionResource::class)
        );
    }

    /**
     * Get current subscription for tenant.
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = tenancy()->tenant;
        $subscription = $this->subscriptionService->getActiveSubscription($tenant);

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

        return response()->json(
            JsonResourceCollection::single(SubscriptionResource::make($subscription->load('plan')))
        );
    }

    /**
     * Create a new subscription.
     */
    public function store(CreateSubscriptionRequest $request): JsonResponse
    {
        $tenant = tenancy()->tenant;
        $plan = $request->getPlan();
        $billingCycle = $request->input('billing_cycle');
        $userId = $request->input('user_id');
        $metadata = $request->getMetadata();

        $subscription = $this->subscriptionService->create(
            $tenant,
            $plan,
            $billingCycle,
            $userId ? (int) $userId : null,
            $metadata
        );

        return response()->json(
            JsonResourceCollection::success(
                'Subscription created successfully',
                SubscriptionResource::make($subscription->load('plan'))->resolve()
            ),
            201
        );
    }

    /**
     * Update a subscription.
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        $data = $request->validated();

        $subscription = $this->subscriptionService->update($subscription, $data);

        return response()->json(
            JsonResourceCollection::success(
                'Subscription updated successfully',
                SubscriptionResource::make($subscription->load('plan'))->resolve()
            )
        );
    }

    /**
     * Upgrade subscription.
     */
    public function upgrade(UpgradeSubscriptionRequest $request): JsonResponse
    {
        $subscription = $request->getSubscription();
        $newPlan = $request->getNewPlan();

        $subscription = $this->subscriptionService->upgrade($subscription, $newPlan);

        return response()->json(
            JsonResourceCollection::success(
                'Subscription upgraded successfully',
                SubscriptionResource::make($subscription->load('plan'))->resolve()
            )
        );
    }

    /**
     * Downgrade subscription.
     */
    public function downgrade(DowngradeSubscriptionRequest $request): JsonResponse
    {
        $subscription = $request->getSubscription();
        $newPlan = $request->getNewPlan();

        $subscription = $this->subscriptionService->downgrade($subscription, $newPlan);

        return response()->json(
            JsonResourceCollection::success(
                'Subscription downgrade scheduled',
                SubscriptionResource::make($subscription->load('plan'))->resolve()
            )
        );
    }

    /**
     * Pause subscription.
     */
    public function pause(PauseSubscriptionRequest $request): JsonResponse
    {
        $subscription = $request->getSubscription();
        $subscription = $this->subscriptionService->pause($subscription);

        return response()->json(
            JsonResourceCollection::success(
                'Subscription paused successfully',
                SubscriptionResource::make($subscription->load('plan'))->resolve()
            )
        );
    }

    /**
     * Resume subscription.
     */
    public function resume(ResumeSubscriptionRequest $request): JsonResponse
    {
        $subscription = $request->getSubscription();
        $subscription = $this->subscriptionService->resume($subscription);

        return response()->json(
            JsonResourceCollection::success(
                'Subscription resumed successfully',
                SubscriptionResource::make($subscription->load('plan'))->resolve()
            )
        );
    }

    /**
     * Cancel subscription.
     */
    public function cancel(CancelSubscriptionRequest $request): JsonResponse
    {
        $subscription = $request->getSubscription();
        $reason = $request->getReason();

        $subscription = $this->subscriptionService->cancel($subscription, $reason);

        return response()->json(
            JsonResourceCollection::success(
                'Subscription cancelled successfully',
                SubscriptionResource::make($subscription->load('plan'))->resolve()
            )
        );
    }

    /**
     * Renew subscription.
     */
    public function renew(RenewSubscriptionRequest $request): JsonResponse
    {
        $subscription = $request->getSubscription();
        $subscription = $this->subscriptionService->renew($subscription);

        return response()->json(
            JsonResourceCollection::success(
                'Subscription renewed successfully',
                SubscriptionResource::make($subscription->load('plan'))->resolve()
            )
        );
    }

    /**
     * Apply voucher to subscription.
     */
    public function applyVoucher(ApplyVoucherRequest $request): JsonResponse
    {
        $subscription = $request->getSubscription();
        $voucher = $request->getVoucher();

        $this->subscriptionService->applyVoucher($subscription, $voucher);

        return response()->json(
            JsonResourceCollection::success('Voucher applied successfully')
        );
    }
}
