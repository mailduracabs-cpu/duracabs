<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | WhatsApp Conversations
        |--------------------------------------------------------------------------
        */

        Schema::table('whats_app_conversations', function (Blueprint $table) {

            if (!Schema::hasColumn('whats_app_conversations', 'wa_id')) {
                $table->string('wa_id')->nullable()->unique();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'mobile')) {
                $table->string('mobile')->nullable()->index();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'customer_name')) {
                $table->string('customer_name')->nullable();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'phone_number_id')) {
                $table->string('phone_number_id')->nullable();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'display_phone_number')) {
                $table->string('display_phone_number')->nullable();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'status')) {
                $table->string('status')->default('open')->index();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'unread_count')) {
                $table->unsignedInteger('unread_count')->default(0);
            }

            if (!Schema::hasColumn('whats_app_conversations', 'last_message_type')) {
                $table->string('last_message_type')->nullable();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'last_message')) {
                $table->text('last_message')->nullable();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'last_message_direction')) {
                $table->string('last_message_direction')->nullable();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'last_message_at')) {
                $table->timestamp('last_message_at')->nullable()->index();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'last_customer_message_at')) {
                $table->timestamp('last_customer_message_at')->nullable();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'service_window_expires_at')) {
                $table->timestamp('service_window_expires_at')->nullable();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'assigned_admin_id')) {
                $table->unsignedBigInteger('assigned_admin_id')->nullable()->index();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }

            if (!Schema::hasColumn('whats_app_conversations', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Messages
        |--------------------------------------------------------------------------
        */

        Schema::table('whats_app_messages', function (Blueprint $table) {

            if (!Schema::hasColumn('whats_app_messages', 'whats_app_conversation_id')) {
                $table->unsignedBigInteger('whats_app_conversation_id')
                    ->nullable()
                    ->index();
            }

            if (!Schema::hasColumn('whats_app_messages', 'message_id')) {
                $table->string('message_id')->nullable()->unique();
            }

            if (!Schema::hasColumn('whats_app_messages', 'direction')) {
                $table->string('direction')->nullable()->index();
            }

            if (!Schema::hasColumn('whats_app_messages', 'type')) {
                $table->string('type')->default('text')->index();
            }

            if (!Schema::hasColumn('whats_app_messages', 'from_number')) {
                $table->string('from_number')->nullable()->index();
            }

            if (!Schema::hasColumn('whats_app_messages', 'to_number')) {
                $table->string('to_number')->nullable()->index();
            }

            if (!Schema::hasColumn('whats_app_messages', 'body')) {
                $table->longText('body')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'content')) {
                $table->json('content')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'media_id')) {
                $table->string('media_id')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'media_mime_type')) {
                $table->string('media_mime_type')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'media_filename')) {
                $table->string('media_filename')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'media_url')) {
                $table->text('media_url')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'reply_to_message_id')) {
                $table->string('reply_to_message_id')->nullable()->index();
            }

            if (!Schema::hasColumn('whats_app_messages', 'status')) {
                $table->string('status')->nullable()->index();
            }

            if (!Schema::hasColumn('whats_app_messages', 'sent_at')) {
                $table->timestamp('sent_at')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'failed_at')) {
                $table->timestamp('failed_at')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->index();
            }

            if (!Schema::hasColumn('whats_app_messages', 'metadata')) {
                $table->json('metadata')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'raw_payload')) {
                $table->json('raw_payload')->nullable();
            }

            if (!Schema::hasColumn('whats_app_messages', 'errors')) {
                $table->json('errors')->nullable();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Conversation Foreign Key
        |--------------------------------------------------------------------------
        */

        Schema::table('whats_app_messages', function (Blueprint $table) {
            $table->foreign('whats_app_conversation_id')
                ->references('id')
                ->on('whats_app_conversations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whats_app_messages', function (Blueprint $table) {

            $table->dropForeign([
                'whats_app_conversation_id',
            ]);

            $table->dropColumn([
                'whats_app_conversation_id',
                'message_id',
                'direction',
                'type',
                'from_number',
                'to_number',
                'body',
                'content',
                'media_id',
                'media_mime_type',
                'media_filename',
                'media_url',
                'reply_to_message_id',
                'status',
                'sent_at',
                'delivered_at',
                'read_at',
                'failed_at',
                'admin_id',
                'metadata',
                'raw_payload',
                'errors',
            ]);
        });

        Schema::table('whats_app_conversations', function (Blueprint $table) {
            $table->dropColumn([
                'wa_id',
                'mobile',
                'customer_name',
                'user_id',
                'phone_number_id',
                'display_phone_number',
                'status',
                'unread_count',
                'last_message_type',
                'last_message',
                'last_message_direction',
                'last_message_at',
                'last_customer_message_at',
                'service_window_expires_at',
                'assigned_admin_id',
                'read_at',
                'closed_at',
                'metadata',
            ]);
        });
    }
};