<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partner_market_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transporter_profile_id')->constrained('fleet_transporter_profiles')->restrictOnDelete();
            $table->string('kind', 20); // booking | free_vehicle
            $table->string('trip_type', 20)->nullable(); // one_way | round_trip
            $table->string('vehicle_class', 80);
            $table->string('pickup_city', 150);
            $table->string('drop_city', 150)->nullable();
            $table->dateTime('pickup_at');
            $table->dateTime('return_at')->nullable();
            $table->decimal('asking_amount', 12, 2)->nullable();
            $table->boolean('negotiable')->default(true);
            $table->string('pricing_mode', 20)->default('quote'); // quote | fixed
            $table->json('inclusions')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('open'); // open | closed
            $table->timestamps();
            $table->index(['kind', 'status', 'pickup_at']);
            $table->index(['transporter_profile_id', 'created_at']);
        });
        Schema::create('partner_market_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('partner_market_posts')->cascadeOnDelete();
            $table->foreignId('transporter_profile_id')->constrained('fleet_transporter_profiles')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending'); // pending | accepted | rejected
            $table->timestamps();
            $table->unique(['post_id', 'transporter_profile_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('partner_market_quotes');
        Schema::dropIfExists('partner_market_posts');
    }
};
