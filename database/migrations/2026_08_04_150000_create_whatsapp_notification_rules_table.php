<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'whatsapp_notification_rules',
            function (Blueprint $table): void {
                $table->id();

                $table->string('name');
                $table->string('event_key', 160)->unique();
                $table->string('template_key', 120)->index();

                $table->boolean('send_customer')->default(false);
                $table->boolean('send_vendor')->default(false);
                $table->boolean('send_driver')->default(false);

                $table->boolean('send_admin')->default(false);
                $table->boolean('send_sales')->default(false);
                $table->boolean('send_operations')->default(false);
                $table->boolean('send_accounts')->default(false);
                $table->boolean('send_support')->default(false);

                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();

                $table->timestamps();

                $table->index([
                    'event_key',
                    'is_active',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_notification_rules');
    }
};