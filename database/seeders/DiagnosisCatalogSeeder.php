<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DiagnosisCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Los diagnósticos ahora se crean en Clinic::seedDefaultCatalog(),
        // llamado desde TreatmentSeeder y al registrar cada clínica nueva.
    }
}
