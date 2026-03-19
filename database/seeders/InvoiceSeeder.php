<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::first();
        $completedAppts = Appointment::where('clinic_id', $clinic->id)
            ->where('status', 'completed')
            ->with('treatments')
            ->get();

        $invoiceNum = 1;

        foreach ($completedAppts->take(30) as $appt) {
            $status = collect(['paid', 'paid', 'paid', 'pending', 'partial'])->random();
            $subtotal = $appt->treatments->sum(fn($t) => $t->pivot->price * $t->pivot->quantity);
            $discount = $status === 'paid' && rand(0,1) ? round($subtotal * 0.1, 2) : 0;
            $total = $subtotal - $discount;

            $invoice = Invoice::create([
                'clinic_id'       => $clinic->id,
                'patient_id'      => $appt->patient_id,
                'appointment_id'  => $appt->id,
                'invoice_number'  => 'INV-' . str_pad($invoiceNum++, 5, '0', STR_PAD_LEFT),
                'invoice_date'    => $appt->appointment_date->toDateString(),
                'due_date'        => $appt->appointment_date->addDays(30)->toDateString(),
                'status'          => $status,
                'subtotal'        => $subtotal,
                'discount_amount' => $discount,
                'total'           => $total,
                'amount_paid'     => match($status) {
                    'paid'    => $total,
                    'partial' => round($total * 0.5, 2),
                    default   => 0,
                },
            ]);

            foreach ($appt->treatments as $treatment) {
                InvoiceItem::create([
                    'invoice_id'   => $invoice->id,
                    'treatment_id' => $treatment->id,
                    'description'  => $treatment->name,
                    'quantity'     => $treatment->pivot->quantity,
                    'unit_price'   => $treatment->pivot->price,
                    'discount'     => 0,
                    'total'        => $treatment->pivot->price * $treatment->pivot->quantity,
                ]);
            }

            if (in_array($status, ['paid', 'partial'])) {
                Payment::create([
                    'clinic_id'    => $clinic->id,
                    'invoice_id'   => $invoice->id,
                    'amount'       => $invoice->amount_paid,
                    'payment_date' => $appt->appointment_date->addDays(rand(0, 7))->toDateString(),
                    'method'       => collect(['cash', 'card', 'transfer', 'insurance'])->random(),
                ]);
            }
        }
    }
}
