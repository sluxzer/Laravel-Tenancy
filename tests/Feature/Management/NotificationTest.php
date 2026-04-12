<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    Notification::query()->delete();
    User::query()->delete();
});

it('can list notifications', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user)->count(5)->create();

    actingAs($user)
        ->get('/api/management/notifications')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'title',
                    'message',
                    'is_read',
                ],
            ],
        ]);
});

it('can get unread notifications count', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user)->unread()->count(3)->create();
    Notification::factory()->for($user)->read()->count(2)->create();

    actingAs($user)
        ->get('/api/management/notifications/unread-count')
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'count' => 3,
        ]);
});

it('can mark notification as read', function () {
    $user = User::factory()->create();
    $notification = Notification::factory()->for($user)->unread()->create();

    actingAs($user)
        ->post("/api/management/notifications/{$notification->id}/read")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);

    expect($notification->fresh()->is_read)->toBeTrue();
});

it('can mark all notifications as read', function () {
    $user = User::factory()->create();
    Notification::factory()->for($user)->unread()->count(5)->create();

    actingAs($user)
        ->post('/api/management/notifications/read-all')
        ->assertStatus(200);

    expect(Notification::where('user_id', $user->id)->unread()->count())->toBe(0);
});

it('can send bulk notification', function () {
    $users = User::factory()->count(3)->create();
    $userIds = $users->pluck('id')->toArray();

    actingAs($users->first())
        ->post('/api/management/notifications/bulk-send', [
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'type' => 'system',
            'user_ids' => $userIds,
        ])
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Bulk notification sent successfully',
        ]);
});

it('requires authentication to access notification endpoints', function () {
    get('/api/management/notifications')
        ->assertStatus(401);

    post('/api/management/notifications/1/read')
        ->assertStatus(401);
});
