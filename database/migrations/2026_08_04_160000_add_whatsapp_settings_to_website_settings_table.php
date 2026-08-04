<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('website_settings', 'whatsapp_enabled')) {
                $table->boolean('whatsapp_enabled')
                    ->default(true)
                    ->after('pinterest_domain_verification');
            }

            if (! Schema::hasColumn('website_settings', 'whatsapp_default_country_code')) {
                $table->string('whatsapp_default_country_code', 10)
                    ->default('91')
                    ->after('whatsapp_enabled');
            }

            if (! Schema::hasColumn('website_settings', 'whatsapp_default_language')) {
                $table->string('whatsapp_default_language', 20)
                    ->default('en')
                    ->after('whatsapp_default_country_code');
            }

            if (! Schema::hasColumn('website_settings', 'whatsapp_test_number')) {
                $table->string('whatsapp_test_number', 30)
                    ->nullable()
                    ->after('whatsapp_default_language');
            }

            if (! Schema::hasColumn('website_settings', 'whatsapp_admin_numbers')) {
                $table->json('whatsapp_admin_numbers')
                    ->nullable()
                    ->after('whatsapp_test_number');
            }

            if (! Schema::hasColumn('website_settings', 'whatsapp_sales_numbers')) {
                $table->json('whatsapp_sales_numbers')
                    ->nullable()
                    ->after('whatsapp_admin_numbers');
            }

            if (! Schema::hasColumn('website_settings', 'whatsapp_operations_numbers')) {
                $table->json('whatsapp_operations_numbers')
                    ->nullable()
                    ->after('whatsapp_sales_numbers');
            }

            if (! Schema::hasColumn('website_settings', 'whatsapp_accounts_numbers')) {
                $table->json('whatsapp_accounts_numbers')
                    ->nullable()
                    ->after('whatsapp_operations_numbers');
            }

            if (! Schema::hasColumn('website_settings', 'whatsapp_support_numbers')) {
                $table->json('whatsapp_support_numbers')
                    ->nullable()
                    ->after('whatsapp_accounts_numbers');
            }
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $columns = [
                'whatsapp_enabled',
                'whatsapp_default_country_code',
                'whatsapp_default_language',
                'whatsapp_test_number',
                'whatsapp_admin_numbers',
                'whatsapp_sales_numbers',
                'whatsapp_operations_numbers',
                'whatsapp_accounts_numbers',
                'whatsapp_support_numbers',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('website_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};