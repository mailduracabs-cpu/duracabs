<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('partner_market_messages')) {
            return;
        }

        Schema::create('partner_market_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('partner_market_posts')->cascadeOnDelete();
            $table->foreignId('sender_vendor_id')->constrained('fleet_transporter_profiles')->restrictOnDelete();
            $table->foreignId('recipient_vendor_id')->constrained('fleet_transporter_profiles')->restrictOnDelete();
            $table->text('message');
            $table->timestamps();
            $table->index(['post_id', 'sender_vendor_id', 'recipient_vendor_id', 'id'], 'pm_messages_conversation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_market_messages');
    }
};
