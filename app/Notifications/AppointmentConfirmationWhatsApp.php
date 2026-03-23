<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AppointmentConfirmationWhatsApp extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['whatsapp'];
    }

    /**
     * Template: appointment_confirmation
     * Body parameters (in order):
     *   {{1}} Patient first name
     *   {{2}} Appointment date  (e.g. "lunes 23 de marzo")
     *   {{3}} Appointment time  (e.g. "10:00 AM")
     *   {{4}} Clinic name
     *   {{5}} Confirmation URL
     *
     * Suggested template text (create this in Meta Business Manager):
     * "Hola {{1}}, te confirmamos tu cita en {{4}} para el {{2}} a las {{3}}.
     *  Confirma tu asistencia aquí: {{5}}
     *  Si necesitas cancelar o reprogramar, comunícate con nosotros."
     */
    public function toWhatsApp(object $notifiable): array
    {
        $appt  = $this->appointment;
        $token = $appt->confirmation_token;
        $url   = route('appointments.confirm', $token);
        $date  = $appt->appointment_date->locale('es')->isoFormat('dddd D [de] MMMM');
        $time  = $appt->appointment_date->format('h:i A');

        return [
            'template' => 'appointment_confirmation',
            'params'   => [
                $notifiable->first_name,
                $date,
                $time,
                $appt->clinic->name,
                $url,
            ],
        ];
    }
}
