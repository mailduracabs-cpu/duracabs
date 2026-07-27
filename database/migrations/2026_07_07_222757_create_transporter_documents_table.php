<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_transporter_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transporter_profile_id')
                ->constrained('fleet_transporter_profiles')
                ->cascadeOnDelete();

            $table->string('document_type', 80);
            $table->string('document_number')->nullable();
            $table->string('document_image')->nullable();

            $table->enum('status', [
                'pending',
                'verified',
                'rejected',
            ])->default('pending');

            $table->text('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('document_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_transporter_documents');
    }
};