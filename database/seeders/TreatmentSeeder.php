<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Seeder;

class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        Clinic::all()->each(fn($clinic) => $clinic->seedDefaultCatalog());
    }
}
