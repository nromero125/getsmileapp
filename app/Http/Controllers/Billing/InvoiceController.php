<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceInstallment;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $query = Invoice::where('clinic_id', $clinicId)->with(['patient', 'appointment']);

        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', "%{$request->search}%")
                  ->orWhereHas('patient', fn($q2) => $q2->where('first_name', 'like', "%{$request->search}%")
                      ->orWhere('last_name', 'like', "%{$request->search}%"));
            });
        }

        $invoices = $query->orderByDesc('invoice_date')->paginate(15)->withQueryString();

        return Inertia::render('Billing/Index', [
            'invoices' => $invoices,
            'filters'  => $request->only(['status', 'search']),
            'stats'    => [
                'total_revenue' => (float) Invoice::where('clinic_id', $clinicId)->sum('amount_paid'),
                'pending'       => (float) Invoice::where('clinic_id', $clinicId)->whereIn('status', ['pending','partial'])->sum(\DB::raw('total - amount_paid')),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $patients  = Patient::where('clinic_id', $clinicId)->get(['id','first_name','last_name']);
        $treatments = Treatment::where('clinic_id', $clinicId)->where('is_active', true)->get(['id','name','default_price']);

        return Inertia::render('Billing/Create', [
            'patients'   => $patients,
            'treatments' => $treatments,
            'patient_id' => $request->patient_id,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'  => 'required|exists:patients,id',
            'invoice_date'=> 'required|date',
            'due_date'    => 'nullable|date',
            'notes'       => 'nullable|string',
            'items'       => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.treatment_id'=> 'nullable|exists:treatments,id',
            'discount_percent'    => 'nullable|numeric|min:0|max:100',
            'tax_percent'         => 'nullable|numeric|min:0|max:100',
        ]);

        $clinicId  = auth()->user()->clinic_id;
        $subtotal  = 0;

        foreach ($validated['items'] as $item) {
            $subtotal += $item['unit_price'] * $item['quantity'];
        }

        $discountPct = $validated['discount_percent'] ?? 0;
        $taxPct      = $validated['tax_percent'] ?? 0;
        $discountAmt = round($subtotal * $discountPct / 100, 2);
        $taxAmt      = round(($subtotal - $discountAmt) * $taxPct / 100, 2);
        $total       = $subtotal - $discountAmt + $taxAmt;

        $invoice = Invoice::create([
            'clinic_id'       => $clinicId,
            'patient_id'      => $validated['patient_id'],
            'invoice_number'  => Invoice::generateNumber($clinicId),
            'invoice_date'    => $validated['invoice_date'],
            'due_date'        => $validated['due_date'] ?? null,
            'status'          => 'pending',
            'subtotal'        => $subtotal,
            'discount_percent'=> $discountPct,
            'discount_amount' => $discountAmt,
            'tax_percent'     => $taxPct,
            'tax_amount'      => $taxAmt,
            'total'           => $total,
            'amount_paid'     => 0,
            'notes'           => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $itemTotal = ($item['unit_price'] * $item['quantity']) - ($item['discount'] ?? 0);
            InvoiceItem::create([
                'invoice_id'   => $invoice->id,
                'treatment_id' => $item['treatment_id'] ?? null,
                'description'  => $item['description'],
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'discount'     => $item['discount'] ?? 0,
                'total'        => $itemTotal,
            ]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeClinic($invoice);
        $invoice->load(['patient', 'items.treatment', 'payments', 'appointment', 'installments']);

        return Inertia::render('Billing/Show', ['invoice' => $invoice]);
    }

    public function receivables(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $today    = now()->toDateString();

        $invoices = Invoice::where('clinic_id', $clinicId)
            ->whereIn('status', ['pending', 'partial'])
            ->with('patient')
            ->orderBy('due_date')
            ->get()
            ->map(function ($inv) use ($today) {
                $balance  = $inv->total - $inv->amount_paid;
                $daysLate = $inv->due_date ? now()->diffInDays($inv->due_date, false) * -1 : null;
                $bucket   = 'current';
                if ($daysLate !== null) {
                    if ($daysLate > 90)      $bucket = '90+';
                    elseif ($daysLate > 60)  $bucket = '61-90';
                    elseif ($daysLate > 30)  $bucket = '31-60';
                    elseif ($daysLate > 0)   $bucket = '1-30';
                }
                return array_merge($inv->toArray(), [
                    'balance_due' => $balance,
                    'days_late'   => $daysLate,
                    'bucket'      => $bucket,
                ]);
            });

        $buckets = [
            'current' => $invoices->where('bucket', 'current'),
            '1-30'    => $invoices->where('bucket', '1-30'),
            '31-60'   => $invoices->where('bucket', '31-60'),
            '61-90'   => $invoices->where('bucket', '61-90'),
            '90+'     => $invoices->where('bucket', '90+'),
        ];

        return Inertia::render('Billing/Receivables', [
            'invoices' => $invoices->values(),
            'summary'  => [
                'total'   => (float) $invoices->sum('balance_due'),
                'current' => (float) $buckets['current']->sum('balance_due'),
                '1-30'    => (float) $buckets['1-30']->sum('balance_due'),
                '31-60'   => (float) $buckets['31-60']->sum('balance_due'),
                '61-90'   => (float) $buckets['61-90']->sum('balance_due'),
                '90+'     => (float) $buckets['90+']->sum('balance_due'),
            ],
        ]);
    }

    public function storeInstallmentPlan(Request $request, Invoice $invoice)
    {
        $this->authorizeClinic($invoice);

        $validated = $request->validate([
            'installments_count' => 'required|integer|min:2|max:60',
            'first_due_date'     => 'required|date',
            'frequency'          => 'required|in:weekly,biweekly,monthly',
        ]);

        $invoice->installments()->delete();

        $balance    = $invoice->total - $invoice->amount_paid;
        $count      = $validated['installments_count'];
        $perInstall = round($balance / $count, 2);
        $date       = Carbon::parse($validated['first_due_date']);

        for ($i = 1; $i <= $count; $i++) {
            $amount = ($i === $count)
                ? $balance - ($perInstall * ($count - 1)) // last covers rounding
                : $perInstall;

            InvoiceInstallment::create([
                'invoice_id'          => $invoice->id,
                'installment_number'  => $i,
                'due_date'            => $date->toDateString(),
                'amount'              => $amount,
            ]);

            match ($validated['frequency']) {
                'weekly'    => $date->addWeek(),
                'biweekly'  => $date->addWeeks(2),
                'monthly'   => $date->addMonth(),
            };
        }

        return back()->with('success', 'Plan de pagos creado.');
    }

    public function payInstallment(Request $request, Invoice $invoice, InvoiceInstallment $installment)
    {
        $this->authorizeClinic($invoice);
        abort_if($installment->invoice_id !== $invoice->id, 403);

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'method'       => 'required|in:cash,card,transfer,insurance,other',
            'reference'    => 'nullable|string',
        ]);

        $amount = $installment->amount - $installment->amount_paid;

        Payment::create([
            'clinic_id'    => $invoice->clinic_id,
            'invoice_id'   => $invoice->id,
            'amount'       => $amount,
            'payment_date' => $validated['payment_date'],
            'method'       => $validated['method'],
            'reference'    => $validated['reference'] ?? null,
            'notes'        => "Cuota #{$installment->installment_number}",
        ]);

        $installment->update(['amount_paid' => $installment->amount, 'paid_at' => now()]);

        $totalPaid = $invoice->payments()->sum('amount') + $amount;
        $invoice->update([
            'amount_paid' => min($totalPaid, $invoice->total),
            'status'      => $totalPaid >= $invoice->total ? 'paid' : 'partial',
        ]);

        return back()->with('success', "Cuota #{$installment->installment_number} pagada.");
    }

    public function destroyInstallmentPlan(Invoice $invoice)
    {
        $this->authorizeClinic($invoice);
        $invoice->installments()->delete();
        return back()->with('success', 'Plan de pagos eliminado.');
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $this->authorizeClinic($invoice);

        $validated = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'method'       => 'required|in:cash,card,transfer,insurance,other',
            'reference'    => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        Payment::create([
            'clinic_id'    => $invoice->clinic_id,
            'invoice_id'   => $invoice->id,
            ...$validated,
        ]);

        $totalPaid = $invoice->payments()->sum('amount') + $validated['amount'];
        $status = $totalPaid >= $invoice->total ? 'paid' : 'partial';

        $invoice->update([
            'amount_paid' => min($totalPaid, $invoice->total),
            'status'      => $status,
        ]);

        return back()->with('success', 'Payment recorded.');
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorizeClinic($invoice);
        $invoice->load(['patient', 'items.treatment', 'payments']);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice, 'clinic' => $invoice->clinic]);
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    private function authorizeClinic(Invoice $invoice): void
    {
        if ($invoice->clinic_id !== auth()->user()->clinic_id) {
            abort(403);
        }
    }
}
