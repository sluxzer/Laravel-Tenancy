<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\post;

beforeEach(function () {
    User::query()->delete();
});

it('can register a new user', function () {
    post('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'token',
            ],
        ]);

    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('John Doe');
});

it('cannot register with invalid email', function () {
    post('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('cannot register with mismatched passwords', function () {
    post('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('cannot register with existing email', function () {
    User::factory()->create([
        'email' => 'john@example.com',
    ]);

    post('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('cannot register with weak password', function () {
    post('/api/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => '123',
        'password_confirmation' => '123',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
