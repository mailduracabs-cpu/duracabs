<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class WhatsAppController extends BaseApiController
{
    /*
    |--------------------------------------------------------------------------
    | Send normal WhatsApp message
    |--------------------------------------------------------------------------
    */

    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:4096'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()
            );
        }

        try {
            $response = WhatsAppService::sendMessage(
                $request->string('mobile')->toString(),
                $request->string('message')->toString()
            );

            $sent = $this->isSuccessful($response);

            return $this->success([
                'sent' => $sent,
                'mobile' => $request->mobile,
                'provider_response' => $response,
            ], $sent
                ? 'WhatsApp message sent successfully'
                : 'WhatsApp message sending failed'
            );
        } catch (\Throwable $exception) {
            Log::error('WhatsApp normal message exception', [
                'mobile' => $request->mobile,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return $this->error(
                'WhatsApp message could not be sent',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send booking confirmation
    |--------------------------------------------------------------------------
    */

    public function bookingConfirmation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'pickup' => ['required', 'string', 'max:500'],
            'drop' => ['required', 'string', 'max:500'],
            'date' => ['required', 'string', 'max:100'],
            'time' => ['required', 'string', 'max:100'],
            'car_type' => ['nullable', 'string', 'max:100'],
            'amount' => ['nullable'],
            'booking_id' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()
            );
        }

        $customerName = $request->customer_name ?: 'Customer';
        $bookingId = $request->booking_id ?: 'N/A';
        $carType = $request->car_type ?: 'Cab';
        $amount = $request->amount !== null
            ? '₹' . $request->amount
            : 'As discussed';

        $message =
            "Dear {$customerName},\n\n" .
            "Your Dura Cabs booking is confirmed.\n\n" .
            "Booking ID: {$bookingId}\n" .
            "Pickup: {$request->pickup}\n" .
            "Drop: {$request->drop}\n" .
            "Date: {$request->date}\n" .
            "Time: {$request->time}\n" .
            "Car: {$carType}\n" .
            "Amount: {$amount}\n\n" .
            "Thank you for choosing Dura Cabs.";

        try {
            $response = WhatsAppService::bookingConfirmation(
                $request->mobile,
                $message
            );

            $sent = $this->isSuccessful($response);

            return $this->success([
                'sent' => $sent,
                'mobile' => $request->mobile,
                'message' => $message,
                'provider_response' => $response,
            ], $sent
                ? 'Booking confirmation sent successfully'
                : 'Booking confirmation sending failed'
            );
        } catch (\Throwable $exception) {
            Log::error('WhatsApp booking confirmation exception', [
                'mobile' => $request->mobile,
                'booking_id' => $request->booking_id,
                'message' => $exception->getMessage(),
            ]);

            return $this->error(
                'Booking confirmation could not be sent',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send booking cancellation
    |--------------------------------------------------------------------------
    */

    public function bookingCancellation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'booking_id' => ['nullable'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'refund_amount' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()
            );
        }

        $customerName = $request->customer_name ?: 'Customer';
        $bookingId = $request->booking_id ?: 'N/A';
        $reason = $request->reason ?: 'As requested';

        $message =
            "Dear {$customerName},\n\n" .
            "Your Dura Cabs booking has been cancelled.\n\n" .
            "Booking ID: {$bookingId}\n" .
            "Reason: {$reason}";

        if ($request->refund_amount !== null) {
            $message .= "\nRefund Amount: ₹{$request->refund_amount}";
        }

        $message .=
            "\n\nFor assistance, please contact Dura Cabs support.";

        try {
            $response = WhatsAppService::bookingCancellation(
                $request->mobile,
                $message
            );

            $sent = $this->isSuccessful($response);

            return $this->success([
                'sent' => $sent,
                'mobile' => $request->mobile,
                'message' => $message,
                'provider_response' => $response,
            ], $sent
                ? 'Booking cancellation sent successfully'
                : 'Booking cancellation sending failed'
            );
        } catch (\Throwable $exception) {
            Log::error('WhatsApp booking cancellation exception', [
                'mobile' => $request->mobile,
                'booking_id' => $request->booking_id,
                'message' => $exception->getMessage(),
            ]);

            return $this->error(
                'Booking cancellation could not be sent',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send driver details
    |--------------------------------------------------------------------------
    */

    public function driverDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
            'driver_name' => ['required', 'string', 'max:100'],
            'driver_mobile' => ['required', 'string', 'max:20'],
            'car_number' => ['nullable', 'string', 'max:50'],
            'car_name' => ['nullable', 'string', 'max:100'],
            'booking_id' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()
            );
        }

        $message =
            "Dura Cabs Driver Details\n\n" .
            "Booking ID: " . ($request->booking_id ?: 'N/A') . "\n" .
            "Driver: {$request->driver_name}\n" .
            "Driver Mobile: {$request->driver_mobile}\n" .
            "Car: " . ($request->car_name ?: 'Cab') . "\n" .
            "Car Number: " . ($request->car_number ?: 'N/A') . "\n\n" .
            "Have a safe journey.";

        try {
            $response = WhatsAppService::driverDetails(
                $request->mobile,
                $message
            );

            $sent = $this->isSuccessful($response);

            return $this->success([
                'sent' => $sent,
                'mobile' => $request->mobile,
                'message' => $message,
                'provider_response' => $response,
            ], $sent
                ? 'Driver details sent successfully'
                : 'Driver details sending failed'
            );
        } catch (\Throwable $exception) {
            Log::error('WhatsApp driver details exception', [
                'mobile' => $request->mobile,
                'booking_id' => $request->booking_id,
                'message' => $exception->getMessage(),
            ]);

            return $this->error(
                'Driver details could not be sent',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send car details
    |--------------------------------------------------------------------------
    */

    public function carDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
            'car_name' => ['required', 'string', 'max:100'],
            'car_number' => ['nullable', 'string', 'max:50'],
            'car_colour' => ['nullable', 'string', 'max:50'],
            'booking_id' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()
            );
        }

        $message =
            "Dura Cabs Vehicle Details\n\n" .
            "Booking ID: " . ($request->booking_id ?: 'N/A') . "\n" .
            "Car: {$request->car_name}\n" .
            "Car Number: " . ($request->car_number ?: 'N/A') . "\n" .
            "Colour: " . ($request->car_colour ?: 'N/A') . "\n\n" .
            "Have a safe journey.";

        try {
            $response = WhatsAppService::carDetails(
                $request->mobile,
                $message
            );

            $sent = $this->isSuccessful($response);

            return $this->success([
                'sent' => $sent,
                'mobile' => $request->mobile,
                'message' => $message,
                'provider_response' => $response,
            ], $sent
                ? 'Car details sent successfully'
                : 'Car details sending failed'
            );
        } catch (\Throwable $exception) {
            Log::error('WhatsApp car details exception', [
                'mobile' => $request->mobile,
                'message' => $exception->getMessage(),
            ]);

            return $this->error(
                'Car details could not be sent',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send payment reminder
    |--------------------------------------------------------------------------
    */

    public function paymentReminder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
            'amount' => ['required'],
            'payment_link' => ['nullable', 'url', 'max:2048'],
            'booking_id' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()
            );
        }

        $message =
            "Dura Cabs Payment Reminder\n\n" .
            "Booking ID: " . ($request->booking_id ?: 'N/A') . "\n" .
            "Amount Due: ₹{$request->amount}\n" .
            "Payment Link: " .
            ($request->payment_link ?: 'Please contact support') .
            "\n\nThank you.";

        try {
            $response = WhatsAppService::paymentReminder(
                $request->mobile,
                $message
            );

            $sent = $this->isSuccessful($response);

            return $this->success([
                'sent' => $sent,
                'mobile' => $request->mobile,
                'message' => $message,
                'provider_response' => $response,
            ], $sent
                ? 'Payment reminder sent successfully'
                : 'Payment reminder sending failed'
            );
        } catch (\Throwable $exception) {
            Log::error('WhatsApp payment reminder exception', [
                'mobile' => $request->mobile,
                'booking_id' => $request->booking_id,
                'message' => $exception->getMessage(),
            ]);

            return $this->error(
                'Payment reminder could not be sent',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send offer
    |--------------------------------------------------------------------------
    */

    public function offerMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:500'],
            'offer' => ['required', 'string', 'max:3000'],
            'link' => ['nullable', 'url', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()
            );
        }

        $message =
            "{$request->title}\n\n" .
            "{$request->offer}\n\n" .
            "Book now: " .
            ($request->link ?: 'https://www.duracabs.com') .
            "\n\nDura Cabs";

        try {
            $response = WhatsAppService::offer(
                $request->mobile,
                $message
            );

            $sent = $this->isSuccessful($response);

            return $this->success([
                'sent' => $sent,
                'mobile' => $request->mobile,
                'message' => $message,
                'provider_response' => $response,
            ], $sent
                ? 'Offer message sent successfully'
                : 'Offer message sending failed'
            );
        } catch (\Throwable $exception) {
            Log::error('WhatsApp offer message exception', [
                'mobile' => $request->mobile,
                'message' => $exception->getMessage(),
            ]);

            return $this->error(
                'Offer message could not be sent',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Meta template message
    |--------------------------------------------------------------------------
    */

    public function templateMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'max:20'],
            'template_name' => ['required', 'string', 'max:512'],
            'language_code' => ['nullable', 'string', 'max:20'],
            'parameters' => ['nullable', 'array'],
            'parameters.*' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return $this->error(
                $validator->errors()->first(),
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()
            );
        }

        try {
            $parameters = array_map(
                static fn ($value): string => (string) $value,
                $request->input('parameters', [])
            );

            $response = WhatsAppService::sendTemplate(
                $request->mobile,
                $request->template_name,
                $parameters,
                $request->input(
                    'language_code',
                    config('services.whatsapp.default_language', 'en')
                )
            );

            $sent = $this->isSuccessful($response);

            return $this->success([
                'sent' => $sent,
                'mobile' => $request->mobile,
                'template_name' => $request->template_name,
                'language_code' => $request->input(
                    'language_code',
                    config('services.whatsapp.default_language', 'en')
                ),
                'parameters' => $parameters,
                'provider_response' => $response,
            ], $sent
                ? 'Template message sent successfully'
                : 'Template message sending failed'
            );
        } catch (\Throwable $exception) {
            Log::error('WhatsApp template message exception', [
                'mobile' => $request->mobile,
                'template_name' => $request->template_name,
                'message' => $exception->getMessage(),
            ]);

            return $this->error(
                'Template message could not be sent',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Meta webhook verification
    |--------------------------------------------------------------------------
    |
    | Meta sends:
    | hub.mode
    | hub.verify_token
    | hub.challenge
    |
    */

    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode')
            ?? $request->query('hub.mode');

        $verifyToken = $request->query('hub_verify_token')
            ?? $request->query('hub.verify_token');

        $challenge = $request->query('hub_challenge')
            ?? $request->query('hub.challenge');

        $configuredToken = (string) config(
            'services.whatsapp.webhook_verify_token'
        );

        if (
            $mode === 'subscribe' &&
            is_string($verifyToken) &&
            hash_equals($configuredToken, $verifyToken)
        ) {
            Log::info('Meta WhatsApp webhook verified');

            return response(
                (string) $challenge,
                Response::HTTP_OK
            )->header('Content-Type', 'text/plain');
        }

        Log::warning('Meta WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token_received' => !empty($verifyToken),
        ]);

        return response(
            'Invalid verification token',
            Response::HTTP_FORBIDDEN
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Receive Meta WhatsApp webhook
    |--------------------------------------------------------------------------
    */

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Meta WhatsApp webhook received', [
            'payload' => $payload,
        ]);

        try {
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];

                    $this->processStatuses(
                        $value['statuses'] ?? []
                    );

                    $this->processIncomingMessages(
                        $value['messages'] ?? [],
                        $value['contacts'] ?? [],
                        $value['metadata'] ?? []
                    );
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'WhatsApp webhook processed',
            ], Response::HTTP_OK);
        } catch (\Throwable $exception) {
            /*
             * Meta expects HTTP 200 quickly.
             * We log processing errors but still acknowledge the webhook,
             * otherwise Meta may repeatedly resend the same event.
             */

            Log::error('Meta WhatsApp webhook processing exception', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'payload' => $payload,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Webhook acknowledged',
            ], Response::HTTP_OK);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Test Meta connection
    |--------------------------------------------------------------------------
    */

    public function testConnection(): JsonResponse
    {
        try {
            $response = WhatsAppService::testConnection();
            $success = $this->isSuccessful($response);

            return $this->success([
                'connected' => $success,
                'provider_response' => $response,
            ], $success
                ? 'Meta WhatsApp connection successful'
                : 'Meta WhatsApp connection failed'
            );
        } catch (\Throwable $exception) {
            Log::error('Meta WhatsApp connection test exception', [
                'message' => $exception->getMessage(),
            ]);

            return $this->error(
                'Meta WhatsApp connection test failed',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process delivery statuses
    |--------------------------------------------------------------------------
    */

    private function processStatuses(array $statuses): void
    {
        foreach ($statuses as $statusData) {
            $messageId = $statusData['id'] ?? null;
            $recipientId = $statusData['recipient_id'] ?? null;
            $status = $statusData['status'] ?? 'unknown';
            $timestamp = $statusData['timestamp'] ?? null;
            $conversation = $statusData['conversation'] ?? [];
            $pricing = $statusData['pricing'] ?? [];
            $errors = $statusData['errors'] ?? [];

            Log::info('Meta WhatsApp message status', [
                'message_id' => $messageId,
                'recipient_id' => $recipientId,
                'status' => $status,
                'timestamp' => $timestamp,
                'conversation' => $conversation,
                'pricing' => $pricing,
                'errors' => $errors,
            ]);

            if ($status === 'failed') {
                Log::error('Meta WhatsApp message failed', [
                    'message_id' => $messageId,
                    'recipient_id' => $recipientId,
                    'errors' => $errors,
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process incoming customer messages
    |--------------------------------------------------------------------------
    */

    private function processIncomingMessages(
        array $messages,
        array $contacts,
        array $metadata
    ): void {
        $contactMap = [];

        foreach ($contacts as $contact) {
            $waId = $contact['wa_id'] ?? null;

            if ($waId) {
                $contactMap[$waId] = [
                    'name' => $contact['profile']['name'] ?? null,
                    'wa_id' => $waId,
                ];
            }
        }

        foreach ($messages as $message) {
            $messageId = $message['id'] ?? null;
            $from = $message['from'] ?? null;
            $timestamp = $message['timestamp'] ?? null;
            $type = $message['type'] ?? 'unknown';
            $context = $message['context'] ?? [];
            $contact = $from && isset($contactMap[$from])
                ? $contactMap[$from]
                : null;

            $content = $this->extractIncomingMessageContent(
                $message,
                $type
            );

            Log::info('Meta WhatsApp incoming message', [
                'message_id' => $messageId,
                'from' => $from,
                'customer_name' => $contact['name'] ?? null,
                'timestamp' => $timestamp,
                'type' => $type,
                'content' => $content,
                'context' => $context,
                'metadata' => $metadata,
            ]);

            try {
                if ($from) {
                    /*
                     * Meta may retry the same webhook. Do not create a
                     * duplicate message or increase unread_count twice.
                     */
                    $alreadySaved = $messageId
                        ? WhatsAppMessage::query()
                            ->where('message_id', $messageId)
                            ->exists()
                        : false;

                    if (!$alreadySaved) {
                        $messageAt = $timestamp
                            ? Carbon::createFromTimestamp((int) $timestamp)
                            : now();

                        $digits = preg_replace(
                            '/\\D+/',
                            '',
                            (string) $from
                        );

                        $lastTenDigits = strlen($digits) >= 10
                            ? substr($digits, -10)
                            : $digits;

                        $user = null;

                        if ($lastTenDigits !== '') {
                            $user = User::query()
                                ->where('mobile', $digits)
                                ->orWhere('mobile', $lastTenDigits)
                                ->orWhere(
                                    'mobile',
                                    'like',
                                    '%' . $lastTenDigits
                                )
                                ->first();
                        }

                        $conversation = WhatsAppConversation::firstOrNew([
                            'wa_id' => $digits ?: (string) $from,
                        ]);

                        if (!$conversation->exists) {
                            $conversation->status = 'open';
                            $conversation->unread_count = 0;
                        }

                        $conversation->mobile =
                            $digits ?: (string) $from;

                        if (!empty($contact['name'])) {
                            $conversation->customer_name =
                                $contact['name'];
                        }

                        if ($user) {
                            $conversation->user_id = $user->id;

                            if (empty($conversation->customer_name)) {
                                $conversation->customer_name =
                                    $user->name ?? null;
                            }
                        }

                        $conversation->phone_number_id =
                            $metadata['phone_number_id'] ?? null;

                        $conversation->display_phone_number =
                            $metadata['display_phone_number'] ?? null;

                        $conversation->status = 'open';
                        $conversation->last_message_type = $type;
                        $conversation->last_message =
                            $this->incomingMessagePreview(
                                $type,
                                $content
                            );

                        $conversation->last_message_direction =
                            'incoming';

                        $conversation->last_message_at = $messageAt;
                        $conversation->last_customer_message_at =
                            $messageAt;

                        /*
                         * A customer message opens Meta's 24-hour customer
                         * service window for free-form replies.
                         */
                        $conversation->service_window_expires_at =
                            $messageAt->copy()->addHours(24);

                        $conversation->unread_count =
                            ((int) $conversation->unread_count) + 1;

                        $conversation->read_at = null;

                        $conversation->metadata = [
                            'phone_number_id' =>
                                $metadata['phone_number_id'] ?? null,
                            'display_phone_number' =>
                                $metadata['display_phone_number'] ?? null,
                        ];

                        $conversation->save();

                        WhatsAppMessage::create([
                            'whats_app_conversation_id' =>
                                $conversation->id,
                            'message_id' => $messageId,
                            'direction' => 'incoming',
                            'type' => $type,
                            'from_number' =>
                                $digits ?: (string) $from,
                            'to_number' =>
                                $metadata['display_phone_number'] ?? null,
                            'body' => $this->incomingMessageBody(
                                $type,
                                $content
                            ),
                            'content' => $content,
                            'media_id' =>
                                $content['media_id'] ?? null,
                            'media_mime_type' =>
                                $content['mime_type'] ?? null,
                            'media_filename' =>
                                $content['filename'] ?? null,
                            'reply_to_message_id' =>
                                $context['id'] ?? null,
                            'status' => 'received',
                            'sent_at' => $messageAt,
                            'metadata' => [
                                'phone_number_id' =>
                                    $metadata['phone_number_id'] ?? null,
                                'display_phone_number' =>
                                    $metadata['display_phone_number'] ?? null,
                                'context' => $context,
                            ],
                            'raw_payload' => $message,
                        ]);

                        Log::info(
                            'WhatsApp incoming message saved to inbox',
                            [
                                'conversation_id' => $conversation->id,
                                'message_id' => $messageId,
                                'from' => $from,
                            ]
                        );
                    }
                }
            } catch (\Throwable $exception) {
                /*
                 * Inbox persistence must never prevent Meta webhook
                 * acknowledgement.
                 */
                Log::error('WhatsApp inbox save failed', [
                    'message_id' => $messageId,
                    'from' => $from,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]);
            }

            if ($messageId) {
                try {
                    WhatsAppService::markAsRead($messageId);
                } catch (\Throwable $exception) {
                    Log::warning('WhatsApp mark-as-read failed', [
                        'message_id' => $messageId,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Extract incoming message content
    |--------------------------------------------------------------------------
    */

    private function extractIncomingMessageContent(
        array $message,
        string $type
    ): array {
        return match ($type) {
            'text' => [
                'body' => $message['text']['body'] ?? null,
            ],

            'button' => [
                'text' => $message['button']['text'] ?? null,
                'payload' => $message['button']['payload'] ?? null,
            ],

            'interactive' => [
                'interactive_type' =>
                    $message['interactive']['type'] ?? null,

                'button_reply_id' =>
                    $message['interactive']['button_reply']['id']
                    ?? null,

                'button_reply_title' =>
                    $message['interactive']['button_reply']['title']
                    ?? null,

                'list_reply_id' =>
                    $message['interactive']['list_reply']['id']
                    ?? null,

                'list_reply_title' =>
                    $message['interactive']['list_reply']['title']
                    ?? null,

                'list_reply_description' =>
                    $message['interactive']['list_reply']['description']
                    ?? null,
            ],

            'image' => [
                'media_id' => $message['image']['id'] ?? null,
                'mime_type' => $message['image']['mime_type'] ?? null,
                'caption' => $message['image']['caption'] ?? null,
                'sha256' => $message['image']['sha256'] ?? null,
            ],

            'document' => [
                'media_id' => $message['document']['id'] ?? null,
                'mime_type' => $message['document']['mime_type'] ?? null,
                'filename' => $message['document']['filename'] ?? null,
                'caption' => $message['document']['caption'] ?? null,
                'sha256' => $message['document']['sha256'] ?? null,
            ],

            'audio' => [
                'media_id' => $message['audio']['id'] ?? null,
                'mime_type' => $message['audio']['mime_type'] ?? null,
                'voice' => $message['audio']['voice'] ?? false,
                'sha256' => $message['audio']['sha256'] ?? null,
            ],

            'video' => [
                'media_id' => $message['video']['id'] ?? null,
                'mime_type' => $message['video']['mime_type'] ?? null,
                'caption' => $message['video']['caption'] ?? null,
                'sha256' => $message['video']['sha256'] ?? null,
            ],

            'location' => [
                'latitude' =>
                    $message['location']['latitude'] ?? null,

                'longitude' =>
                    $message['location']['longitude'] ?? null,

                'name' =>
                    $message['location']['name'] ?? null,

                'address' =>
                    $message['location']['address'] ?? null,
            ],

            'contacts' => [
                'contacts' => $message['contacts'] ?? [],
            ],

            'reaction' => [
                'message_id' =>
                    $message['reaction']['message_id'] ?? null,

                'emoji' =>
                    $message['reaction']['emoji'] ?? null,
            ],

            'order' => [
                'catalog_id' =>
                    $message['order']['catalog_id'] ?? null,

                'product_items' =>
                    $message['order']['product_items'] ?? [],
            ],

            default => [
                'raw' => $message[$type] ?? $message,
            ],
        };
    }

    private function incomingMessageBody(
        string $type,
        array $content
    ): ?string {
        return match ($type) {
            'text' =>
                $content['body'] ?? null,

            'button' =>
                $content['text'] ?? null,

            'interactive' =>
                $content['button_reply_title']
                ?? $content['list_reply_title']
                ?? null,

            'image', 'video', 'document' =>
                $content['caption'] ?? null,

            'location' =>
                $content['name']
                ?? $content['address']
                ?? 'Location',

            'reaction' =>
                $content['emoji'] ?? null,

            'audio' =>
                'Voice/Audio message',

            'contacts' =>
                'Contact shared',

            default =>
                null,
        };
    }

    private function incomingMessagePreview(
        string $type,
        array $content
    ): string {
        $body = $this->incomingMessageBody($type, $content);

        if ($body !== null && trim($body) !== '') {
            return mb_substr(trim($body), 0, 500);
        }

        return match ($type) {
            'image' => '📷 Image',
            'video' => '🎥 Video',
            'audio' => '🎤 Voice/Audio message',
            'document' => '📄 Document',
            'location' => '📍 Location',
            'contacts' => '👤 Contact',
            'reaction' => 'Reaction',
            'interactive' => 'Interactive reply',
            default => ucfirst($type) . ' message',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize service response
    |--------------------------------------------------------------------------
    |
    | Supports both old boolean responses and new array responses.
    |
    */

    private function isSuccessful(mixed $response): bool
    {
        if (is_bool($response)) {
            return $response;
        }

        if (is_array($response)) {
            return (bool) (
                $response['status']
                ?? $response['success']
                ?? false
            );
        }

        return false;
    }
}