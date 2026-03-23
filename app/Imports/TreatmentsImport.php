<?php

namespace App\Imports;

use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TreatmentsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped  = 0;
    public array $errors = [];

    private array $categoryCache = [];

    public function __construct(private int $clinicId) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;

            $name = trim($row['nombre'] ?? $row['name'] ?? '');
            if (! $name) {
                $this->errors[] = "Fila {$rowNum}: nombre del tratamiento es requerido.";
                $this->skipped++;
                continue;
            }

            $categoryName = trim($row['categoria'] ?? $row['category'] ?? 'General');
            $categoryId   = $this->resolveCategory($categoryName);

            $price    = (float) ($row['precio'] ?? $row['price'] ?? 0);
            $duration = (int)   ($row['duracion_minutos'] ?? $row['duration_minutes'] ?? 30);

            $exists = Treatment::where('clinic_id', $this->clinicId)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                $this->skipped++;
                continue;
            }

            Treatment::create([
                'clinic_id'            => $this->clinicId,
                'treatment_category_id' => $categoryId,
                'name'                 => $name,
                'description'          => trim($row['descripcion'] ?? $row['description'] ?? '') ?: null,
                'default_price'        => $price,
                'duration_minutes'     => $duration,
                'is_active'            => true,
            ]);

            $this->imported++;
        }
    }

    private function resolveCategory(string $name): int
    {
        if (isset($this->categoryCache[$name])) {
            return $this->categoryCache[$name];
        }

        $cat = TreatmentCategory::firstOrCreate(
            ['clinic_id' => $this->clinicId, 'name' => $name],
            ['color' => '#6B7280']
        );

        $this->categoryCache[$name] = $cat->id;
        return $cat->id;
    }
}
