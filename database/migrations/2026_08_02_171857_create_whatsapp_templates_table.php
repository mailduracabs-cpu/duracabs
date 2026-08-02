<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('template_name')->unique();

            $table->string('category')->default('UTILITY');
            $table->string('language')->default('en');

            $table->string('header_type')->nullable();
            $table->text('header_text')->nullable();
            $table->text('header_media')->nullable();

            $table->longText('body');
            $table->text('footer')->nullable();

            $table->json('variables')->nullable();
            $table->json('buttons')->nullable();

            $table->string('status')->default('draft');
            $table->string('meta_status')->default('not_submitted');

            $table->text('meta_rejection_reason')->nullable();
            $table->string('meta_template_id')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->index(['category', 'language']);
            $table->index(['meta_status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};