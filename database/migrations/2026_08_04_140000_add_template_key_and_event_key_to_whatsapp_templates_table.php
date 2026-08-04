<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('whatsapp_templates', 'template_key')) {
                $table->string('template_key', 120)
                    ->nullable()
                    ->after('template_name')
                    ->index();
            }

            if (! Schema::hasColumn('whatsapp_templates', 'event_key')) {
                $table->string('event_key', 160)
                    ->nullable()
                    ->after('template_key')
                    ->index();
            }
        });

        DB::table('whatsapp_templates')
            ->orderBy('id')
            ->get(['id', 'template_name', 'template_key', 'event_key'])
            ->each(function (object $template): void {
                $templateName = trim((string) $template->template_name);

                if ($templateName === '') {
                    return;
                }

                $baseKey = preg_replace(
                    '/_v\d+$/i',
                    '',
                    $templateName
                ) ?: $templateName;

                $baseKey = Str::of($baseKey)
                    ->lower()
                    ->replaceMatches('/[^a-z0-9_]+/', '_')
                    ->trim('_')
                    ->toString();

                $updates = [];

                if (blank($template->template_key)) {
                    $updates['template_key'] = $baseKey;
                }

                if (blank($template->event_key)) {
                    $updates['event_key'] = str_replace(
                        '_',
                        '.',
                        $baseKey
                    );
                }

                if ($updates !== []) {
                    DB::table('whatsapp_templates')
                        ->where('id', $template->id)
                        ->update($updates);
                }
            });
    }

    public function down(): void
    {
        Schema::table('whatsapp_templates', function (Blueprint $table): void {
            if (Schema::hasColumn('whatsapp_templates', 'event_key')) {
                $table->dropColumn('event_key');
            }

            if (Schema::hasColumn('whatsapp_templates', 'template_key')) {
                $table->dropColumn('template_key');
            }
        });
    }
};