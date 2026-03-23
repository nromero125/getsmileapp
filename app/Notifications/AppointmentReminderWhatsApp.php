<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AppointmentReminderWhatsApp extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['whatsapp'];
    }

    /**
     * Template: appointment_reminder
     * Body parameters (in order):
     *   {{1}} Patient first name
     *   {{2}} Appointment date+time  (e.g. "mañana lunes 23 de marzo a las 10:00 AM")
     *   {{3}} Clinic name
     *   {{4}} Clinic phone
     *
     * Suggested template text:
     * "Hola {{1}}, te recordamos que tienes una cita en {{3}} {{2}}.
     *  Si necesitas reprogramar llámanos al {{4}}. ¡Te esperamos!"
     */
    public function toWhatsApp(object $notifiable): array
    {
        $appt      = $this->appointment;
        $date      = $appt->appointment_date->locale('es')->isoFormat('dddd D [de] MMMM [a las] h:mm A');
        $hoursAway = now()->diffInHours($appt->appointment_date);
        $when      = $hoursAway <= 24 ? "mañana {$date}" : $date;

        return [
            'template' => 'appointment_reminder',
            'params'   => [
                $notifiable->first_name,
                $when,
                $appt->clinic->name,
                $appt->clinic->phone ?? '',
            ],
        ];
    }
}
