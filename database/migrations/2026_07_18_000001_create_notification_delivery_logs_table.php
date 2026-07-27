<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_delivery_logs', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('notifiable');
            $table->string('channel', 30);
            $table->string('recipient')->nullable();
            $table->string('event')->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->json('gateway_response')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_logs');
    }
};
