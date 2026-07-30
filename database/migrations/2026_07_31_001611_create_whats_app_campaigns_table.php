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
        Schema::create('whats_app_campaigns', function (Blueprint $table) {
            $table->id();

            $table->string('campaign_name');
            $table->string('campaign_type')->default('template');

            $table->string('template_name')->nullable();
            $table->string('language', 20)->default('en_US');

            $table->string('header_type')->nullable();
            $table->text('header_media')->nullable();

            $table->longText('body')->nullable();
            $table->text('footer')->nullable();
            $table->json('button_payload')->nullable();
            $table->json('template_variables')->nullable();

            $table->string('audience_type')->default('manual');
            $table->json('audience_data')->nullable();

            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->string('status')->default('draft');

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('failure_reason')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('campaign_type');
            $table->index('audience_type');
            $table->index('scheduled_at');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_campaigns');
    }
};