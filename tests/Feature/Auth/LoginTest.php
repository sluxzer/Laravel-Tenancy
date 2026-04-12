<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\post;

beforeEach(function () {
    // Clean up before each test
    User::query()->delete();
});

it('can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    post('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                ],
                'token',
            ],
        ]);
});

it('cannot login with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    post('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ])
        ->assertStatus(401);
});

it('cannot login with unverified email', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => null,
    ]);

    post('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])
        ->assertStatus(403)
        ->assertJson([
            'message' => 'Your email address is not verified.',
        ]);
});

it('can logout when authenticated', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    post('/api/auth/logout')
        ->withHeader('Authorization', 'Bearer '.$token)
        ->assertStatus(200);

    expect($user->tokens()->count())->toBe(0);
});

it('cannot logout when not authenticated', function () {
    post('/api/auth/logout')
        ->assertStatus(401);
});
