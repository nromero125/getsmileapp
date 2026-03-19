<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use Illuminate\Database\Seeder;

class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::first();

        $categories = [
            ['name' => 'Preventive', 'color' => '#10B981'],
            ['name' => 'Restorative', 'color' => '#3B82F6'],
            ['name' => 'Orthodontics', 'color' => '#8B5CF6'],
            ['name' => 'Oral Surgery', 'color' => '#EF4444'],
            ['name' => 'Endodontics', 'color' => '#F59E0B'],
            ['name' => 'Cosmetic', 'color' => '#EC4899'],
            ['name' => 'Periodontics', 'color' => '#06B6D4'],
        ];

        foreach ($categories as $cat) {
            TreatmentCategory::create(['clinic_id' => $clinic->id, ...$cat]);
        }

        $treatments = [
            // Preventive
            ['category' => 'Preventive', 'name' => 'Dental Cleaning',        'price' => 120,  'duration' => 60],
            ['category' => 'Preventive', 'name' => 'Dental Exam',            'price' => 80,   'duration' => 30],
            ['category' => 'Preventive', 'name' => 'Dental X-Ray (Full)',    'price' => 150,  'duration' => 30],
            ['category' => 'Preventive', 'name' => 'Fluoride Treatment',     'price' => 45,   'duration' => 15],
            ['category' => 'Preventive', 'name' => 'Sealants (per tooth)',   'price' => 60,   'duration' => 15],
            // Restorative
            ['category' => 'Restorative', 'name' => 'Composite Filling',    'price' => 180,  'duration' => 45],
            ['category' => 'Restorative', 'name' => 'Amalgam Filling',      'price' => 120,  'duration' => 45],
            ['category' => 'Restorative', 'name' => 'Dental Crown (PFM)',   'price' => 1100, 'duration' => 90],
            ['category' => 'Restorative', 'name' => 'Dental Crown (Zirconia)', 'price' => 1400, 'duration' => 90],
            ['category' => 'Restorative', 'name' => 'Dental Bridge (3-unit)', 'price' => 2800, 'duration' => 120],
            ['category' => 'Restorative', 'name' => 'Dentures (Complete)',  'price' => 1800, 'duration' => 90],
            // Orthodontics
            ['category' => 'Orthodontics', 'name' => 'Traditional Braces',  'price' => 5500, 'duration' => 60],
            ['category' => 'Orthodontics', 'name' => 'Clear Aligners',      'price' => 6500, 'duration' => 60],
            ['category' => 'Orthodontics', 'name' => 'Retainer',            'price' => 400,  'duration' => 30],
            // Oral Surgery
            ['category' => 'Oral Surgery', 'name' => 'Simple Extraction',   'price' => 200,  'duration' => 30],
            ['category' => 'Oral Surgery', 'name' => 'Wisdom Tooth Removal', 'price' => 400, 'duration' => 60],
            ['category' => 'Oral Surgery', 'name' => 'Dental Implant',      'price' => 3500, 'duration' => 90],
            // Endodontics
            ['category' => 'Endodontics', 'name' => 'Root Canal (1 canal)', 'price' => 700,  'duration' => 90],
            ['category' => 'Endodontics', 'name' => 'Root Canal (3 canals)', 'price' => 1100, 'duration' => 120],
            // Cosmetic
            ['category' => 'Cosmetic', 'name' => 'Teeth Whitening (In-office)', 'price' => 450, 'duration' => 90],
            ['category' => 'Cosmetic', 'name' => 'Porcelain Veneer',        'price' => 1200, 'duration' => 90],
            ['category' => 'Cosmetic', 'name' => 'Smile Makeover',          'price' => 4500, 'duration' => 120],
            // Periodontics
            ['category' => 'Periodontics', 'name' => 'Deep Cleaning (SRP)', 'price' => 350,  'duration' => 60],
            ['category' => 'Periodontics', 'name' => 'Gum Surgery',         'price' => 1200, 'duration' => 90],
        ];

        foreach ($treatments as $t) {
            $cat = TreatmentCategory::where('clinic_id', $clinic->id)->where('name', $t['category'])->first();
            Treatment::create([
                'clinic_id' => $clinic->id,
                'treatment_category_id' => $cat->id,
                'name' => $t['name'],
                'default_price' => $t['price'],
                'duration_minutes' => $t['duration'],
                'is_active' => true,
            ]);
        }
    }
}
