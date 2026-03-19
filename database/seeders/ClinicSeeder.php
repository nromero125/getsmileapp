<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        Clinic::create([
            'name' => 'Smile Dental Clinic',
            'slug' => 'smile-dental-clinic',
            'email' => 'info@smiledental.com',
            'phone' => '+1 (555) 234-5678',
            'address' => '123 Dental Plaza, Suite 400, New York, NY 10001',
            'website' => 'https://smiledental.com',
            'tax_id' => '12-3456789',
            'is_active' => true,
            'settings' => [
                'currency' => 'USD',
                'timezone' => 'America/New_York',
                'appointment_slot_minutes' => 30,
                'working_hours' => [
                    'monday'    => ['start' => '08:00', 'end' => '18:00'],
                    'tuesday'   => ['start' => '08:00', 'end' => '18:00'],
                    'wednesday' => ['start' => '08:00', 'end' => '18:00'],
                    'thursday'  => ['start' => '08:00', 'end' => '18:00'],
                    'friday'    => ['start' => '08:00', 'end' => '17:00'],
                    'saturday'  => ['start' => '09:00', 'end' => '14:00'],
                    'sunday'    => null,
                ],
            ],
        ]);
    }
}
