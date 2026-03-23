<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoiceReadyWhatsApp extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['whatsapp'];
    }

    /**
     * Template: invoice_ready
     * Body parameters (in order):
     *   {{1}} Patient first name
     *   {{2}} Invoice number    (e.g. INV-00001)
     *   {{3}} Total amount      (e.g. RD$5,000.00)
     *   {{4}} Clinic name
     *
     * Suggested template text:
     * "Hola {{1}}, tu factura {{2}} de {{4}} por un monto de {{3}} ha sido generada.
     *  Gracias por tu preferencia."
     */
    public function toWhatsApp(object $notifiable): array
    {
        $inv    = $this->invoice;
        $amount = 'RD$' . number_format((float) $inv->total, 2);

        return [
            'template' => 'invoice_ready',
            'clinic'   => $inv->clinic,
            'params'   => [
                $notifiable->first_name,
                $inv->invoice_number,
                $amount,
                $inv->clinic->name,
            ],
        ];
    }
}
