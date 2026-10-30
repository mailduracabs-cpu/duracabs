<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['active_payment_id', 'assigned_payment_id'] as $column) {
            if (!Schema::hasColumn('partner_market_posts', $column)) Schema::table('partner_market_posts', fn (Blueprint $t) => $t->unsignedBigInteger($column)->nullable());
        }
        foreach (['reserved_until', 'gateway_checked_at', 'gateway_check_started_at', 'order_creation_started_at', 'release_confirmed_at'] as $column) {
            if (!Schema::hasColumn('partner_market_secure_payments', $column)) Schema::table('partner_market_secure_payments', fn (Blueprint $t) => $t->dateTime($column)->nullable());
        }
        if (!Schema::hasColumn('partner_market_secure_payments', 'reservation_state')) Schema::table('partner_market_secure_payments', fn (Blueprint $t) => $t->string('reservation_state', 24)->default('legacy'));
        if (!Schema::hasColumn('partner_market_secure_payments', 'gateway_check_token')) Schema::table('partner_market_secure_payments', fn (Blueprint $t) => $t->string('gateway_check_token', 36)->nullable());
        // Preserve immutable gateway evidence for every retry. Keep a non-unique FK index before dropping UNIQUE.
        $indexes = Schema::getIndexes('partner_market_secure_payments');
        if (!collect($indexes)->contains(fn ($i) => $i['name'] === 'pm_payment_quote_attempts')) Schema::table('partner_market_secure_payments', fn (Blueprint $t) => $t->index('quote_id', 'pm_payment_quote_attempts'));
        foreach ($indexes as $index) {
            if ($index['unique'] && $index['columns'] === ['quote_id']) Schema::table('partner_market_secure_payments', fn (Blueprint $t) => $t->dropUnique($index['name']));
        }
        // Resume safely after partial MySQL DDL. Only unmigrated legacy rows are inferred.
        DB::table('partner_market_posts')->orderBy('id')->chunkById(200, function ($posts) {
            foreach ($posts as $post) {
                DB::transaction(function () use ($post) {
                    $payment = DB::table('partner_market_secure_payments')->where('post_id', $post->id)->orderByDesc('id')->first();
                    if ($payment && $payment->reservation_state === 'legacy') {
                        $pending = $payment->status === 'pending';
                        DB::table('partner_market_secure_payments')->where('id', $payment->id)->update([
                            'reservation_state' => $pending ? 'reserved' : ($payment->status === 'paid' ? 'paid' : 'failed'),
                            'reserved_until' => $pending ? now()->addSeconds(120) : null,
                        ]);
                        $state = $pending ? 'payment_pending' : ($payment->status === 'paid' ? ($payment->completion_confirmed_at ? 'completed' : 'assigned') : 'cancelled');
                        DB::table('partner_market_posts')->where('id', $post->id)->update([
                            'status' => $state, 'active_payment_id' => $pending ? $payment->id : null,
                            'assigned_payment_id' => $pending ? null : $payment->id,
                        ]);
                        if ($pending) DB::table('partner_market_quotes')->where('id', $payment->quote_id)->update(['status' => 'reserved']);
                    } elseif (!$payment && $post->status === 'closed') {
                        $assigned = DB::table('partner_market_quotes')->where('post_id', $post->id)->where('status', 'accepted')->exists();
                        DB::table('partner_market_posts')->where('id', $post->id)->update(['status' => $assigned ? 'assigned' : 'cancelled']);
                    }
                });
            }
        });
    }
    public function down(): void { /* Financial evidence must not be destroyed on rollback. */ }
};
