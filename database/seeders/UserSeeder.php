<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Clinic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::first();

        // Admin (Clinic Owner)
        User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Dr. Sarah Mitchell',
            'email' => 'admin@dentaris.app',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+1 (555) 100-0001',
            'specialty' => 'General Dentistry',
            'is_active' => true,
        ]);

        // Dentists
        User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Dr. James Rodriguez',
            'email' => 'james@dentaris.app',
            'password' => Hash::make('password'),
            'role' => 'dentist',
            'phone' => '+1 (555) 100-0002',
            'specialty' => 'Orthodontics',
            'is_active' => true,
            'working_hours' => [
                'monday'    => ['start' => '09:00', 'end' => '17:00'],
                'tuesday'   => ['start' => '09:00', 'end' => '17:00'],
                'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                'thursday'  => ['start' => '09:00', 'end' => '17:00'],
                'friday'    => ['start' => '09:00', 'end' => '15:00'],
            ],
        ]);

        User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Dr. Emily Chen',
            'email' => 'emily@dentaris.app',
            'password' => Hash::make('password'),
            'role' => 'dentist',
            'phone' => '+1 (555) 100-0003',
            'specialty' => 'Endodontics & Periodontics',
            'is_active' => true,
            'working_hours' => [
                'tuesday'   => ['start' => '08:00', 'end' => '16:00'],
                'wednesday' => ['start' => '08:00', 'end' => '16:00'],
                'thursday'  => ['start' => '08:00', 'end' => '16:00'],
                'friday'    => ['start' => '08:00', 'end' => '16:00'],
                'saturday'  => ['start' => '09:00', 'end' => '13:00'],
            ],
        ]);

        // Receptionist
        User::create([
            'clinic_id' => $clinic->id,
            'name' => 'Maria Santos',
            'email' => 'receptionist@dentaris.app',
            'password' => Hash::make('password'),
            'role' => 'receptionist',
            'phone' => '+1 (555) 100-0004',
            'is_active' => true,
        ]);
    }
}
