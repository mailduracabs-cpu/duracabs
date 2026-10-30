<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('partner_market_bank_accounts')) Schema::create('partner_market_bank_accounts', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('vendor_id'); $t->unsignedInteger('revision');
            $t->longText('encrypted_details'); $t->string('last_four', 4); $t->string('details_fingerprint', 64);
            $t->unsignedBigInteger('changed_by_user_id')->nullable(); $t->dateTime('created_at')->nullable();
            $t->unique(['vendor_id', 'revision'], 'pm_bank_vendor_revision');
        });
        if (!Schema::hasColumn('partner_market_admin_settlements', 'payout_bank_account_id')) Schema::table('partner_market_admin_settlements', fn (Blueprint $t) => $t->unsignedBigInteger('payout_bank_account_id')->nullable()->index());
    }
    public function down(): void { /* Preserve bank versions referenced by financial history. */ }
};
