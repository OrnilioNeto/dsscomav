<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_reminders', function (Blueprint $table) {
            // PENDING | SENT | DELIVERED | READ | FAILED (webhook do WA-AKG)
            $table->string('delivery_status', 20)->nullable()->after('status');
            $table->timestamp('delivered_at')->nullable()->after('enviado_em');
            $table->index('gateway_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('training_reminders', function (Blueprint $table) {
            $table->dropIndex(['gateway_message_id']);
            $table->dropColumn(['delivery_status', 'delivered_at']);
        });
    }
};
