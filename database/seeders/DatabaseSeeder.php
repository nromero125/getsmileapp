<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ClinicSeeder::class,
            UserSeeder::class,
            TreatmentSeeder::class,
            DiagnosisCatalogSeeder::class,
            PatientSeeder::class,
            AppointmentSeeder::class,
            InvoiceSeeder::class,
        ]);
    }
}
