<?php

use App\Http\Controllers\Api\ReplicateWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/replicate', [ReplicateWebhookController::class, 'handle'])
    ->name('api.webhooks.replicate');
