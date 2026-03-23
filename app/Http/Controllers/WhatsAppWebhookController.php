<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                    $from = $message['from'] ?? null;
                    $body = $message['text']['body'] ?? null;
                    [$patient, $clinic] = $from ? $this->resolvePatientAndClinic($from) : [null, null];

                    Log::info('WhatsApp:incoming', [
                        'from'       => $from,
                        'type'       => $message['type'] ?? null,
                        'text'       => $body,
                        'patient_id' => $patient?->id,
                        'clinic_id'  => $clinic?->id,
                    ]);

                    if ($from && $clinic) {
                        DB::table('whatsapp_messages')->insert([
                            'clinic_id'  => $clinic->id,
                            'patient_id' => $patient?->id,
                            'phone'      => $from,
                            'direction'  => 'in',
                            'body'       => $body,
                            'template'   => null,
                            'read_at'    => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
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

    /**
     * Find the patient and clinic for an incoming WhatsApp message.
     *
     * Strategy: look up whatsapp_last_contact to find which clinic wrote
     * to this number most recently, then find the patient within that clinic.
     * Falls back to a global phone search if no contact record exists.
     *
     * Returns [Patient|null, Clinic|null].
     */
    private function resolvePatientAndClinic(string $from): array
    {
        $wa = app(WhatsAppService::class);

        // 1. Check which clinic last contacted this number
        $contact = DB::table('whatsapp_last_contact')->where('phone', $from)->first();

        if ($contact) {
            $patient = Patient::where('clinic_id', $contact->clinic_id)
                ->whereNotNull('phone')
                ->get(['id', 'first_name', 'last_name', 'phone', 'clinic_id'])
                ->first(fn($p) => $wa->normalizePhone($p->phone) === $from);

            if ($patient) return [$patient, $patient->clinic];
        }

        // 2. Fallback: search all clinics (handles first-ever reply before any outgoing)
        $patient = Patient::whereNotNull('phone')
            ->get(['id', 'first_name', 'last_name', 'phone', 'clinic_id'])
            ->first(fn($p) => $wa->normalizePhone($p->phone) === $from);

        return $patient ? [$patient, $patient->clinic] : [null, null];
    }
}
