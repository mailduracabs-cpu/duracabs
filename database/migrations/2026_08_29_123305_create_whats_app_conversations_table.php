<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whats_app_conversations', function (Blueprint $table) {
            $table->id();

            // Customer identity
            $table->string('wa_id', 30)->unique();
            $table->string('mobile', 30)->nullable()->index();
            $table->string('customer_name')->nullable();

            // Optional link with Dura Cabs customer
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // WhatsApp business number metadata
            $table->string('phone_number_id')->nullable();
            $table->string('display_phone_number')->nullable();

            // Conversation state
            $table->string('status', 30)->default('open')->index();
            $table->unsignedInteger('unread_count')->default(0);

            // Last message preview
            $table->string('last_message_type', 50)->nullable();
            $table->text('last_message')->nullable();
            $table->string('last_message_direction', 20)->nullable();
            $table->timestamp('last_message_at')->nullable()->index();

            // Customer service window
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamp('service_window_expires_at')->nullable();

            // Admin handling
            $table->unsignedBigInteger('assigned_admin_id')->nullable()->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['status', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whats_app_conversations');
    }
};