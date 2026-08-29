<x-filament-panels::page>

    <style>
        .wa-wrapper {
            height: calc(100vh - 180px);
            min-height: 620px;
            display: grid;
            grid-template-columns: 340px minmax(0, 1fr);
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
        }

        .dark .wa-wrapper {
            border-color: #374151;
            background: #111827;
        }

        .wa-sidebar {
            display: flex;
            min-width: 0;
            flex-direction: column;
            border-right: 1px solid #e5e7eb;
            background: #fff;
        }

        .dark .wa-sidebar {
            border-color: #374151;
            background: #111827;
        }

        .wa-sidebar-header {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .dark .wa-sidebar-header {
            border-color: #374151;
        }

        .wa-heading {
            margin-bottom: 12px;
            font-size: 18px;
            font-weight: 700;
        }

        .wa-search {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 10px 12px;
            background: #f9fafb;
            outline: none;
        }

        .wa-search:focus {
            border-color: #9ca3af;
        }

        .dark .wa-search {
            border-color: #4b5563;
            background: #1f2937;
            color: #fff;
        }

        .wa-chat-list {
            flex: 1;
            overflow-y: auto;
        }

        .wa-chat-item {
            width: 100%;
            display: flex;
            gap: 12px;
            padding: 13px 14px;
            border-bottom: 1px solid #f3f4f6;
            text-align: left;
            cursor: pointer;
            transition: background .15s ease;
        }

        .wa-chat-item:hover {
            background: #f9fafb;
        }

        .wa-chat-item.active {
            background: #f0fdf4;
        }

        .dark .wa-chat-item {
            border-color: #1f2937;
        }

        .dark .wa-chat-item:hover,
        .dark .wa-chat-item.active {
            background: #1f2937;
        }

        .wa-avatar {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #e5e7eb;
            font-weight: 700;
            font-size: 16px;
        }

        .dark .wa-avatar {
            background: #374151;
        }

        .wa-chat-info {
            min-width: 0;
            flex: 1;
        }

        .wa-chat-top {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
        }

        .wa-chat-name {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 600;
        }

        .wa-chat-time {
            flex-shrink: 0;
            color: #6b7280;
            font-size: 11px;
        }

        .wa-chat-bottom {
            margin-top: 3px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .wa-last-message {
            min-width: 0;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #6b7280;
            font-size: 13px;
        }

        .wa-unread {
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #22c55e;
            color: white;
            font-size: 11px;
            font-weight: 700;
        }

        .wa-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: #efeae2;
        }

        .dark .wa-main {
            background: #0f172a;
        }

        .wa-main-header {
            min-height: 70px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
        }

        .dark .wa-main-header {
            border-color: #374151;
            background: #111827;
        }

        .wa-main-name {
            font-weight: 700;
        }

        .wa-main-number {
            color: #6b7280;
            font-size: 12px;
        }

        .wa-messages {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
        }

        .wa-message-row {
            display: flex;
            margin-bottom: 8px;
        }

        .wa-message-row.incoming {
            justify-content: flex-start;
        }

        .wa-message-row.outgoing {
            justify-content: flex-end;
        }

        .wa-bubble {
            max-width: min(70%, 650px);
            padding: 8px 10px 6px;
            border-radius: 9px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, .08);
            word-break: break-word;
        }

        .wa-message-row.incoming .wa-bubble {
            background: #fff;
            border-top-left-radius: 2px;
        }

        .wa-message-row.outgoing .wa-bubble {
            background: #d9fdd3;
            border-top-right-radius: 2px;
        }

        .dark .wa-message-row.incoming .wa-bubble {
            background: #1f2937;
        }

        .dark .wa-message-row.outgoing .wa-bubble {
            background: #14532d;
        }

        .wa-message-body {
            white-space: pre-wrap;
            font-size: 14px;
            line-height: 1.45;
        }

        .wa-message-meta {
            margin-top: 3px;
            display: flex;
            justify-content: flex-end;
            gap: 4px;
            color: #6b7280;
            font-size: 10px;
        }

        .wa-ticks {
            font-weight: 700;
        }

        .wa-reply-area {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .dark .wa-reply-area {
            border-color: #374151;
            background: #111827;
        }

        .wa-reply-form {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .wa-reply-input {
            flex: 1;
            min-height: 46px;
            max-height: 120px;
            resize: none;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 11px 13px;
            background: #fff;
            outline: none;
        }

        .wa-reply-input:focus {
            border-color: #9ca3af;
        }

        .dark .wa-reply-input {
            border-color: #4b5563;
            background: #1f2937;
            color: #fff;
        }

        .wa-send-button {
            height: 46px;
            padding: 0 20px;
            border: 0;
            border-radius: 12px;
            background: #16a34a;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
        }

        .wa-send-button:hover {
            background: #15803d;
        }

        .wa-send-button:disabled {
            cursor: wait;
            opacity: .65;
        }

        .wa-empty {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
            color: #6b7280;
            text-align: center;
        }

        .wa-window-warning {
            padding: 8px 14px;
            background: #fff7ed;
            color: #9a3412;
            text-align: center;
            font-size: 12px;
        }

        @media (max-width: 900px) {
            .wa-wrapper {
                grid-template-columns: 280px minmax(0, 1fr);
            }

            .wa-bubble {
                max-width: 85%;
            }
        }
    </style>

    <div
        class="wa-wrapper"
        wire:poll.10s
    >
        {{-- LEFT CHAT LIST --}}
        <div class="wa-sidebar">

            <div class="wa-sidebar-header">
                <div class="wa-heading">
                    Chats
                </div>

                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    class="wa-search"
                    placeholder="Search customer..."
                >
            </div>

            <div class="wa-chat-list">

                @forelse($this->conversations as $conversation)

                    <button
                        type="button"
                        wire:key="conversation-{{ $conversation->id }}"
                        wire:click="selectConversation({{ $conversation->id }})"
                        class="wa-chat-item {{ $selectedConversationId === $conversation->id ? 'active' : '' }}"
                    >
                        <div class="wa-avatar">
                            {{ $this->conversationInitial($conversation) }}
                        </div>

                        <div class="wa-chat-info">

                            <div class="wa-chat-top">

                                <div class="wa-chat-name">
                                    {{ $this->conversationName($conversation) }}
                                </div>

                                <div class="wa-chat-time">
                                    @if($conversation->last_message_at)
                                        {{ $conversation->last_message_at->format('h:i A') }}
                                    @endif
                                </div>

                            </div>

                            <div class="wa-chat-bottom">

                                <div class="wa-last-message">
                                    @if($conversation->last_message_direction === 'outgoing')
                                        ✓
                                    @endif

                                    {{ $conversation->last_message ?: 'New conversation' }}
                                </div>

                                @if((int) $conversation->unread_count > 0)
                                    <span class="wa-unread">
                                        {{ $conversation->unread_count }}
                                    </span>
                                @endif

                            </div>

                        </div>
                    </button>

                @empty

                    <div class="wa-empty">
                        No WhatsApp chats found.
                    </div>

                @endforelse

            </div>
        </div>


        {{-- RIGHT CHAT --}}
        <div class="wa-main">

            @if($this->selectedConversation)

                @php
                    $selectedConversation = $this->selectedConversation;

                    $windowExpired =
                        $selectedConversation->service_window_expires_at
                        && now()->greaterThan(
                            $selectedConversation->service_window_expires_at
                        );
                @endphp

                <div class="wa-main-header">

                    <div class="wa-avatar">
                        {{ $this->conversationInitial($selectedConversation) }}
                    </div>

                    <div>
                        <div class="wa-main-name">
                            {{ $this->conversationName($selectedConversation) }}
                        </div>

                        <div class="wa-main-number">
                            {{ $this->formatPhone(
                                $selectedConversation->mobile
                                ?: $selectedConversation->wa_id
                            ) }}
                        </div>
                    </div>

                </div>

                @if($windowExpired)
                    <div class="wa-window-warning">
                        24-hour WhatsApp reply window expired. An approved template is required before sending a normal message.
                    </div>
                @endif


                <div
                    id="whatsappMessages"
                    class="wa-messages"
                >

                    @forelse($this->messages as $message)

                        @php
                            $outgoing = $message->direction === 'outgoing';

                            $body = trim((string) $message->body);

                            if ($body === '') {
                                $body = match ($message->type) {
                                    'image' => '📷 Image',
                                    'video' => '🎥 Video',
                                    'audio' => '🎤 Audio message',
                                    'document' => '📄 Document',
                                    'location' => '📍 Location',
                                    'contacts' => '👤 Contact',
                                    default => ucfirst((string) $message->type),
                                };
                            }
                        @endphp

                        <div
                            wire:key="message-{{ $message->id }}"
                            class="wa-message-row {{ $outgoing ? 'outgoing' : 'incoming' }}"
                        >
                            <div class="wa-bubble">

                                <div class="wa-message-body">{{ $body }}</div>

                                <div class="wa-message-meta">

                                    <span>
                                        {{
                                            optional(
                                                $message->sent_at
                                                ?: $message->created_at
                                            )->format('h:i A')
                                        }}
                                    </span>

                                    @if($outgoing)
                                        <span class="wa-ticks">
                                            @if($message->read_at)
                                                ✓✓
                                            @elseif($message->delivered_at)
                                                ✓✓
                                            @else
                                                ✓
                                            @endif
                                        </span>
                                    @endif

                                </div>

                            </div>
                        </div>

                    @empty

                        <div class="wa-empty">
                            No messages yet.
                        </div>

                    @endforelse

                </div>


                <div class="wa-reply-area">

                    <form
                        wire:submit="sendReply"
                        class="wa-reply-form"
                    >

                        <textarea
                            wire:model="replyMessage"
                            class="wa-reply-input"
                            rows="1"
                            placeholder="{{ $windowExpired ? '24-hour window expired' : 'Type a message...' }}"
                            @disabled($windowExpired)
                        ></textarea>

                        <button
                            type="submit"
                            class="wa-send-button"
                            wire:loading.attr="disabled"
                            wire:target="sendReply"
                            @disabled($windowExpired)
                        >
                            <span wire:loading.remove wire:target="sendReply">
                                Send ➤
                            </span>

                            <span wire:loading wire:target="sendReply">
                                Sending...
                            </span>
                        </button>

                    </form>

                </div>

            @else

                <div class="wa-empty">
                    Select a customer chat to start.
                </div>

            @endif

        </div>
    </div>


    <script>
        function scrollWhatsAppChatToBottom() {
            setTimeout(function () {
                const container = document.getElementById('whatsappMessages');

                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 100);
        }

        document.addEventListener('DOMContentLoaded', function () {
            scrollWhatsAppChatToBottom();
        });

        document.addEventListener('livewire:initialized', function () {

            Livewire.on('whatsapp-chat-selected', function () {
                scrollWhatsAppChatToBottom();
            });

            Livewire.on('whatsapp-message-sent', function () {
                scrollWhatsAppChatToBottom();
            });

        });
    </script>

</x-filament-panels::page>