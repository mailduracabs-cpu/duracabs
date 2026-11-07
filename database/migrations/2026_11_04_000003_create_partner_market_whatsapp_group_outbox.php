<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL DDL can commit CREATE TABLE before a later ADD INDEX fails.
        // Resume that partial installation without deleting queued messages.
        if (!Schema::hasTable('partner_market_whatsapp_group_outbox')) {
            Schema::create('partner_market_whatsapp_group_outbox', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedInteger('post_revision');
            $table->string('event_kind', 20);
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->string('lease_token', 64)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            });
        }

        $required = ['id', 'post_id', 'post_revision', 'event_kind', 'payload', 'status',
            'attempts', 'available_at', 'claimed_at', 'completed_at', 'last_error',
            'created_at', 'updated_at'];
        $present = Schema::getColumnListing('partner_market_whatsapp_group_outbox');
        $missing = array_diff($required, $present);
        if ($missing) {
            throw new \RuntimeException('Outbox schema requires inspection; missing columns: '.implode(', ', $missing));
        }
        if (!in_array('lease_token', $present, true)) {
            Schema::table('partner_market_whatsapp_group_outbox', function (Blueprint $table) {
                $table->string('lease_token', 64)->nullable();
            });
        }

        // Explicit short names stay within MySQL's identifier limit.
        if (!$this->hasIndex(['post_id', 'post_revision'], true)) {
            $duplicate = DB::table('partner_market_whatsapp_group_outbox')
                ->select('post_id', 'post_revision')->groupBy('post_id', 'post_revision')
                ->havingRaw('COUNT(*) > 1')->first();
            if ($duplicate) {
                throw new \RuntimeException('Duplicate outbox revisions need review. No rows have been deleted.');
            }
            Schema::table('partner_market_whatsapp_group_outbox', function (Blueprint $table) {
                $table->unique(['post_id', 'post_revision'], 'pm_wa_post_revision_uq');
            });
        }
        if (!$this->hasIndex(['status', 'available_at'], false)) {
            Schema::table('partner_market_whatsapp_group_outbox', function (Blueprint $table) {
                $table->index(['status', 'available_at'], 'pm_wa_status_available_idx');
            });
        }
    }

    private function hasIndex(array $columns, bool $unique): bool
    {
        $connection = DB::connection();
        $table = $connection->getTablePrefix().'partner_market_whatsapp_group_outbox';
        $quoted = '`'.str_replace('`', '``', $table).'`';
        $indexes = [];
        foreach ($connection->select('SHOW INDEX FROM '.$quoted) as $row) {
            $key = (string) $row->Key_name;
            $indexes[$key]['columns'][(int) $row->Seq_in_index] = $row->Column_name;
            $indexes[$key]['unique'] = (int) $row->Non_unique === 0;
        }
        foreach ($indexes as $index) {
            ksort($index['columns']);
            if (array_values($index['columns']) === $columns && (!$unique || $index['unique'])) return true;
        }
        return false;
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_market_whatsapp_group_outbox');
    }
};
