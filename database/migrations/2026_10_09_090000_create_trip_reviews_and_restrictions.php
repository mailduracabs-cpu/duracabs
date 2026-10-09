<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_reviews', function (Blueprint $table): void {
            $table->id();
            $table->string('trip_type', 20);
            $table->unsignedBigInteger('trip_id');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['trip_type', 'trip_id', 'reviewer_id'], 'trip_review_direction_unique');
            $table->index(['subject_id', 'created_at']);
        });
        Schema::create('trip_account_restrictions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('review_id')->nullable()->unique()->constrained('trip_reviews')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_account_restrictions');
        Schema::dropIfExists('trip_reviews');
    }
};
