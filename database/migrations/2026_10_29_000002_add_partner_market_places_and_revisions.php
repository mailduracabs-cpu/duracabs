<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('partner_market_posts', function (Blueprint $table): void {
            $table->string('pickup_place_id', 255)->nullable();
            $table->string('drop_place_id', 255)->nullable();
            $table->unsignedInteger('revision')->default(1);
        });
        Schema::table('partner_market_quotes', function (Blueprint $table): void {
            $table->unsignedInteger('post_revision')->default(1);
        });
        Schema::create('partner_market_post_edits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained('partner_market_posts')->cascadeOnDelete();
            $table->foreignId('actor_vendor_id')->constrained('fleet_transporter_profiles')->restrictOnDelete();
            $table->unsignedInteger('from_revision'); $table->unsignedInteger('to_revision');
            $table->json('before_data'); $table->json('after_data'); $table->json('previous_quotes');
            $table->timestamp('created_at');
            $table->index(['post_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('partner_market_post_edits');
        Schema::table('partner_market_quotes', fn (Blueprint $table) => $table->dropColumn('post_revision'));
        Schema::table('partner_market_posts', fn (Blueprint $table) => $table->dropColumn(['pickup_place_id', 'drop_place_id', 'revision']));
    }
};
