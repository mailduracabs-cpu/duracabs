<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table): void {
            if (! Schema::hasColumn('banners', 'title')) {
                $table->string('title')->nullable()->after('name');
            }
            if (! Schema::hasColumn('banners', 'subtitle')) {
                $table->string('subtitle')->nullable()->after('title');
            }
            if (! Schema::hasColumn('banners', 'button_label')) {
                $table->string('button_label', 40)->default('Book now')->after('subtitle');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table): void {
            foreach (['button_label', 'subtitle', 'title'] as $column) {
                if (Schema::hasColumn('banners', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
