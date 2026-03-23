<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WhatsAppInboxController extends Controller
{
    /**
     * List all conversations for this clinic, grouped by phone.
     */
    public function index(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;

        // One row per phone: last message + unread count
        $conversations = DB::table('whatsapp_messages as m')
            ->where('m.clinic_id', $clinicId)
            ->select(
                'm.phone',
                'm.patient_id',
                DB::raw('MAX(m.created_at) as last_message_at'),
                DB::raw("MAX(CASE WHEN m.direction = 'in' OR m.direction = 'out' THEN m.body END) as last_body"),
                DB::raw("MAX(CASE WHEN m.created_at = (SELECT MAX(m2.created_at) FROM whatsapp_messages m2 WHERE m2.phone = m.phone AND m2.clinic_id = m.clinic_id) THEN m.direction END) as last_direction"),
                DB::raw("MAX(CASE WHEN m.created_at = (SELECT MAX(m2.created_at) FROM whatsapp_messages m2 WHERE m2.phone = m.phone AND m2.clinic_id = m.clinic_id) THEN m.template END) as last_template"),
                DB::raw("SUM(CASE WHEN m.direction = 'in' AND m.read_at IS NULL THEN 1 ELSE 0 END) as unread_count")
            )
            ->groupBy('m.phone', 'm.patient_id')
            ->orderByDesc('last_message_at')
            ->get();

        // Enrich with patient names
        $patientIds = $conversations->pluck('patient_id')->filter()->unique()->values();
        $patients   = \App\Models\Patient::whereIn('id', $patientIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        $conversations = $conversations->map(function ($c) use ($patients) {
            $patient = $c->patient_id ? $patients->get($c->patient_id) : null;
            return [
                'phone'           => $c->phone,
                'patient_id'      => $c->patient_id,
                'patient_name'    => $patient ? $patient->full_name : null,
                'last_message_at' => $c->last_message_at,
                'last_body'       => $c->last_body,
                'last_direction'  => $c->last_direction,
                'last_template'   => $c->last_template,
                'unread_count'    => (int) $c->unread_count,
            ];
        });

        return Inertia::render('WhatsApp/Inbox', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Return the message thread for a given phone number and mark as read.
     */
    public function thread(Request $request, string $phone)
    {
        $clinicId = auth()->user()->clinic_id;

        // Mark all incoming messages from this phone as read
        DB::table('whatsapp_messages')
            ->where('clinic_id', $clinicId)
            ->where('phone', $phone)
            ->where('direction', 'in')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = DB::table('whatsapp_messages')
            ->where('clinic_id', $clinicId)
            ->where('phone', $phone)
            ->orderBy('created_at')
            ->get(['id', 'direction', 'body', 'template', 'read_at', 'created_at']);

        $patient = DB::table('whatsapp_messages as m')
            ->join('patients as p', 'p.id', '=', 'm.patient_id')
            ->where('m.clinic_id', $clinicId)
            ->where('m.phone', $phone)
            ->whereNotNull('m.patient_id')
            ->select('p.id', 'p.first_name', 'p.last_name')
            ->first();

        return response()->json([
            'messages' => $messages,
            'patient'  => $patient,
        ]);
    }
}
