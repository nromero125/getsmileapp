<?php

namespace App\Imports;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PatientsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped  = 0;
    public array $errors = [];

    public function __construct(private int $clinicId) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // 1-indexed + header row

            $firstName = trim($row['nombre'] ?? $row['first_name'] ?? '');
            $lastName  = trim($row['apellido'] ?? $row['last_name'] ?? '');

            if (! $firstName || ! $lastName) {
                $this->errors[] = "Fila {$rowNum}: nombre y apellido son requeridos.";
                $this->skipped++;
                continue;
            }

            $phone    = $this->normalizePhone($row['telefono'] ?? $row['phone'] ?? null);
            $email    = strtolower(trim($row['email'] ?? '')) ?: null;
            $document = trim($row['cedula_rnc'] ?? $row['client_document'] ?? '') ?: null;

            // Duplicate check by phone or email within this clinic
            $exists = Patient::where('clinic_id', $this->clinicId)
                ->where(function ($q) use ($phone, $email, $document) {
                    if ($phone)    $q->orWhere('phone', $phone);
                    if ($email)    $q->orWhere('email', $email);
                    if ($document) $q->orWhere('client_document', $document);
                })
                ->exists();

            if ($exists) {
                $this->skipped++;
                continue;
            }

            $birth = null;
            if (! empty($row['fecha_nacimiento'] ?? $row['birth_date'] ?? null)) {
                try {
                    $birth = Carbon::parse($row['fecha_nacimiento'] ?? $row['birth_date'])->toDateString();
                } catch (\Throwable) {}
            }

            Patient::create([
                'clinic_id'       => $this->clinicId,
                'first_name'      => $firstName,
                'last_name'       => $lastName,
                'email'           => $email,
                'phone'           => $phone,
                'client_document' => $document,
                'birth_date'      => $birth,
                'gender'          => $this->normalizeGender($row['genero'] ?? $row['gender'] ?? null),
                'address'         => trim($row['direccion'] ?? $row['address'] ?? '') ?: null,
                'notes'           => trim($row['notas'] ?? $row['notes'] ?? '') ?: null,
            ]);

            $this->imported++;
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) return null;
        $digits = preg_replace('/\D/', '', $phone);
        return $digits ?: null;
    }

    private function normalizeGender(?string $g): ?string
    {
        if (! $g) return null;
        $g = strtolower(trim($g));
        if (in_array($g, ['m', 'masculino', 'male', 'hombre'])) return 'M';
        if (in_array($g, ['f', 'femenino', 'female', 'mujer']))  return 'F';
        return null;
    }
}
