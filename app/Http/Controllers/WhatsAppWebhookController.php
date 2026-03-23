<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Meta calls GET to verify the webhook endpoint during setup.
     * It sends hub.mode, hub.challenge, and hub.verify_token.
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $challenge = $request->query('hub_challenge');
        $token     = $request->query('hub_verify_token');

        if ($mode === 'subscribe' && $token === config('whatsapp.webhook_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Meta sends POST for incoming messages and delivery status updates.
     * For MVP: just log them. Can be extended to mark appointments as confirmed, etc.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                // Incoming message from patient
                foreach ($value['messages'] ?? [] as $message) {
                    Log::info('WhatsApp:incoming', [
                        'from' => $message['from'] ?? null,
                        'type' => $message['type'] ?? null,
                        'text' => $message['text']['body'] ?? null,
                    ]);
                }

                // Delivery / read status updates
                foreach ($value['statuses'] ?? [] as $status) {
                    Log::info('WhatsApp:status', [
                        'id'        => $status['id'] ?? null,
                        'status'    => $status['status'] ?? null,
                        'recipient' => $status['recipient_id'] ?? null,
                    ]);
                }
            }
        }

        // Must return 200 quickly or Meta will retry
        return response('OK', 200);
    }
}
