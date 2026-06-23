<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public static function send(string $number, string $message): bool
    {
        $apiKey = env('WHATSAPP_APIKEY');
        $sender = env('WHATSAPP_SENDER');
        

        if (!$apiKey || !$sender) {
            \Log::warning('WhatsApp credentials not configured');
            return false;
        }

        try {
            $response = Http::post('https://whatsapp.sambcart.com/send-message', [
                'api_key' => $apiKey,
                'sender' => $sender,
                'number' => '91'.$number,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            \Log::error('WhatsApp send failed: ' . $e->getMessage());
            return false;
        }
    }
}

