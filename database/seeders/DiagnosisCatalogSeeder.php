<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\DiagnosisCatalog;
use Illuminate\Database\Seeder;

class DiagnosisCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $clinicIds = Clinic::pluck('id');

        $entries = [
            ['code' => 'CAR', 'name' => 'Caries',               'description' => 'Lesión cariosa activa',                    'color' => '#EF4444', 'severity' => 'medium'],
            ['code' => 'CAR-P', 'name' => 'Caries profunda',    'description' => 'Caries con compromiso pulpar inminente',    'color' => '#DC2626', 'severity' => 'high'],
            ['code' => 'FRAC', 'name' => 'Fractura',             'description' => 'Fractura coronaria o radicular',           'color' => '#F97316', 'severity' => 'high'],
            ['code' => 'PERIO', 'name' => 'Periodontitis',       'description' => 'Enfermedad periodontal activa',            'color' => '#8B5CF6', 'severity' => 'high'],
            ['code' => 'GING', 'name' => 'Gingivitis',           'description' => 'Inflamación gingival',                    'color' => '#A78BFA', 'severity' => 'low'],
            ['code' => 'PROT', 'name' => 'Corona protésica',     'description' => 'Pieza con corona protésica',              'color' => '#F59E0B', 'severity' => 'low'],
            ['code' => 'OBT', 'name' => 'Obturación',            'description' => 'Restauración presente',                   'color' => '#3B82F6', 'severity' => 'low'],
            ['code' => 'EXTR', 'name' => 'Extracción indicada',  'description' => 'Pieza con indicación de extracción',      'color' => '#6B7280', 'severity' => 'critical'],
            ['code' => 'AUS', 'name' => 'Ausente',               'description' => 'Pieza dental ausente',                    'color' => '#1F2937', 'severity' => 'medium'],
            ['code' => 'TCR', 'name' => 'Tratamiento de conducto','description' => 'Pieza con tratamiento endodóntico',      'color' => '#7C3AED', 'severity' => 'medium'],
            ['code' => 'IMP', 'name' => 'Implante',              'description' => 'Pieza reemplazada por implante',          'color' => '#0EA5E9', 'severity' => 'low'],
            ['code' => 'PUEN', 'name' => 'Puente',               'description' => 'Pieza incluida en puente protésico',      'color' => '#06B6D4', 'severity' => 'low'],
            ['code' => 'FISU', 'name' => 'Fisura / Grieta',      'description' => 'Fisura o grieta en esmalte',              'color' => '#FB923C', 'severity' => 'medium'],
            ['code' => 'SENS', 'name' => 'Sensibilidad',         'description' => 'Hipersensibilidad dentinaria',            'color' => '#FCD34D', 'severity' => 'low'],
            ['code' => 'REAB', 'name' => 'Reabsorción radicular','description' => 'Reabsorción radicular interna o externa', 'color' => '#E879F9', 'severity' => 'high'],
            ['code' => 'SANO', 'name' => 'Sano',                 'description' => 'Pieza sin alteraciones',                  'color' => '#10B981', 'severity' => 'low'],
        ];

        foreach ($clinicIds as $clinicId) {
            foreach ($entries as $entry) {
                DiagnosisCatalog::firstOrCreate(
                    ['clinic_id' => $clinicId, 'code' => $entry['code']],
                    [...$entry, 'clinic_id' => $clinicId]
                );
            }
        }
    }
}
