<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RazorpayService
{
    private const API_BASE_URL = 'https://api.razorpay.com/v1';

    /**
     * Create a Razorpay order.
     */
    public static function createOrder(
        float $amount,
        string $receipt,
        array $notes = [],
        string $currency = 'INR'
    ): array {
        if ($amount <= 0) {
            return self::failure('Invalid Razorpay order amount.');
        }

        $receipt = trim($receipt);

        if ($receipt === '') {
            return self::failure('Razorpay receipt is required.');
        }

        try {
            $credentials = self::credentials();

            if (! $credentials['valid']) {
                return self::failure($credentials['message']);
            }

            $payload = [
                'amount' => self::toPaise($amount),
                'currency' => strtoupper(trim($currency ?: 'INR')),
                'receipt' => mb_substr($receipt, 0, 40),
                'notes' => self::sanitizeNotes($notes),
            ];

            $response = self::client(
                $credentials['key'],
                $credentials['secret']
            )->post(self::API_BASE_URL . '/orders', $payload);

            if (! $response->successful()) {
                return self::apiFailure(
                    'Unable to create Razorpay order.',
                    $response,
                    'Razorpay Order API Error'
                );
            }

            $data = $response->json();

            if (! is_array($data) || empty($data['id'])) {
                return self::failure(
                    'Invalid response received from Razorpay while creating order.',
                    ['response' => $data]
                );
            }

            return self::success($data, 'Razorpay order created successfully.');
        } catch (Throwable $e) {
            return self::exceptionFailure('Razorpay Order Error', $e);
        }
    }

    /**
     * Create a Razorpay Payment Link.
     *
     * The payment is NOT considered received when this method succeeds.
     * Booking payment must be updated only after Razorpay webhook confirms payment.
     */
    public static function createPaymentLink(
        float $amount,
        string $referenceId,
        string $description,
        array $customer = [],
        array $notes = [],
        string $currency = 'INR'
    ): array {
        if ($amount <= 0) {
            return self::failure('Invalid Razorpay payment link amount.');
        }

        $referenceId = trim($referenceId);

        if ($referenceId === '') {
            return self::failure('Razorpay payment link reference is required.');
        }

        try {
            $credentials = self::credentials();

            if (! $credentials['valid']) {
                return self::failure($credentials['message']);
            }

            $payload = [
                'amount' => self::toPaise($amount),
                'currency' => strtoupper(trim($currency ?: 'INR')),
                'accept_partial' => false,
                'reference_id' => mb_substr($referenceId, 0, 40),
                'description' => mb_substr(
                    trim($description) !== ''
                        ? trim($description)
                        : 'Dura Cabs payment',
                    0,
                    255
                ),
                'notify' => [
                    // WhatsApp is sent by Dura Cabs using the approved Meta template.
                    'sms' => false,
                    'email' => false,
                ],
                'reminder_enable' => false,
                'notes' => self::sanitizeNotes($notes),
            ];

            $customerPayload = [];

            $name = trim((string) ($customer['name'] ?? ''));
            $contact = preg_replace('/\D+/', '', (string) ($customer['contact'] ?? ''));
            $email = trim((string) ($customer['email'] ?? ''));

            if ($name !== '') {
                $customerPayload['name'] = mb_substr($name, 0, 255);
            }

            if ($contact !== '') {
                $customerPayload['contact'] = mb_substr($contact, 0, 20);
            }

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $customerPayload['email'] = mb_substr($email, 0, 255);
            }

            if ($customerPayload !== []) {
                $payload['customer'] = $customerPayload;
            }

            $response = self::client(
                $credentials['key'],
                $credentials['secret']
            )->post(self::API_BASE_URL . '/payment_links', $payload);

            if (! $response->successful()) {
                return self::apiFailure(
                    'Unable to create Razorpay payment link.',
                    $response,
                    'Razorpay Payment Link API Error'
                );
            }

            $data = $response->json();

            if (
                ! is_array($data)
                || empty($data['id'])
                || empty($data['short_url'])
            ) {
                return self::failure(
                    'Invalid response received from Razorpay while creating payment link.',
                    ['response' => $data]
                );
            }

            return self::success(
                $data,
                'Razorpay payment link created successfully.'
            );
        } catch (Throwable $e) {
            return self::exceptionFailure('Razorpay Payment Link Error', $e);
        }
    }

    /**
     * Verify the checkout signature generated by Razorpay.
     */
    public static function verifyPayment(
        string $orderId,
        string $paymentId,
        string $signature
    ): array {
        $orderId = trim($orderId);
        $paymentId = trim($paymentId);
        $signature = trim($signature);

        if ($orderId === '' || $paymentId === '' || $signature === '') {
            return self::failure('Incomplete Razorpay payment verification data.');
        }

        try {
            $credentials = self::credentials();

            if (! $credentials['valid']) {
                return self::failure($credentials['message']);
            }

            $generatedSignature = hash_hmac(
                'sha256',
                $orderId . '|' . $paymentId,
                $credentials['secret']
            );

            $verified = hash_equals($generatedSignature, $signature);

            return [
                'status' => $verified,
                'message' => $verified
                    ? 'Payment verified successfully.'
                    : 'Invalid payment signature.',
                'data' => [
                    'order_id' => $orderId,
                    'payment_id' => $paymentId,
                    'verified' => $verified,
                ],
            ];
        } catch (Throwable $e) {
            return self::exceptionFailure('Razorpay Verify Error', $e);
        }
    }

    /**
     * Fetch a Razorpay payment directly from Razorpay.
     */
    public static function fetchPayment(string $paymentId): array
    {
        $paymentId = trim($paymentId);

        if ($paymentId === '') {
            return self::failure('Razorpay payment ID is required.');
        }

        try {
            $credentials = self::credentials();

            if (! $credentials['valid']) {
                return self::failure($credentials['message']);
            }

            $response = self::client(
                $credentials['key'],
                $credentials['secret']
            )->get(self::API_BASE_URL . '/payments/' . rawurlencode($paymentId));

            if (! $response->successful()) {
                return self::apiFailure(
                    'Unable to fetch Razorpay payment.',
                    $response,
                    'Razorpay Fetch Payment API Error'
                );
            }

            return self::success(
                $response->json(),
                'Razorpay payment fetched successfully.'
            );
        } catch (Throwable $e) {
            return self::exceptionFailure('Razorpay Fetch Payment Error', $e);
        }
    }

    /**
     * Fetch a Razorpay order directly from Razorpay.
     */
    public static function fetchOrder(string $orderId): array
    {
        $orderId = trim($orderId);

        if ($orderId === '') {
            return self::failure('Razorpay order ID is required.');
        }

        try {
            $credentials = self::credentials();

            if (! $credentials['valid']) {
                return self::failure($credentials['message']);
            }

            $response = self::client(
                $credentials['key'],
                $credentials['secret']
            )->get(self::API_BASE_URL . '/orders/' . rawurlencode($orderId));

            if (! $response->successful()) {
                return self::apiFailure(
                    'Unable to fetch Razorpay order.',
                    $response,
                    'Razorpay Fetch Order API Error'
                );
            }

            return self::success(
                $response->json(),
                'Razorpay order fetched successfully.'
            );
        } catch (Throwable $e) {
            return self::exceptionFailure('Razorpay Fetch Order Error', $e);
        }
    }

    /**
     * Capture a payment when auto-capture is disabled in Razorpay settings.
     */
    public static function capturePayment(
        string $paymentId,
        float $amount,
        string $currency = 'INR'
    ): array {
        $paymentId = trim($paymentId);

        if ($paymentId === '' || $amount <= 0) {
            return self::failure('Valid payment ID and amount are required.');
        }

        try {
            $credentials = self::credentials();

            if (! $credentials['valid']) {
                return self::failure($credentials['message']);
            }

            $response = self::client(
                $credentials['key'],
                $credentials['secret']
            )->post(
                self::API_BASE_URL
                . '/payments/'
                . rawurlencode($paymentId)
                . '/capture',
                [
                    'amount' => self::toPaise($amount),
                    'currency' => strtoupper(trim($currency ?: 'INR')),
                ]
            );

            if (! $response->successful()) {
                return self::apiFailure(
                    'Unable to capture Razorpay payment.',
                    $response,
                    'Razorpay Capture API Error'
                );
            }

            return self::success(
                $response->json(),
                'Razorpay payment captured successfully.'
            );
        } catch (Throwable $e) {
            return self::exceptionFailure('Razorpay Capture Error', $e);
        }
    }

    /**
     * Refund a Razorpay payment.
     */
    public static function refund(
        string $paymentId,
        float $amount,
        string $reason = '',
        array $notes = []
    ): array {
        $paymentId = trim($paymentId);

        if ($paymentId === '') {
            return self::failure('Razorpay payment ID is required.');
        }

        if ($amount <= 0) {
            return self::failure('Invalid refund amount.');
        }

        try {
            $credentials = self::credentials();

            if (! $credentials['valid']) {
                return self::failure($credentials['message']);
            }

            $refundNotes = $notes;

            if (trim($reason) !== '') {
                $refundNotes['reason'] = trim($reason);
            }

            $response = self::client(
                $credentials['key'],
                $credentials['secret']
            )->post(
                self::API_BASE_URL
                . '/payments/'
                . rawurlencode($paymentId)
                . '/refund',
                [
                    'amount' => self::toPaise($amount),
                    'notes' => self::sanitizeNotes($refundNotes),
                ]
            );

            if (! $response->successful()) {
                return self::apiFailure(
                    'Razorpay refund failed.',
                    $response,
                    'Razorpay Refund API Error'
                );
            }

            $data = $response->json();

            if (! is_array($data) || empty($data['id'])) {
                return self::failure(
                    'Invalid refund response received from Razorpay.',
                    ['response' => $data]
                );
            }

            return self::success($data, 'Refund processed successfully.');
        } catch (Throwable $e) {
            return self::exceptionFailure('Razorpay Refund Error', $e);
        }
    }

    /**
     * Fetch refund status.
     */
    public static function fetchRefund(string $refundId): array
    {
        $refundId = trim($refundId);

        if ($refundId === '') {
            return self::failure('Razorpay refund ID is required.');
        }

        try {
            $credentials = self::credentials();

            if (! $credentials['valid']) {
                return self::failure($credentials['message']);
            }

            $response = self::client(
                $credentials['key'],
                $credentials['secret']
            )->get(self::API_BASE_URL . '/refunds/' . rawurlencode($refundId));

            if (! $response->successful()) {
                return self::apiFailure(
                    'Unable to fetch Razorpay refund.',
                    $response,
                    'Razorpay Fetch Refund API Error'
                );
            }

            return self::success(
                $response->json(),
                'Razorpay refund fetched successfully.'
            );
        } catch (Throwable $e) {
            return self::exceptionFailure('Razorpay Fetch Refund Error', $e);
        }
    }

    /**
     * Verify Razorpay webhook signature.
     */
    public static function verifyWebhook(
        string $payload,
        string $signature
    ): bool {
        $secret = self::webhookSecret();
        $signature = trim($signature);

        if ($secret === '' || $payload === '' || $signature === '') {
            return false;
        }

        $generatedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($generatedSignature, $signature);
    }

    /**
     * Return public Razorpay key for Flutter checkout.
     */
    public static function publicKey(): ?string
    {
        $key = trim((string) config(
            'services.razorpay.key',
            env('RAZORPAY_KEY_ID')
        ));

        return $key !== '' ? $key : null;
    }

    private static function credentials(): array
    {
        $key = trim((string) config(
            'services.razorpay.key',
            env('RAZORPAY_KEY_ID')
        ));

        $secret = trim((string) config(
            'services.razorpay.secret',
            env('RAZORPAY_KEY_SECRET')
        ));

        if ($key === '' || $secret === '') {
            return [
                'valid' => false,
                'key' => $key,
                'secret' => $secret,
                'message' => 'Razorpay credentials are missing.',
            ];
        }

        return [
            'valid' => true,
            'key' => $key,
            'secret' => $secret,
            'message' => 'Razorpay credentials loaded.',
        ];
    }

    private static function webhookSecret(): string
    {
        return trim((string) config(
            'services.razorpay.webhook_secret',
            env('RAZORPAY_WEBHOOK_SECRET')
        ));
    }

    private static function client(string $key, string $secret)
    {
        return Http::withBasicAuth($key, $secret)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(2, 300, throw: false);
    }

    private static function toPaise(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private static function sanitizeNotes(array $notes): array
    {
        $clean = [];

        foreach ($notes as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            }

            $clean[mb_substr((string) $key, 0, 256)] = mb_substr(
                (string) $value,
                0,
                256
            );
        }

        return $clean;
    }

    private static function success(
        mixed $data,
        string $message
    ): array {
        return [
            'status' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    private static function failure(
        string $message,
        array $extra = []
    ): array {
        return array_merge([
            'status' => false,
            'message' => $message,
        ], $extra);
    }

    private static function apiFailure(
        string $message,
        Response $response,
        string $logMessage
    ): array {
        $body = $response->json();
        $razorpayMessage = data_get($body, 'error.description')
            ?? data_get($body, 'error.reason')
            ?? $message;

        Log::error($logMessage, [
            'http_status' => $response->status(),
            'razorpay_response' => $body,
        ]);

        return self::failure($razorpayMessage, [
            'http_status' => $response->status(),
            'response' => config('app.debug') ? $body : null,
        ]);
    }

    private static function exceptionFailure(
        string $logMessage,
        Throwable $e
    ): array {
        Log::error($logMessage, [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return self::failure(
            config('app.debug')
                ? $e->getMessage()
                : 'Razorpay service is temporarily unavailable.'
        );
    }
}