<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\DentalRecord;
use App\Models\Patient;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $clinic    = Clinic::first();
        $dentists  = User::where('clinic_id', $clinic->id)->where('role', '!=', 'receptionist')->get();
        $patients  = Patient::where('clinic_id', $clinic->id)->get();
        $treatments = Treatment::where('clinic_id', $clinic->id)->get();

        $statuses = ['scheduled', 'confirmed', 'completed', 'completed', 'completed', 'cancelled'];
        $reasons  = [
            'Routine checkup', 'Teeth cleaning', 'Cavity filling', 'Crown placement',
            'Root canal treatment', 'Teeth whitening', 'Orthodontic consultation',
            'Extraction', 'Dental implant consult', 'Gum treatment', 'Emergency pain',
        ];

        $toothConditions = ['healthy', 'cavity', 'crown', 'filling', 'root_canal', 'missing'];
        $count = 0;

        // Generate appointments across past 3 months + next month
        for ($daysBack = 90; $daysBack >= -30; $daysBack--) {
            $date = Carbon::now()->subDays($daysBack);

            // Skip Sundays
            if ($date->dayOfWeek === 0) continue;

            // 3-6 appointments per day
            $daily = rand(3, 6);
            $hours = collect([9, 10, 11, 13, 14, 15, 16])->shuffle()->take($daily);

            foreach ($hours as $hour) {
                $patient  = $patients->random();
                $dentist  = $dentists->random();
                $status   = $daysBack > 0 ? collect(['completed', 'completed', 'cancelled'])->random()
                                          : collect(['scheduled', 'confirmed'])->random();

                $treatment = $treatments->random();
                $apptDate  = $date->copy()->setHour($hour)->setMinute(0);

                $appointment = Appointment::create([
                    'clinic_id'        => $clinic->id,
                    'patient_id'       => $patient->id,
                    'dentist_id'       => $dentist->id,
                    'created_by'       => $dentist->id,
                    'appointment_date' => $apptDate,
                    'duration_minutes' => $treatment->duration_minutes,
                    'status'           => $status,
                    'reason'           => collect($reasons)->random(),
                    'total_cost'       => $treatment->default_price,
                ]);

                $appointment->treatments()->attach($treatment->id, [
                    'price'    => $treatment->default_price,
                    'quantity' => 1,
                ]);

                // Add dental records for completed appointments
                if ($status === 'completed') {
                    $toothNum = rand(1, 32);
                    DentalRecord::create([
                        'clinic_id'      => $clinic->id,
                        'patient_id'     => $patient->id,
                        'appointment_id' => $appointment->id,
                        'dentist_id'     => $dentist->id,
                        'tooth_number'   => $toothNum,
                        'condition'      => collect($toothConditions)->random(),
                        'notes'          => 'Treatment performed successfully.',
                    ]);
                }

                $count++;
                if ($count >= 50) break 2;
            }
        }
    }
}
