<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'whats_app_campaigns',
            function (Blueprint $table): void {
                if (
                    ! Schema::hasColumn(
                        'whats_app_campaigns',
                        'whatsapp_template_id'
                    )
                ) {
                    $table
                        ->foreignId('whatsapp_template_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('whatsapp_templates')
                        ->nullOnDelete();

                    $table->index(
                        ['whatsapp_template_id', 'status'],
                        'wa_campaign_template_status_index'
                    );
                }
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'whats_app_campaigns',
            function (Blueprint $table): void {
                if (
                    Schema::hasColumn(
                        'whats_app_campaigns',
                        'whatsapp_template_id'
                    )
                ) {
                    $table->dropIndex(
                        'wa_campaign_template_status_index'
                    );

                    $table->dropConstrainedForeignId(
                        'whatsapp_template_id'
                    );
                }
            }
        );
    }
};