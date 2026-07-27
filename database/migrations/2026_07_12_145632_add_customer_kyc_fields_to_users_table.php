<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add minimum customer KYC fields to users table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'aadhar_front')) {
                $table->string('aadhar_front')
                    ->nullable()
                    ->after('aadhar_image');
            }

            if (! Schema::hasColumn('users', 'aadhar_back')) {
                $table->string('aadhar_back')
                    ->nullable()
                    ->after('aadhar_front');
            }

            if (! Schema::hasColumn('users', 'driving_licence_front')) {
                $table->string('driving_licence_front')
                    ->nullable()
                    ->after('driving_licence_number');
            }

            if (! Schema::hasColumn('users', 'driving_licence_back')) {
                $table->string('driving_licence_back')
                    ->nullable()
                    ->after('driving_licence_front');
            }

            if (! Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status', 30)
                    ->default('not_uploaded')
                    ->after('driving_licence_back');
            }
        });
    }

    /**
     * Remove customer KYC fields.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];

            foreach ([
                'aadhar_front',
                'aadhar_back',
                'driving_licence_front',
                'driving_licence_back',
                'kyc_status',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columns[] = $column;
                }
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};