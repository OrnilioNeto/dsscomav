<?php

use App\Modules\LembretesWhatsapp\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Webhook público do WA-AKG (validado por HMAC no controller). O grupo `api`
// e o prefixo /api são aplicados pelo ModuleServiceProvider.
Route::post('/lembretes-whatsapp/status', [WebhookController::class, 'status'])
    ->name('lembretes.whatsapp.webhook');
