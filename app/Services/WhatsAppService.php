<?php

namespace App\Services;

use App\Models\Clinic;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private ?string $phoneNumberId;
    private ?string $token;

    public function __construct()
    {
        $this->apiUrl        = config('whatsapp.api_url');
        $this->phoneNumberId = config('whatsapp.phone_number_id') ?: null;
        $this->token         = config('whatsapp.token') ?: null;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Send a pre-approved template message.
     *
     * @param  string  $to       Recipient phone (any format — will be normalized)
     * @param  string  $template Key from config('whatsapp.templates')
     * @param  array   $params   Body parameter values in order
     * @param  array   $header   Optional header parameter (e.g. ['type'=>'text','text'=>'...'])
     */
    public function sendTemplate(
        string  $to,
        string  $template,
        array   $params = [],
        array   $header = [],
        ?Clinic $clinic = null,
    ): bool {
        if ($clinic && ! $clinic->waCanSend()) {
            Log::warning('WhatsApp: quota exhausted', [
                'clinic'    => $clinic->id,
                'plan'      => $clinic->wa_plan,
                'used'      => $clinic->wa_messages_used,
                'quota'     => $clinic->wa_messages_quota + $clinic->wa_messages_extra,
            ]);
            return false;
        }
        $phone = $this->normalizePhone($to);
        if (! $phone) {
            Log::warning('WhatsApp: invalid phone number', ['raw' => $to]);
            return false;
        }

        $cfg = config("whatsapp.templates.{$template}");
        if (! $cfg) {
            Log::error("WhatsApp: template '{$template}' not defined in config.");
            return false;
        }

        $components = [];

        if (! empty($header)) {
            $components[] = ['type' => 'header', 'parameters' => [$header]];
        }

        if (! empty($params)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(
                    fn($v) => ['type' => 'text', 'text' => (string) $v],
                    $params
                ),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $phone,
            'type'              => 'template',
            'template'          => [
                'name'       => $cfg['name'],
                'language'   => ['code' => $cfg['language']],
                'components' => $components,
            ],
        ];

        return $this->send($payload, $template, $phone, $clinic);
    }

    /**
     * Send a free-form text message (only valid within 24h customer service window).
     */
    public function sendText(string $to, string $message): bool
    {
        $phone = $this->normalizePhone($to);
        if (! $phone) {
            Log::warning('WhatsApp: invalid phone number', ['raw' => $to]);
            return false;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $phone,
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => $message],
        ];

        return $this->send($payload, 'text', $phone);
    }

    // ── Phone normalization ───────────────────────────────────────────────────

    /**
     * Normalize a phone number to the E.164-like format Meta requires (digits only, with country code).
     * Examples:
     *   "809-555-1234"   → "1809555 1234" → "18095551234"
     *   "+1 809 555 1234"→ "18095551234"
     *   "18095551234"    → "18095551234"
     */
    public function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (empty($digits)) return null;

        $countryCode = config('whatsapp.default_country_code', '1');

        // Already has country code prefix
        if (str_starts_with($digits, $countryCode) && strlen($digits) > 10) {
            return $digits;
        }

        // 10-digit local number (DR: 809/829/849 + 7 digits)
        if (strlen($digits) === 10) {
            return $countryCode . $digits;
        }

        // Unexpected format — return as-is and let Meta reject it
        return $digits;
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function send(array $payload, string $type, string $phone, ?Clinic $clinic = null): bool
    {
        if (! $this->token || ! $this->phoneNumberId) {
            Log::error('WhatsApp: WHATSAPP_TOKEN or WHATSAPP_PHONE_NUMBER_ID not configured.');
            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                Log::info('WhatsApp:sent', [
                    'type'  => $type,
                    'to'    => $phone,
                    'msgId' => $response->json('messages.0.id'),
                ]);
                $clinic?->waIncrementUsed();
                return true;
            }

            Log::error('WhatsApp:failed', [
                'type'   => $type,
                'to'     => $phone,
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('WhatsApp:exception', [
                'type'    => $type,
                'to'      => $phone,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
