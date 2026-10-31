<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KirimiService
{
    /**
     * Format phone number to international WhatsApp format (e.g. 628123456789).
     */
    public static function formatPhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Keep only digits
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) {
            return null;
        }

        // Convert leading 0 to 62
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (!str_starts_with($clean, '62')) {
            $clean = '62' . $clean;
        }

        return $clean;
    }

    /**
     * Send a WhatsApp text message via Kirimi.id REST API.
     *
     * @param string $receiverPhone
     * @param string $message
     * @return array ['success' => bool, 'response' => mixed, 'error' => ?string]
     */
    public static function sendTextMessage(string $receiverPhone, string $message): array
    {
        try {
            $userCode = config('services.kirimi.user_code', env('KIRIMI_USER_CODE'));
            $deviceId = config('services.kirimi.device_id', env('KIRIMI_DEVICE_ID'));
            $secret   = config('services.kirimi.secret', env('KIRIMI_SECRET'));
            $url      = config('services.kirimi.api_url', env('KIRIMI_API_URL', 'https://api.kirimi.id/v1/send-message'));

            if (!$userCode || !$deviceId || !$secret) {
                Log::warning('[KirimiService] Kirimi credentials are missing in configuration.');
                return [
                    'success'  => false,
                    'response' => null,
                    'error'    => 'Kredensial Kirimi belum lengkap (user_code, device_id, atau secret kosong).'
                ];
            }

            $formattedPhone = self::formatPhoneNumber($receiverPhone);
            if (!$formattedPhone) {
                return [
                    'success'  => false,
                    'response' => null,
                    'error'    => "Format nomor HP tidak valid: '{$receiverPhone}'"
                ];
            }

            $payload = [
                'user_code'          => $userCode,
                'device_id'          => $deviceId,
                'receiver'           => $formattedPhone,
                'message'            => $message,
                'secret'             => $secret,
                'enableTypingEffect' => false,
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

            $body = $response->json() ?? $response->body();
            $isSuccess = $response->successful() && is_array($body) && ($body['success'] ?? false) === true;

            if (!$isSuccess) {
                Log::warning("[KirimiService] WhatsApp message failed to {$formattedPhone}", [
                    'status' => $response->status(),
                    'body'   => $body,
                ]);
            } else {
                Log::info("[KirimiService] WhatsApp message sent successfully to {$formattedPhone}");
            }

            return [
                'success'  => $isSuccess,
                'response' => $body,
                'error'    => $isSuccess ? null : (is_array($body) ? ($body['message'] ?? 'Kirimi API returned failure') : (string) $body),
            ];

        } catch (\Throwable $e) {
            Log::error('[KirimiService] Exception while sending message: ' . $e->getMessage(), [
                'receiver' => $receiverPhone,
                'trace'    => $e->getTraceAsString(),
            ]);

            return [
                'success'  => false,
                'response' => null,
                'error'    => $e->getMessage(),
            ];
        }
    }
}
