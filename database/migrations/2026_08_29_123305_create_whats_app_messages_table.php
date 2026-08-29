<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whats_app_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('whats_app_conversation_id')
                ->constrained('whats_app_conversations')
                ->cascadeOnDelete();

            // Meta WhatsApp message ID
            $table->string('message_id')->nullable()->unique();

            // incoming / outgoing
            $table->string('direction', 20)->index();

            // text, image, document, audio, video,
            // location, contacts, interactive, button etc.
            $table->string('type', 50)->default('text')->index();

            $table->string('from_number', 30)->nullable()->index();
            $table->string('to_number', 30)->nullable()->index();

            // Human-readable message
            $table->longText('body')->nullable();

            // Complete extracted content
            $table->json('content')->nullable();

            // Media details
            $table->string('media_id')->nullable();
            $table->string('media_mime_type')->nullable();
            $table->string('media_filename')->nullable();
            $table->text('media_url')->nullable();

            // Reply/context
            $table->string('reply_to_message_id')->nullable()->index();

            // Meta delivery status
            $table->string('status', 30)->nullable()->index();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Which admin sent outgoing reply
            $table->unsignedBigInteger('admin_id')->nullable()->index();

            // Raw Meta information for debugging/future use
            $table->json('metadata')->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('errors')->nullable();

            $table->timestamps();

            $table->index([
                'whats_app_conversation_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whats_app_messages');
    }
};