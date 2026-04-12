<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\NotificationService;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    User::query()->delete();
    Notification::query()->delete();
    NotificationPreference::query()->delete();
});

it('can create a notification', function () {
    $service = new NotificationService;
    $user = User::factory()->create();

    $notification = $service->createNotification([
        'tenant_id' => 1,
        'user_id' => $user->id,
        'type' => 'test',
        'title' => 'Test Notification',
        'message' => 'This is a test message',
    ]);

    assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'type' => 'test',
        'title' => 'Test Notification',
    ]);

    expect($notification->user_id)->toBe($user->id);
});

it('can mark notification as read', function () {
    $service = new NotificationService;
    $notification = Notification::factory()->unread()->create();

    $updated = $service->markAsRead($notification);

    expect($updated->is_read)->toBeTrue()
        ->and($updated->read_at)->not->toBeNull();
});

it('can mark all notifications as read for user', function () {
    $service = new NotificationService;
    $user = User::factory()->create();
    Notification::factory()->for($user)->unread()->count(5)->create();

    $count = $service->markAllAsRead($user);

    expect($count)->toBe(5);
    expect(Notification::where('user_id', $user->id)->unread()->count())->toBe(0);
});

it('can get unread notification count', function () {
    $service = new NotificationService;
    $user = User::factory()->create();
    Notification::factory()->for($user)->unread()->count(3)->create();
    Notification::factory()->for($user)->read()->count(2)->create();

    $count = $service->getUnreadCount($user);

    expect($count)->toBe(3);
});

it('can get user notifications', function () {
    $service = new NotificationService;
    $user = User::factory()->create();
    Notification::factory()->for($user)->count(10)->create();

    $notifications = $service->getUserNotifications($user, 5);

    expect($notifications)->toHaveCount(5);
});

it('can send bulk notification', function () {
    $service = new NotificationService;
    $users = User::factory()->count(3)->create();

    $service->sendBulkNotification($users->pluck('id')->toArray(), [
        'type' => 'bulk',
        'title' => 'Bulk Test',
        'message' => 'This is a bulk message',
    ]);

    expect(Notification::where('type', 'bulk')->count())->toBe(3);
});

it('can update user notification preferences', function () {
    $service = new NotificationService;
    $user = User::factory()->create();

    $service->updatePreferences($user, [
        'email' => true,
        'sms' => false,
        'push' => true,
    ]);

    assertDatabaseHas('notification_preferences', [
        'user_id' => $user->id,
        'type' => 'email',
        'is_enabled' => true,
    ]);

    assertDatabaseHas('notification_preferences', [
        'user_id' => $user->id,
        'type' => 'sms',
        'is_enabled' => false,
    ]);
});

it('can get user notification preferences', function () {
    $service = new NotificationService;
    $user = User::factory()->create();

    NotificationPreference::create([
        'user_id' => $user->id,
        'type' => 'email',
        'is_enabled' => true,
    ]);

    $preferences = $service->getUserPreferences($user);

    expect($preferences)->toContain('email');
});
