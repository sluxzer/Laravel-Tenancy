<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Invoice::query()->delete();
    User::query()->delete();
});

it('can list invoices', function () {
    $user = User::factory()->create();
    Invoice::factory()->for($user)->count(3)->create();

    actingAs($user)
        ->get('/api/billing/invoices')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'invoice_number',
                    'amount',
                    'status',
                    'due_date',
                ],
            ],
        ]);
});

it('can get a specific invoice', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->for($user)->create();

    actingAs($user)
        ->get("/api/billing/invoices/{$invoice->id}")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ],
        ]);
});

it('can filter invoices by status', function () {
    $user = User::factory()->create();
    Invoice::factory()->for($user)->paid()->count(2)->create();
    Invoice::factory()->for($user)->unpaid()->count(1)->create();

    actingAs($user)
        ->get('/api/billing/invoices?status=paid')
        ->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('can send invoice', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->for($user)->unpaid()->create();

    actingAs($user)
        ->post("/api/billing/invoices/{$invoice->id}/send")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Invoice sent successfully',
        ]);
});

it('can download invoice', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->for($user)->create();

    actingAs($user)
        ->get("/api/billing/invoices/{$invoice->id}/download")
        ->assertStatus(200);
});

it('requires authentication to access invoice endpoints', function () {
    get('/api/billing/invoices')
        ->assertStatus(401);

    get('/api/billing/invoices/1')
        ->assertStatus(401);
});
