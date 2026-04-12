<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'This is your multi-tenant application. The id of current tenant is '.tenant('id');
});
