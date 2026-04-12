<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Route::inertia('/', 'welcome', [
//     'canRegister' => Features::enabled(Features::registration()),
// ])->name('home');

Route::get('/', function () {
    // return view('welcome');
    dd(\App\Models\User::first()->email);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});


foreach (config('tenancy.identification.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        // your central routes
    });
}
require __DIR__.'/settings.php';
