<?php

use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Middleware\VerifyWhatsAppSignature;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')->middleware('throttle:webhooks')->group(function () {
    Route::post('whatsapp', [WhatsAppWebhookController::class, 'handle'])
        ->middleware(VerifyWhatsAppSignature::class)
        ->name('webhooks.whatsapp');
});
