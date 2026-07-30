<?php

namespace App\Services;

use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppCampaignRecipient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class WhatsAppCampaignService
{
    private const GRAPH_API_VERSION = 'v23.0';

    private const BATCH_SIZE = 50;

    private const MAX_RECIPIENT_RETRIES = 3;

    /**
     * Campaign ke pending aur retry-eligible recipients process karega.
     */
    public function process(WhatsAppCampaign $campaign): void
    {
        $campaign->refresh();

        if ($campaign->isCancelled()) {
            return;
        }

        $this->validateCampaign($campaign);

        $campaign->recipients()
            ->where(function ($query): void {
                $query
                    ->where(
                        'status',
                        WhatsAppCampaignRecipient::STATUS_PENDING
                    )
                    ->orWhere(function ($retryQuery): void {
                        $retryQuery
                            ->where(
                                'status',
                                WhatsAppCampaignRecipient::STATUS_FAILED
                            )
                            ->where(
                                'retry_count',
                                '<',
                                self::MAX_RECIPIENT_RETRIES
                            );
                    });
            })
            ->orderBy('id')
            ->chunkById(
                self::BATCH_SIZE,
                function ($recipients) use ($campaign): bool {
                    $campaign->refresh();

                    if ($campaign->isCancelled()) {
                        return false;
                    }

                    foreach ($recipients as $recipient) {
                        $campaign->refresh();

                        if ($campaign->isCancelled()) {
                            return false;
                        }

                        $this->processRecipient(
                            $campaign,
                            $recipient
                        );

                        /*
                         * Meta API par bahut tezi se requests jaane se
                         * bachane ke liye halka delay.
                         */
                        usleep(150000);
                    }

                    return true;
                }
            );

        $campaign->refreshCounters();
    }

    /**
     * Ek recipient ko template message send karega.
     */
    private function processRecipient(
        WhatsAppCampaign $campaign,
        WhatsAppCampaignRecipient $recipient
    ): void {
        try {
            $recipient->markAsProcessing();

            $payload = $this->buildTemplatePayload(
                $campaign,
                $recipient
            );

            $response = $this->sendRequest($payload);

            if (! $response->successful()) {
                $this->handleFailedResponse(
                    $recipient,
                    $response
                );

                return;
            }

            $responseData = $response->json();

            $metaMessageId = data_get(
                $responseData,
                'messages.0.id'
            );

            $messageStatus = data_get(
                $responseData,
                'messages.0.message_status'
            );

            /*
             * Meta Cloud API successful response me aam taur par
             * message_status "accepted" hota hai.
             */
            if (! $metaMessageId) {
                $recipient->incrementRetryCount();

                $recipient->markAsFailed(
                    'Meta API response me message ID nahi mila.',
                    $responseData
                );

                return;
            }

            if ($messageStatus === 'sent') {
                $recipient->markAsSent(
                    $metaMessageId,
                    $responseData
                );

                return;
            }

            $recipient->markAsAccepted(
                $metaMessageId,
                $responseData
            );
        } catch (ConnectionException $exception) {
            $recipient->incrementRetryCount();

            $recipient->markAsFailed(
                'Meta API connection error: '
                    . $exception->getMessage()
            );

            Log::error(
                'WhatsApp campaign Meta API connection failed.',
                [
                    'campaign_id' => $campaign->id,
                    'recipient_id' => $recipient->id,
                    'mobile' => $recipient->masked_mobile,
                    'message' => $exception->getMessage(),
                ]
            );
        } catch (Throwable $exception) {
            $recipient->incrementRetryCount();

            $recipient->markAsFailed(
                $exception->getMessage()
            );

            Log::error(
                'WhatsApp campaign recipient processing failed.',
                [
                    'campaign_id' => $campaign->id,
                    'recipient_id' => $recipient->id,
                    'mobile' => $recipient->masked_mobile,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]
            );
        }
    }

    /**
     * Meta WhatsApp template payload banayega.
     */
    private function buildTemplatePayload(
        WhatsAppCampaign $campaign,
        WhatsAppCampaignRecipient $recipient
    ): array {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizeMobile($recipient->mobile),
            'type' => 'template',
            'template' => [
                'name' => $campaign->template_name,
                'language' => [
                    'code' => $campaign->language ?: 'en_US',
                ],
            ],
        ];

        $components = [];

        $headerComponent = $this->buildHeaderComponent(
            $campaign
        );

        if ($headerComponent !== null) {
            $components[] = $headerComponent;
        }

        $bodyComponent = $this->buildBodyComponent(
            $campaign,
            $recipient
        );

        if ($bodyComponent !== null) {
            $components[] = $bodyComponent;
        }

        $buttonComponents = $this->buildButtonComponents(
            $campaign,
            $recipient
        );

        foreach ($buttonComponents as $buttonComponent) {
            $components[] = $buttonComponent;
        }

        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        return $payload;
    }

    /**
     * Image, video ya document header component banayega.
     */
    private function buildHeaderComponent(
        WhatsAppCampaign $campaign
    ): ?array {
        $headerType = strtolower(
            trim((string) $campaign->header_type)
        );

        $headerMedia = trim(
            (string) $campaign->header_media
        );

        if (
            ! in_array(
                $headerType,
                ['image', 'video', 'document'],
                true
            )
            || $headerMedia === ''
        ) {
            return null;
        }

        $mediaParameter = [
            'link' => $headerMedia,
        ];

        if ($headerType === 'document') {
            $mediaParameter['filename'] = basename(
                parse_url($headerMedia, PHP_URL_PATH)
                    ?: 'document'
            );
        }

        return [
            'type' => 'header',
            'parameters' => [
                [
                    'type' => $headerType,
                    $headerType => $mediaParameter,
                ],
            ],
        ];
    }

    /**
     * Template body variables banayega.
     */
    private function buildBodyComponent(
        WhatsAppCampaign $campaign,
        WhatsAppCampaignRecipient $recipient
    ): ?array {
        $variables = $recipient->variables;

        if (! is_array($variables) || $variables === []) {
            $variables = $campaign->template_variables;
        }

        if (! is_array($variables) || $variables === []) {
            return null;
        }

        $variables = $this->sortTemplateVariables(
            $variables
        );

        $parameters = [];

        foreach ($variables as $key => $value) {
            $resolvedValue = $this->resolveVariableValue(
                $value,
                $recipient,
                (string) $key
            );

            $parameters[] = [
                'type' => 'text',
                'text' => $resolvedValue,
            ];
        }

        if ($parameters === []) {
            return null;
        }

        return [
            'type' => 'body',
            'parameters' => $parameters,
        ];
    }

    /**
     * Meta template button variables banayega.
     */
    private function buildButtonComponents(
        WhatsAppCampaign $campaign,
        WhatsAppCampaignRecipient $recipient
    ): array {
        $buttons = $campaign->button_payload;

        if (! is_array($buttons) || $buttons === []) {
            return [];
        }

        $components = [];

        foreach ($buttons as $index => $button) {
            if (! is_array($button)) {
                continue;
            }

            $buttonType = strtolower(
                (string) ($button['type'] ?? '')
            );

            $buttonValue = $button['value']
                ?? $button['payload']
                ?? $button['url_parameter']
                ?? null;

            if (
                ! in_array(
                    $buttonType,
                    ['quick_reply', 'url'],
                    true
                )
                || $buttonValue === null
                || trim((string) $buttonValue) === ''
            ) {
                continue;
            }

            $resolvedValue = $this->replaceRecipientPlaceholders(
                (string) $buttonValue,
                $recipient
            );

            $parameter = $buttonType === 'quick_reply'
                ? [
                    'type' => 'payload',
                    'payload' => $resolvedValue,
                ]
                : [
                    'type' => 'text',
                    'text' => $resolvedValue,
                ];

            $components[] = [
                'type' => 'button',
                'sub_type' => $buttonType,
                'index' => (string) $index,
                'parameters' => [$parameter],
            ];
        }

        return $components;
    }

    /**
     * Meta Graph API ko request bhejega.
     */
    private function sendRequest(array $payload): Response
    {
        $accessToken = $this->getAccessToken();
        $phoneNumberId = $this->getPhoneNumberId();
        $graphVersion = $this->getGraphApiVersion();

        $endpoint = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $graphVersion,
            $phoneNumberId
        );

        return Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(20)
            ->timeout(60)
            ->retry(
                2,
                1000,
                function (
                    Throwable $exception,
                    $request
                ): bool {
                    return $exception instanceof ConnectionException;
                },
                throw: false
            )
            ->post($endpoint, $payload);
    }

    /**
     * Meta API error response ko recipient record me save karega.
     */
    private function handleFailedResponse(
        WhatsAppCampaignRecipient $recipient,
        Response $response
    ): void {
        $responseData = $response->json();

        $errorMessage = data_get(
            $responseData,
            'error.message'
        );

        $errorCode = data_get(
            $responseData,
            'error.code'
        );

        $errorSubcode = data_get(
            $responseData,
            'error.error_subcode'
        );

        $reason = 'Meta WhatsApp API request failed.';

        if ($errorMessage) {
            $reason .= ' ' . $errorMessage;
        }

        if ($errorCode !== null) {
            $reason .= ' Code: ' . $errorCode . '.';
        }

        if ($errorSubcode !== null) {
            $reason .= ' Subcode: ' . $errorSubcode . '.';
        }

        $recipient->incrementRetryCount();

        $recipient->markAsFailed(
            $reason,
            $responseData ?: $response->body()
        );

        Log::warning(
            'WhatsApp campaign message rejected by Meta API.',
            [
                'recipient_id' => $recipient->id,
                'campaign_id' => $recipient->campaign_id,
                'mobile' => $recipient->masked_mobile,
                'http_status' => $response->status(),
                'error_code' => $errorCode,
                'error_subcode' => $errorSubcode,
                'error_message' => $errorMessage,
            ]
        );
    }

    /**
     * Campaign send karne se pehle required fields validate karega.
     */
    private function validateCampaign(
        WhatsAppCampaign $campaign
    ): void {
        if (
            trim((string) $campaign->template_name) === ''
        ) {
            throw new RuntimeException(
                'Campaign ka Meta template name missing hai.'
            );
        }

        if (
            ! $campaign->recipients()
                ->where(function ($query): void {
                    $query
                        ->where(
                            'status',
                            WhatsAppCampaignRecipient::STATUS_PENDING
                        )
                        ->orWhere(function ($retryQuery): void {
                            $retryQuery
                                ->where(
                                    'status',
                                    WhatsAppCampaignRecipient::STATUS_FAILED
                                )
                                ->where(
                                    'retry_count',
                                    '<',
                                    self::MAX_RECIPIENT_RETRIES
                                );
                        });
                })
                ->exists()
        ) {
            throw new RuntimeException(
                'Campaign me koi pending recipient nahi hai.'
            );
        }

        $this->getAccessToken();
        $this->getPhoneNumberId();
    }

    /**
     * Mobile number ko WhatsApp international format me normalize karega.
     */
    private function normalizeMobile(
        string $mobile
    ): string {
        $number = preg_replace(
            '/\D+/',
            '',
            $mobile
        ) ?? '';

        if (strlen($number) === 10) {
            $number = '91' . $number;
        }

        if (
            strlen($number) === 11
            && str_starts_with($number, '0')
        ) {
            $number = '91' . substr($number, 1);
        }

        if (
            strlen($number) < 10
            || strlen($number) > 15
        ) {
            throw new RuntimeException(
                'Invalid WhatsApp mobile number: ' . $mobile
            );
        }

        return $number;
    }

    /**
     * Template variables ko {{1}}, {{2}}, {{3}} ke order me arrange karega.
     */
    private function sortTemplateVariables(
        array $variables
    ): array {
        uksort(
            $variables,
            static function (
                string|int $first,
                string|int $second
            ): int {
                $firstNumber = (int) preg_replace(
                    '/\D+/',
                    '',
                    (string) $first
                );

                $secondNumber = (int) preg_replace(
                    '/\D+/',
                    '',
                    (string) $second
                );

                if ($firstNumber === $secondNumber) {
                    return strcmp(
                        (string) $first,
                        (string) $second
                    );
                }

                return $firstNumber <=> $secondNumber;
            }
        );

        return $variables;
    }

    /**
     * Variable ke special values ko recipient data se replace karega.
     */
    private function resolveVariableValue(
        mixed $value,
        WhatsAppCampaignRecipient $recipient,
        string $key
    ): string {
        $text = trim((string) $value);

        if ($text === '') {
            return '-';
        }

        $normalizedText = strtolower(
            str_replace(
                [' ', '-', '{', '}'],
                ['_', '_', '', ''],
                $text
            )
        );

        return match ($normalizedText) {
            'name',
            'customer_name',
            'recipient_name' => $recipient->name
                ?: 'Customer',

            'mobile',
            'phone',
            'customer_mobile',
            'recipient_mobile' => $recipient->mobile,

            default => $this->replaceRecipientPlaceholders(
                $text,
                $recipient
            ),
        };
    }

    /**
     * Text me recipient placeholders replace karega.
     */
    private function replaceRecipientPlaceholders(
        string $text,
        WhatsAppCampaignRecipient $recipient
    ): string {
        return str_replace(
            [
                '{name}',
                '{{name}}',
                '{customer_name}',
                '{{customer_name}}',
                '{mobile}',
                '{{mobile}}',
                '{customer_mobile}',
                '{{customer_mobile}}',
            ],
            [
                $recipient->name ?: 'Customer',
                $recipient->name ?: 'Customer',
                $recipient->name ?: 'Customer',
                $recipient->name ?: 'Customer',
                $recipient->mobile,
                $recipient->mobile,
                $recipient->mobile,
                $recipient->mobile,
            ],
            $text
        );
    }

    private function getAccessToken(): string
    {
        $token = (string) (
            config('services.whatsapp.access_token')
            ?: config('whatsapp.access_token')
            ?: env('WHATSAPP_ACCESS_TOKEN')
            ?: env('WHATSAPP_TOKEN')
        );

        $token = trim($token);

        if ($token === '') {
            throw new RuntimeException(
                'WhatsApp access token configured nahi hai.'
            );
        }

        return $token;
    }

    private function getPhoneNumberId(): string
    {
        $phoneNumberId = (string) (
            config('services.whatsapp.phone_number_id')
            ?: config('whatsapp.phone_number_id')
            ?: env('WHATSAPP_PHONE_NUMBER_ID')
        );

        $phoneNumberId = trim($phoneNumberId);

        if ($phoneNumberId === '') {
            throw new RuntimeException(
                'WhatsApp Phone Number ID configured nahi hai.'
            );
        }

        return $phoneNumberId;
    }

    private function getGraphApiVersion(): string
    {
        $version = (string) (
            config('services.whatsapp.graph_version')
            ?: config('services.whatsapp.api_version')
            ?: config('whatsapp.graph_version')
            ?: env('WHATSAPP_GRAPH_VERSION')
            ?: env('WHATSAPP_API_VERSION')
            ?: self::GRAPH_API_VERSION
        );

        $version = trim($version);

        if ($version === '') {
            return self::GRAPH_API_VERSION;
        }

        return str_starts_with($version, 'v')
            ? $version
            : 'v' . $version;
    }
}