<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityService;

use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Activity::query()->delete();
    User::query()->delete();
});

it('can log an activity', function () {
    $service = new ActivityService;
    $user = User::factory()->create();

    $activity = $service->log($user, 'user.login', 'User logged in', [
        'ip_address' => '127.0.0.1',
    ]);

    expect($activity->causer_id)->toBe($user->id);
    expect($activity->event)->toBe('user.login');
});

it('can get activity feed', function () {
    $service = new ActivityService;
    $user = User::factory()->create();
    Activity::factory()->forUser($user)->count(5)->create();

    $feed = $service->getFeed($user->id, 10);

    expect($feed)->toHaveCount(5);
});

it('can get activity summary', function () {
    $service = new ActivityService;
    $user = User::factory()->create();
    Activity::factory()->forUser($user)->count(10)->create();

    $summary = $service->getSummary($user->id);

    expect($summary['total_activities'])->toBe(10);
});

it('can get activities by type', function () {
    $service = new ActivityService;
    $user = User::factory()->create();
    Activity::factory()->forUser($user)->create(['event' => 'user.login']);
    Activity::factory()->forUser($user)->create(['event' => 'user.logout']);
    Activity::factory()->forUser($user)->create(['event' => 'user.login']);

    $activities = $service->getByType($user->id, 'user.login');

    expect($activities)->toHaveCount(2);
});

it('can get recent activities', function () {
    $service = new ActivityService;
    $user = User::factory()->create();
    Activity::factory()->forUser($user)->count(10)->create();

    $recent = $service->getRecent($user->id, 5);

    expect($recent)->toHaveCount(5);
});

it('can delete old activities', function () {
    $service = new ActivityService;
    $user = User::factory()->create();
    Activity::factory()->forUser($user)->count(10)->create([
        'created_at' => now()->subMonths(2),
    ]);

    $deleted = $service->deleteOldActivities(90); // 90 days

    expect($deleted)->toBe(10);
    assertDatabaseMissing('activities', ['causer_id' => $user->id]);
});
