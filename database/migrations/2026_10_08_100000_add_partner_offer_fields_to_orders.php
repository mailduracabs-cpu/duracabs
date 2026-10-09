<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('partner_offer_amount', 10, 2)->nullable();
            $table->string('partner_offer_fare_type', 24)->nullable();
            $table->boolean('partner_offer_parking_included')->default(false);
            $table->text('partner_offer_notes')->nullable();
            $table->string('partner_offer_status', 24)->nullable()->index();
            $table->uuid('partner_offer_revision')->nullable();
            $table->timestamp('partner_offer_assigned_at')->nullable();
            $table->unsignedBigInteger('partner_offer_assigned_by')->nullable();
            $table->timestamp('partner_offer_accepted_at')->nullable();
            $table->unsignedBigInteger('partner_offer_accepted_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['partner_offer_status']);
            $table->dropColumn(['partner_offer_amount', 'partner_offer_fare_type', 'partner_offer_parking_included',
                'partner_offer_notes', 'partner_offer_status', 'partner_offer_revision', 'partner_offer_assigned_at',
                'partner_offer_assigned_by', 'partner_offer_accepted_at', 'partner_offer_accepted_by']);
        });
    }
};
