<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whats_app_campaign_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                ->constrained('whats_app_campaigns')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('customer_id')->nullable();

            $table->string('name')->nullable();
            $table->string('mobile', 20);
            $table->string('wa_id')->nullable();

            $table->string('template_name')->nullable();

            $table->json('variables')->nullable();

            $table->string('status')->default('pending');
            // pending, processing, accepted, sent, delivered, read, failed

            $table->string('meta_message_id')->nullable();

            $table->text('meta_response')->nullable();

            $table->text('failure_reason')->nullable();

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->unsignedSmallInteger('retry_count')->default(0);

            $table->timestamps();

            $table->index('campaign_id');
            $table->index('customer_id');
            $table->index('mobile');
            $table->index('status');
            $table->index('meta_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_campaign_recipients');
    }
};