<?php

namespace App\Http\Controllers;

use App\Services\KirimiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiTestController extends Controller
{
    /**
     * Endpoint sederhana untuk testing koneksi API (Ping).
     *
     * URL: GET /api/ping atau GET /api/test-hit
     */
    public function ping(Request $request): JsonResponse
    {
        return response()->json([
            'status'      => 'success',
            'code'        => 200,
            'message'     => 'API HRD System aktif dan siap menerima request.',
            'environment' => config('app.env'),
            'timestamp'   => now()->format('Y-m-d H:i:s'),
            'client_ip'   => $request->ip(),
            'available_test_endpoints' => [
                'ping'          => url('/api/ping'),
                'test_kirim_wa' => url('/api/test-kirim-wa?phone=082245833986&message=Halo+Testing'),
                'test_kirimi'   => url('/api/test-kirimi'),
                'test_hit_url'  => url('/api/test-hit-url?url=https://api.kirimi.id'),
            ]
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Endpoint GET untuk testing pengiriman WhatsApp via Kirimi.id.
     *
     * URL: GET /api/test-kirim-wa
     * Query Parameters:
     * - phone: Nomor HP tujuan (misal: 082245833986). Jika kosong, menggunakan KIRIMI_OVERRIDE_PHONE atau default.
     * - message: Pesan yang ingin dikirimkan.
     */
    public function testSendWhatsApp(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        $defaultPhone = config('services.kirimi.override_phone') ?: '082245833986';
        $receiverPhone = $request->query('phone', $defaultPhone);

        $defaultMessage = "🔔 *[TESTING WHATSAPP HRD]*\n"
            . "Halo! Ini adalah pesan uji coba pengiriman notifikasi dari HRD System.\n"
            . "----------------------------------------\n"
            . "🕒 Waktu Kirim: " . now()->format('d M Y, H:i:s') . "\n"
            . "🌐 Server: " . config('app.url') . "\n"
            . "Status: API Terhubung dengan baik.";

        $message = $request->query('message', $defaultMessage);

        // Eksekusi pengiriman via KirimiService
        $result = KirimiService::sendTextMessage($receiverPhone, $message);

        $executionTime = round((microtime(true) - $startTime) * 1000, 2) . ' ms';

        $secret = config('services.kirimi.secret');
        $maskedSecret = $secret ? (substr($secret, 0, 6) . '...' . substr($secret, -6)) : null;

        // Panduan jika terjadi error umum
        $diagnosis = null;
        if (!$result['success']) {
            $errMessage = is_array($result['response']) ? ($result['response']['message'] ?? '') : ($result['error'] ?? '');

            if (stripos($errMessage, 'Device is not connected') !== false) {
                $diagnosis = 'Device WhatsApp Anda di Kirimi.id sedang tidak terhubung (terputus). Silakan buka Dashboard https://kirimi.id, lalu hubungkan atau scan ulang QR Code pada device terkait (' . config('services.kirimi.device_id') . ').';
            } elseif (stripos($errMessage, 'unauthorized') !== false || stripos($errMessage, 'secret') !== false) {
                $diagnosis = 'Kredensial Kirimi (secret atau user_code) tidak valid. Periksa file .env untuk KIRIMI_USER_CODE dan KIRIMI_SECRET.';
            } else {
                $diagnosis = 'Periksa respon detail pada kirimi_response di bawah untuk meninjau keterangan dari Kirimi.id.';
            }
        } else {
            $diagnosis = 'Pesan berhasil diterima dan dikirimkan oleh gateway Kirimi.id.';
        }

        return response()->json([
            'status'         => $result['success'] ? 'success' : 'failed',
            'code'           => $result['success'] ? 200 : 400,
            'message'        => $result['success'] ? 'Pesan WhatsApp berhasil dikirim!' : 'Pengiriman WhatsApp gagal.',
            'diagnosis'      => $diagnosis,
            'input_data'     => [
                'receiver_input'     => $receiverPhone,
                'receiver_formatted' => KirimiService::formatPhoneNumber($receiverPhone),
                'message_preview'    => $message,
            ],
            'kirimi_config'  => [
                'user_code'      => config('services.kirimi.user_code'),
                'device_id'      => config('services.kirimi.device_id'),
                'api_url'        => config('services.kirimi.api_url'),
                'secret_masked'  => $maskedSecret,
                'override_phone' => config('services.kirimi.override_phone'),
            ],
            'kirimi_response'=> $result['response'] ?? $result['error'],
            'execution_time' => $executionTime,
            'timestamp'      => now()->format('Y-m-d H:i:s'),
        ], $result['success'] ? 200 : 400, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Endpoint GET untuk testing hit ke URL API luar sembarang (Network / HTTP Connectivity check).
     *
     * URL: GET /api/test-hit-url?url=https://api.kirimi.id
     */
    public function testHitUrl(Request $request): JsonResponse
    {
        $targetUrl = $request->query('url', 'https://api.kirimi.id');
        $startTime = microtime(true);

        try {
            $response = Http::timeout(10)->get($targetUrl);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2) . ' ms';

            return response()->json([
                'status'         => 'success',
                'target_url'     => $targetUrl,
                'http_status'    => $response->status(),
                'execution_time' => $executionTime,
                'response_body'  => $response->json() ?? substr($response->body(), 0, 500),
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return response()->json([
                'status'         => 'error',
                'target_url'     => $targetUrl,
                'error_message'  => $e->getMessage(),
                'execution_time' => round((microtime(true) - $startTime) * 1000, 2) . ' ms',
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }
}

