<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('{tenant}')->middleware('tenancy.path')->group(function () {
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Tenant info
    Route::get('/', function () {
        return response()->json([
            'tenant_id' => tenant('id'),
            'message' => 'Tenant accessed successfully',
        ]);
    });
});
