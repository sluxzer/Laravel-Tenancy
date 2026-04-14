<?php

namespace App\Providers;

use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\RefundRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Eloquent\InvoiceRepository;
use App\Repositories\Eloquent\PaymentRepository;
use App\Repositories\Eloquent\PlanRepository;
use App\Repositories\Eloquent\RefundRepository;
use App\Repositories\Eloquent\SubscriptionRepository;
use App\Repositories\Eloquent\TransactionRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            SubscriptionRepositoryInterface::class,
            SubscriptionRepository::class
        );

        $this->app->bind(
            PlanRepositoryInterface::class,
            PlanRepository::class
        );

        // TODO: Bind other repositories when they are implemented
        // $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        // $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        // $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        // $this->app->bind(RefundRepositoryInterface::class, RefundRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
