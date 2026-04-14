<?php

declare(strict_types=1);

use App\Models\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Unit\Repositories\TestPlanRepository;

beforeEach(function () {
    $this->repository = new TestPlanRepository;
});

it('can find a model by id', function () {
    $plan = Plan::factory()->create(['name' => 'Test Plan']);

    $result = $this->repository->find($plan->id);

    expect($result)->toBeInstanceOf(Plan::class)
        ->and($result->id)->toBe($plan->id)
        ->and($result->name)->toBe('Test Plan');
});

it('returns null when model not found', function () {
    $result = $this->repository->find(999999);

    expect($result)->toBeNull();
});

it('can find a model or fail', function () {
    $plan = Plan::factory()->create();

    $result = $this->repository->findOrFail($plan->id);

    expect($result)->toBeInstanceOf(Plan::class)
        ->and($result->id)->toBe($plan->id);
});

it('throws exception when findOrFail fails', function () {
    $this->repository->findOrFail(999999);
})->throws(ModelNotFoundException::class);

it('can get all models', function () {
    Plan::factory()->count(3)->sequence(
        ['name' => 'Plan 1', 'slug' => 'plan-1'],
        ['name' => 'Plan 2', 'slug' => 'plan-2'],
        ['name' => 'Plan 3', 'slug' => 'plan-3'],
    )->create();

    $result = $this->repository->all();

    expect($result)->toHaveCount(3)
        ->and($result)->each->toBeInstanceOf(Plan::class);
});

it('can create a model', function () {
    $data = [
        'name' => 'New Plan',
        'slug' => 'new-plan',
        'price_monthly' => 29.99,
        'is_active' => true,
    ];

    $result = $this->repository->create($data);

    expect($result)->toBeInstanceOf(Plan::class)
        ->and($result->name)->toBe('New Plan')
        ->and($result->slug)->toBe('new-plan')
        ->and(Plan::where('slug', 'new-plan')->exists())->toBeTrue();
});

it('can update a model', function () {
    $plan = Plan::factory()->create(['name' => 'Old Name']);

    $result = $this->repository->update($plan, ['name' => 'New Name']);

    expect($result)->toBeTrue()
        ->and($plan->fresh()->name)->toBe('New Name');
});

it('can delete a model', function () {
    $plan = Plan::factory()->create();

    $result = $this->repository->delete($plan);

    expect($result)->toBeTrue()
        ->and(Plan::where('id', $plan->id)->exists())->toBeFalse();
});

it('can paginate models', function () {
    Plan::factory()->count(25)->sequence(fn ($sequence) => [
        'name' => "Plan {$sequence->index}",
        'slug' => "plan-{$sequence->index}",
    ])->create();

    $result = $this->repository->paginate(10);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(25)
        ->and($result->perPage())->toBe(10);
});

it('can chain where conditions', function () {
    Plan::factory()->create(['name' => 'Active Plan', 'slug' => 'active-plan', 'is_active' => true]);
    Plan::factory()->create(['name' => 'Inactive Plan', 'slug' => 'inactive-plan', 'is_active' => false]);

    $result = $this->repository
        ->where('is_active', true)
        ->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->name)->toBe('Active Plan');
});

it('can chain order by', function () {
    Plan::factory()->create(['name' => 'A Plan', 'slug' => 'a-plan', 'price_monthly' => 10]);
    Plan::factory()->create(['name' => 'B Plan', 'slug' => 'b-plan', 'price_monthly' => 20]);
    Plan::factory()->create(['name' => 'C Plan', 'slug' => 'c-plan', 'price_monthly' => 30]);

    $result = $this->repository
        ->orderBy('price_monthly', 'desc')
        ->get();

    expect($result->first()->name)->toBe('C Plan')
        ->and($result->last()->name)->toBe('A Plan');
});
