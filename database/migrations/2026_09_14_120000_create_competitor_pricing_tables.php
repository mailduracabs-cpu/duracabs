<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitor_price_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('price_id')->unique();
            $table->decimal('baseline_price', 12, 2);
            $table->decimal('floor_price', 12, 2);
            $table->decimal('undercut_amount', 8, 2)->default(50);
            $table->boolean('enabled')->default(true)->index();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('competitor_price_checks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('price_id')->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->json('source_prices')->nullable();
            $table->string('lowest_source', 40)->nullable();
            $table->decimal('lowest_price', 12, 2)->nullable();
            $table->decimal('old_price', 12, 2)->nullable();
            $table->decimal('calculated_price', 12, 2)->nullable();
            $table->decimal('applied_price', 12, 2)->nullable();
            $table->string('status', 30)->index();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitor_price_checks');
        Schema::dropIfExists('competitor_price_rules');
    }
};
