<?php

namespace App\Imports;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AppointmentsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped  = 0;
    public array $errors = [];

    private array $patientCache = [];
    private array $dentistCache = [];

    public function __construct(private int $clinicId) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;

            // Resolve patient
            $patientRef = trim($row['email_paciente'] ?? $row['patient_email'] ?? $row['cedula_paciente'] ?? $row['patient_document'] ?? '');
            $patientId  = $this->resolvePatient($patientRef);

            if (! $patientId) {
                $this->errors[] = "Fila {$rowNum}: paciente '{$patientRef}' no encontrado.";
                $this->skipped++;
                continue;
            }

            // Parse date
            $dateRaw = $row['fecha_cita'] ?? $row['appointment_date'] ?? null;
            if (! $dateRaw) {
                $this->errors[] = "Fila {$rowNum}: fecha_cita es requerida.";
                $this->skipped++;
                continue;
            }

            try {
                $date = Carbon::parse($dateRaw);
            } catch (\Throwable) {
                $this->errors[] = "Fila {$rowNum}: fecha_cita inválida '{$dateRaw}'.";
                $this->skipped++;
                continue;
            }

            // Resolve dentist (optional)
            $dentistRef = trim($row['email_dentista'] ?? $row['dentist_email'] ?? '');
            $dentistId  = $dentistRef ? $this->resolveDentist($dentistRef) : null;

            $validStatuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];
            $status = in_array($row['estado'] ?? $row['status'] ?? '', $validStatuses)
                ? ($row['estado'] ?? $row['status'])
                : 'completed';

            Appointment::create([
                'clinic_id'        => $this->clinicId,
                'patient_id'       => $patientId,
                'dentist_id'       => $dentistId,
                'appointment_date' => $date,
                'status'           => $status,
                'notes'            => trim($row['notas'] ?? $row['notes'] ?? '') ?: null,
                'total_cost'       => (float) ($row['costo_total'] ?? $row['total_cost'] ?? 0),
            ]);

            $this->imported++;
        }
    }

    private function resolvePatient(string $ref): ?int
    {
        if (! $ref) return null;
        if (isset($this->patientCache[$ref])) return $this->patientCache[$ref];

        $patient = Patient::where('clinic_id', $this->clinicId)
            ->where(fn($q) => $q->where('email', $ref)->orWhere('client_document', $ref))
            ->first();

        $this->patientCache[$ref] = $patient?->id;
        return $patient?->id;
    }

    private function resolveDentist(string $email): ?int
    {
        if (isset($this->dentistCache[$email])) return $this->dentistCache[$email];

        $dentist = User::where('clinic_id', $this->clinicId)
            ->where('email', $email)
            ->first();

        $this->dentistCache[$email] = $dentist?->id;
        return $dentist?->id;
    }
}
