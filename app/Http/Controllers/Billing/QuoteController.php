<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $query = Quote::where('clinic_id', $clinicId)->with('patient');

        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('quote_number', 'like', "%{$request->search}%")
                  ->orWhereHas('patient', fn($q2) => $q2->where('first_name', 'like', "%{$request->search}%")
                      ->orWhere('last_name', 'like', "%{$request->search}%"));
            });
        }

        $quotes = $query->orderByDesc('quote_date')->paginate(15)->withQueryString();

        return Inertia::render('Billing/Quotes/Index', [
            'quotes'  => $quotes,
            'filters' => $request->only(['status', 'search']),
            'stats'   => [
                'total'    => (float) Quote::where('clinic_id', $clinicId)->whereIn('status', ['draft','sent','accepted'])->sum('total'),
                'accepted' => (float) Quote::where('clinic_id', $clinicId)->where('status', 'accepted')->sum('total'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $clinicId  = auth()->user()->clinic_id;
        $patients  = Patient::where('clinic_id', $clinicId)->get(['id','first_name','last_name']);
        $treatments = Treatment::where('clinic_id', $clinicId)->where('is_active', true)->get(['id','name','default_price']);

        return Inertia::render('Billing/Quotes/Create', [
            'patients'   => $patients,
            'treatments' => $treatments,
            'patient_id' => $request->patient_id,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'      => 'required|exists:patients,id',
            'quote_date'      => 'required|date',
            'valid_until'     => 'nullable|date',
            'notes'           => 'nullable|string',
            'discount_percent'=> 'nullable|numeric|min:0|max:100',
            'tax_percent'     => 'nullable|numeric|min:0|max:100',
            'items'           => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:1',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.treatment_id'=> 'nullable|exists:treatments,id',
        ]);

        $clinicId    = auth()->user()->clinic_id;
        $subtotal    = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $validated['items']));
        $discountPct = $validated['discount_percent'] ?? 0;
        $taxPct      = $validated['tax_percent'] ?? 0;
        $discountAmt = round($subtotal * $discountPct / 100, 2);
        $taxAmt      = round(($subtotal - $discountAmt) * $taxPct / 100, 2);
        $total       = $subtotal - $discountAmt + $taxAmt;

        $quote = Quote::create([
            'clinic_id'       => $clinicId,
            'patient_id'      => $validated['patient_id'],
            'quote_number'    => Quote::generateNumber($clinicId),
            'quote_date'      => $validated['quote_date'],
            'valid_until'     => $validated['valid_until'] ?? null,
            'status'          => 'draft',
            'subtotal'        => $subtotal,
            'discount_percent'=> $discountPct,
            'discount_amount' => $discountAmt,
            'tax_percent'     => $taxPct,
            'tax_amount'      => $taxAmt,
            'total'           => $total,
            'notes'           => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            QuoteItem::create([
                'quote_id'     => $quote->id,
                'treatment_id' => $item['treatment_id'] ?? null,
                'description'  => $item['description'],
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'discount'     => $item['discount'] ?? 0,
                'total'        => ($item['unit_price'] * $item['quantity']) - ($item['discount'] ?? 0),
            ]);
        }

        return redirect()->route('quotes.show', $quote)->with('success', 'Cotización creada.');
    }

    public function show(Quote $quote)
    {
        $this->authorizeClinic($quote);
        $quote->load(['patient', 'items.treatment', 'invoice']);

        return Inertia::render('Billing/Quotes/Show', ['quote' => $quote]);
    }

    public function updateStatus(Request $request, Quote $quote)
    {
        $this->authorizeClinic($quote);

        $validated = $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
        ]);

        $quote->update($validated);

        return back()->with('success', 'Estado actualizado.');
    }

    public function convertToInvoice(Quote $quote)
    {
        $this->authorizeClinic($quote);

        if ($quote->converted_to_invoice_id) {
            return redirect()->route('invoices.show', $quote->converted_to_invoice_id);
        }

        $clinicId = auth()->user()->clinic_id;

        $invoice = Invoice::create([
            'clinic_id'        => $clinicId,
            'patient_id'       => $quote->patient_id,
            'invoice_number'   => Invoice::generateNumber($clinicId),
            'invoice_date'     => now()->toDateString(),
            'status'           => 'pending',
            'subtotal'         => $quote->subtotal,
            'discount_percent' => $quote->discount_percent,
            'discount_amount'  => $quote->discount_amount,
            'tax_percent'      => $quote->tax_percent,
            'tax_amount'       => $quote->tax_amount,
            'total'            => $quote->total,
            'amount_paid'      => 0,
            'notes'            => $quote->notes,
        ]);

        foreach ($quote->items as $item) {
            InvoiceItem::create([
                'invoice_id'   => $invoice->id,
                'treatment_id' => $item->treatment_id,
                'description'  => $item->description,
                'quantity'     => $item->quantity,
                'unit_price'   => $item->unit_price,
                'discount'     => $item->discount,
                'total'        => $item->total,
            ]);
        }

        $quote->update(['status' => 'accepted', 'converted_to_invoice_id' => $invoice->id]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Cotización convertida a factura.');
    }

    public function destroy(Quote $quote)
    {
        $this->authorizeClinic($quote);
        $quote->delete();
        return redirect()->route('quotes.index')->with('success', 'Cotización eliminada.');
    }

    private function authorizeClinic(Quote $quote): void
    {
        abort_if($quote->clinic_id !== auth()->user()->clinic_id, 403);
    }
}
