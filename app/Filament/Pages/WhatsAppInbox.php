<?php

namespace App\Filament\Pages;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class WhatsAppInbox extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp Inbox';

    protected static ?string $title = 'WhatsApp Inbox';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.whats-app-inbox';

    public ?int $selectedConversationId = null;

    public string $search = '';

    public string $replyMessage = '';

    public function mount(): void
    {
        $firstConversation = WhatsAppConversation::query()
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        if ($firstConversation) {
            $this->selectConversation($firstConversation->id);
        }
    }

    public function updatedSearch(): void
    {
        // Livewire automatically refreshes the conversation list.
    }

    public function getConversationsProperty(): Collection
    {
        $query = WhatsAppConversation::query()
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        $search = trim($this->search);

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('mobile', 'like', '%' . $search . '%')
                    ->orWhere('wa_id', 'like', '%' . $search . '%')
                    ->orWhere('last_message', 'like', '%' . $search . '%');
            });
        }

        return $query->limit(100)->get();
    }

    public function getSelectedConversationProperty(): ?WhatsAppConversation
    {
        if (!$this->selectedConversationId) {
            return null;
        }

        return WhatsAppConversation::query()
            ->find($this->selectedConversationId);
    }

    public function getMessagesProperty(): Collection
    {
        if (!$this->selectedConversationId) {
            return collect();
        }

        return WhatsAppMessage::query()
            ->where(
                'whats_app_conversation_id',
                $this->selectedConversationId
            )
            ->orderBy('sent_at')
            ->orderBy('id')
            ->get();
    }

    public function selectConversation(int $conversationId): void
    {
        $conversation = WhatsAppConversation::query()
            ->find($conversationId);

        if (!$conversation) {
            return;
        }

        $this->selectedConversationId = $conversation->id;

        $conversation->forceFill([
            'unread_count' => 0,
            'read_at' => now(),
        ])->save();

        WhatsAppMessage::query()
            ->where('whats_app_conversation_id', $conversation->id)
            ->where('direction', 'incoming')
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        $this->dispatch('whatsapp-chat-selected');
    }

    public function sendReply(): void
    {
        $message = trim($this->replyMessage);

        if ($message === '') {
            return;
        }

        $conversation = $this->selectedConversation;

        if (!$conversation) {
            Notification::make()
                ->title('Please select a chat first.')
                ->warning()
                ->send();

            return;
        }

        $number = trim(
            (string) (
                $conversation->mobile
                ?: $conversation->wa_id
            )
        );

        if ($number === '') {
            Notification::make()
                ->title('Customer WhatsApp number is missing.')
                ->danger()
                ->send();

            return;
        }

        /*
         * Meta allows normal free-form replies while the customer-service
         * conversation window is open.
         */
        if (
            $conversation->service_window_expires_at
            && now()->greaterThan($conversation->service_window_expires_at)
        ) {
            Notification::make()
                ->title('24-hour reply window has expired')
                ->body('Send an approved WhatsApp template to restart the conversation.')
                ->warning()
                ->send();

            return;
        }

        try {
            $result = WhatsAppService::sendMessage(
                $number,
                $message
            );

            if (!(bool) ($result['status'] ?? false)) {
                $errorMessage = (string) (
                    $result['message']
                    ?? $result['error']
                    ?? 'WhatsApp could not send this message.'
                );

                Notification::make()
                    ->title('Message not sent')
                    ->body($errorMessage)
                    ->danger()
                    ->send();

                return;
            }

            $metaMessageId = $this->extractMetaMessageId($result);

            WhatsAppMessage::create([
                'whats_app_conversation_id' => $conversation->id,
                'message_id' => $metaMessageId,
                'direction' => 'outgoing',
                'type' => 'text',

                'from_number' => $conversation->display_phone_number,
                'to_number' => $number,

                'body' => $message,

                'content' => [
                    'body' => $message,
                ],

                'status' => 'sent',
                'sent_at' => now(),

                'admin_id' => auth()->id(),

                'metadata' => [
                    'source' => 'filament_whatsapp_inbox',
                ],

                'raw_payload' => $result,
            ]);

            $conversation->forceFill([
                'last_message' => Str::limit($message, 500),
                'last_message_type' => 'text',
                'last_message_direction' => 'outgoing',
                'last_message_at' => now(),
                'status' => 'open',
                'read_at' => now(),
                'unread_count' => 0,
            ])->save();

            $this->replyMessage = '';

            $this->dispatch('whatsapp-message-sent');

        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Message not sent')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function extractMetaMessageId(array $result): ?string
    {
        $possibleIds = [
            data_get($result, 'message_id'),
            data_get($result, 'data.messages.0.id'),
            data_get($result, 'response.messages.0.id'),
            data_get($result, 'body.messages.0.id'),
            data_get($result, 'messages.0.id'),
        ];

        foreach ($possibleIds as $id) {
            if (is_string($id) && trim($id) !== '') {
                return trim($id);
            }
        }

        return null;
    }

    public function conversationName(WhatsAppConversation $conversation): string
    {
        $name = trim((string) $conversation->customer_name);

        if ($name !== '') {
            return $name;
        }

        return $this->formatPhone(
            (string) ($conversation->mobile ?: $conversation->wa_id)
        );
    }

    public function conversationInitial(WhatsAppConversation $conversation): string
    {
        $name = $this->conversationName($conversation);

        return Str::upper(
            Str::substr(trim($name), 0, 1)
        ) ?: '?';
    }

    public function formatPhone(?string $number): string
    {
        $number = trim((string) $number);

        if ($number === '') {
            return '';
        }

        if (str_starts_with($number, '+')) {
            return $number;
        }

        return '+' . $number;
    }
}