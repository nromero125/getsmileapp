<?php

namespace App\Http\Controllers\Appointment;

use App\Http\Controllers\Controller;
use App\Mail\AppointmentConfirmationMail;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use App\Notifications\AppointmentConfirmationWhatsApp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;

        // Calendar view - return appointments for FullCalendar
        $start = $request->start ? Carbon::parse($request->start) : Carbon::now()->startOfMonth();
        $end   = $request->end   ? Carbon::parse($request->end)   : Carbon::now()->endOfMonth();

        $appointments = Appointment::where('clinic_id', $clinicId)
            ->whereBetween('appointment_date', [$start, $end])
            ->with(['patient', 'dentist', 'treatments'])
            ->get()
            ->map(fn($appt) => [
                'id'            => $appt->id,
                'title'         => $appt->patient->full_name,
                'start'         => $appt->appointment_date->toIso8601String(),
                'end'           => $appt->end_time->toIso8601String(),
                'color'         => $appt->status_color,
                'extendedProps' => [
                    'patient'  => $appt->patient->full_name,
                    'dentist'  => $appt->dentist->name,
                    'status'   => $appt->status,
                    'reason'   => $appt->reason,
                    'patient_id' => $appt->patient_id,
                ],
            ]);

        $dentists = User::where('clinic_id', $clinicId)
            ->whereIn('role', ['admin', 'dentist'])
            ->get(['id', 'name', 'specialty', 'is_active']);

        $patients  = Patient::where('clinic_id', $clinicId)
            ->get(['id', 'first_name', 'last_name', 'phone']);

        $treatments = Treatment::where('clinic_id', $clinicId)
            ->where('is_active', true)
            ->get(['id', 'name', 'default_price', 'duration_minutes']);

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'dentists'     => $dentists,
            'patients'     => $patients,
            'treatments'   => $treatments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'dentist_id'       => 'required|exists:users,id',
            'appointment_date' => 'required|date',
            'duration_minutes' => 'required|integer|min:15',
            'reason'           => 'nullable|string|max:255',
            'notes'            => 'nullable|string',
            'status'           => 'required|in:scheduled,confirmed',
            'treatment_ids'    => 'nullable|array',
            'treatment_ids.*'  => 'exists:treatments,id',
        ]);

        $clinicId = auth()->user()->clinic_id;

        // Conflict detection
        $conflict = Appointment::where('clinic_id', $clinicId)
            ->where('dentist_id', $validated['dentist_id'])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where(function ($q) use ($validated) {
                $start = Carbon::parse($validated['appointment_date']);
                $end   = $start->copy()->addMinutes($validated['duration_minutes']);
                $q->whereBetween('appointment_date', [$start->subMinute(), $end]);
            })
            ->exists();

        if ($conflict) {
            return back()->withErrors(['appointment_date' => 'This dentist has a conflicting appointment at this time.']);
        }

        $appointment = Appointment::create([
            ...$validated,
            'clinic_id'  => $clinicId,
            'created_by' => auth()->id(),
        ]);

        if (!empty($validated['treatment_ids'])) {
            $treatments = Treatment::whereIn('id', $validated['treatment_ids'])->get();
            $syncData = [];
            $total = 0;
            foreach ($treatments as $treatment) {
                $syncData[$treatment->id] = ['price' => $treatment->default_price, 'quantity' => 1];
                $total += $treatment->default_price;
            }
            $appointment->treatments()->sync($syncData);
            $appointment->update(['total_cost' => $total]);
        }

        return redirect()->route('appointments.index')
            ->with('success', 'Appointment booked successfully.');
    }

    public function show(Appointment $appointment)
    {
        $this->authorizeClinic($appointment);
        $appointment->load(['patient', 'dentist', 'treatments', 'clinicalNotes.dentist', 'invoice']);
        return Inertia::render('Appointments/Show', ['appointment' => $appointment]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->authorizeClinic($appointment);

        $validated = $request->validate([
            'patient_id'          => 'sometimes|exists:patients,id',
            'dentist_id'          => 'sometimes|exists:users,id',
            'appointment_date'    => 'sometimes|date',
            'duration_minutes'    => 'sometimes|integer|min:15',
            'status'              => 'sometimes|in:scheduled,confirmed,in_progress,completed,cancelled,no_show',
            'reason'              => 'nullable|string|max:255',
            'notes'               => 'nullable|string',
            'cancellation_reason' => 'nullable|string',
        ]);

        $appointment->update($validated);

        return back()->with('success', 'Appointment updated.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorizeClinic($appointment);
        $appointment->delete();
        return redirect()->route('appointments.index')
            ->with('success', 'Appointment deleted.');
    }

    public function apiList(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $start = Carbon::parse($request->start ?? now()->startOfMonth());
        $end   = Carbon::parse($request->end   ?? now()->endOfMonth());

        $appointments = Appointment::where('clinic_id', $clinicId)
            ->whereBetween('appointment_date', [$start, $end])
            ->with(['patient', 'dentist'])
            ->get()
            ->map(fn($appt) => [
                'id'    => $appt->id,
                'title' => $appt->patient->full_name,
                'start' => $appt->appointment_date->toIso8601String(),
                'end'   => $appt->end_time->toIso8601String(),
                'color' => $appt->status_color,
                'extendedProps' => [
                    'patient'              => $appt->patient->full_name,
                    'patient_id'           => $appt->patient_id,
                    'patient_email'        => $appt->patient->email,
                    'dentist'              => $appt->dentist->name,
                    'dentist_id'           => $appt->dentist_id,
                    'dentist_inactive'     => ! $appt->dentist->is_active,
                    'status'               => $appt->status,
                    'reason'               => $appt->reason,
                    'duration'             => $appt->duration_minutes,
                    'confirmation_sent_at' => $appt->confirmation_sent_at?->toIso8601String(),
                ],
            ]);

        return response()->json($appointments);
    }

    public function sendConfirmation(Appointment $appointment)
    {
        $this->authorizeClinic($appointment);

        if (! $appointment->patient->email && ! $appointment->patient->phone) {
            return back()->with('error', 'El paciente no tiene correo ni teléfono registrado.');
        }

        if (in_array($appointment->status, ['completed', 'cancelled', 'no_show'])) {
            return back()->with('error', 'No se puede enviar confirmación a una cita finalizada.');
        }

        $appointment->update([
            'confirmation_token'   => Str::uuid(),
            'confirmation_sent_at' => now(),
        ]);

        $appointment->load(['patient', 'dentist', 'clinic']);

        $sent   = [];
        $failed = [];

        // Email confirmation — independent of WhatsApp
        if ($appointment->patient->email) {
            try {
                Mail::to($appointment->patient->email)
                    ->send(new AppointmentConfirmationMail($appointment));
                $sent[] = 'correo';
            } catch (\Throwable $e) {
                \Log::error('sendConfirmation:email_failed', [
                    'appointment' => $appointment->id,
                    'error'       => $e->getMessage(),
                ]);
                $failed[] = 'correo';
            }
        }

        // WhatsApp confirmation — independent of email
        if ($appointment->patient->phone) {
            try {
                $appointment->patient->notify(new AppointmentConfirmationWhatsApp($appointment));
                $sent[] = 'WhatsApp';
            } catch (\Throwable $e) {
                \Log::error('sendConfirmation:whatsapp_failed', [
                    'appointment' => $appointment->id,
                    'error'       => $e->getMessage(),
                ]);
                $failed[] = 'WhatsApp';
            }
        }

        if (empty($sent) && ! empty($failed)) {
            return back()->with('error', 'No se pudo enviar la confirmación por ' . implode(' ni ', $failed) . '.');
        }

        $msg = 'Confirmación enviada por ' . implode(' y ', $sent) . '.';
        if (! empty($failed)) {
            $msg .= ' (Falló: ' . implode(', ', $failed) . ')';
        }

        return back()->with('success', $msg);
    }

    public function confirm(string $token)
    {
        $appointment = Appointment::where('confirmation_token', $token)
            ->with(['patient', 'dentist', 'clinic'])
            ->first();

        if (! $appointment) {
            return view('appointment.confirmed', ['success' => false, 'appointment' => null]);
        }

        $appointment->update([
            'status'             => 'confirmed',
            'confirmed_at'       => now(),
            'confirmation_token' => null,
        ]);

        return view('appointment.confirmed', ['success' => true, 'appointment' => $appointment]);
    }

    private function authorizeClinic(Appointment $appointment): void
    {
        if ($appointment->clinic_id !== auth()->user()->clinic_id) {
            abort(403);
        }
    }
}
