<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminderWhatsApp;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature   = 'appointments:send-reminders
                                {--hours=24 : Hours before appointment to send reminder}';
    protected $description = 'Send WhatsApp reminders for upcoming appointments';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $from  = now()->addHours($hours)->startOfHour();
        $to    = now()->addHours($hours)->endOfHour();

        $appointments = Appointment::with(['patient', 'clinic', 'dentist'])
            ->whereBetween('appointment_date', [$from, $to])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNull('reminder_sent_at')
            ->get();

        if ($appointments->isEmpty()) {
            $this->info("No appointments found in window {$from} – {$to}.");
            return self::SUCCESS;
        }

        $sent   = 0;
        $failed = 0;

        foreach ($appointments as $appt) {
            if (! $appt->patient->phone) {
                $this->warn("Skipping #{$appt->id} — patient has no phone.");
                continue;
            }

            try {
                $appt->patient->notify(new AppointmentReminderWhatsApp($appt));
                $appt->update(['reminder_sent_at' => now()]);
                $sent++;
                $this->line("✓ Reminder queued for {$appt->patient->full_name}");
            } catch (\Throwable $e) {
                $failed++;
                $this->error("✗ Failed for appointment #{$appt->id}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Sent: {$sent} · Failed: {$failed}");
        return self::SUCCESS;
    }
}
