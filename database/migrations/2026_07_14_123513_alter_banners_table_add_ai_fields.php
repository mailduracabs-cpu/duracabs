<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (!Schema::hasColumn('banners', 'is_active')) {
                $table->boolean('is_active')
                    ->default(true)
                    ->after('ride_type');
            }

            if (!Schema::hasColumn('banners', 'redirect_type')) {
                $table->string('redirect_type')
                    ->nullable()
                    ->after('is_active');
            }

            if (!Schema::hasColumn('banners', 'redirect_value')) {
                $table->string('redirect_value')
                    ->nullable()
                    ->after('redirect_type');
            }

            if (!Schema::hasColumn('banners', 'redirect_id')) {
                $table->unsignedBigInteger('redirect_id')
                    ->nullable()
                    ->after('redirect_value');
            }

            if (!Schema::hasColumn('banners', 'coupon_code')) {
                $table->string('coupon_code')
                    ->nullable()
                    ->after('redirect_id');
            }

            if (!Schema::hasColumn('banners', 'priority')) {
                $table->integer('priority')
                    ->default(0)
                    ->after('coupon_code');
            }

            if (!Schema::hasColumn('banners', 'start_date')) {
                $table->date('start_date')
                    ->nullable()
                    ->after('priority');
            }

            if (!Schema::hasColumn('banners', 'end_date')) {
                $table->date('end_date')
                    ->nullable()
                    ->after('start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $columns = [
                'is_active',
                'redirect_type',
                'redirect_value',
                'redirect_id',
                'coupon_code',
                'priority',
                'start_date',
                'end_date',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('banners', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};