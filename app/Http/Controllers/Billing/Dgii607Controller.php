<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class Dgii607Controller extends Controller
{
    public function index()
    {
        return Inertia::render('Billing/Dgii607', [
            'clinic' => auth()->user()->clinic,
        ]);
    }

    public function download(Request $request)
    {
        $request->validate([
            'year'  => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $clinic = auth()->user()->clinic;
        $year   = (int) $request->year;
        $month  = (int) $request->month;

        [$lines, $errors] = $this->buildLines($clinic, $year, $month);

        if (count($errors) > 0) {
            return response()->json([
                'error' => implode(' · ', array_slice($errors, 0, 5)),
            ], 422);
        }

        if (count($lines) === 0) {
            return response()->json([
                'error' => 'No hay comprobantes fiscales con NCF para el período seleccionado.',
            ], 422);
        }

        $rnc      = preg_replace('/\D/', '', $clinic->tax_id ?? '');
        $period   = sprintf('%04d%02d', $year, $month);
        $filename = "607{$rnc}{$period}.txt";
        $content  = implode("\r\n", $lines) . "\r\n";

        return response($content, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Core builder ─────────────────────────────────────────────────────────

    private function buildLines(object $clinic, int $year, int $month): array
    {
        $errors = [];
        $lines  = [];

        // Non-draft invoices with NCF issued in this period
        $invoices = Invoice::where('clinic_id', $clinic->id)
            ->whereNotNull('ncf')
            ->where('status', '!=', 'draft')
            ->whereYear('invoice_date', $year)
            ->whereMonth('invoice_date', $month)
            ->with([
                'patient:id,client_document',
                'payments' => fn($q) => $q->select('invoice_id', 'amount', 'method'),
            ])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        foreach ($invoices as $inv) {
            $err = $this->validateInvoice($inv);
            if ($err) { $errors[] = $err; continue; }

            $clientDoc  = preg_replace('/\D/', '', $inv->patient->client_document);
            $clientType = $this->detectClientType($inv->patient->client_document);
            $ncf        = str_replace('-', '', $inv->ncf);
            $date       = $inv->invoice_date->format('Ymd');

            // ── Original invoice line (B01 / B02) ──────────────────────────
            if (in_array($inv->ncf_type, ['B01', 'B02'])) {
                $pay    = $this->paymentBreakdown($inv);
                $status = $inv->status === 'voided' ? 0 : 1;

                $lines[] = $this->formatLine(
                    clientDoc:   $clientDoc,
                    clientType:  $clientType,
                    ncf:         $ncf,
                    modifiedNcf: '',
                    date:        $date,
                    amount:      $this->fmt($inv->total),
                    itbis:       $this->fmt($inv->tax_amount),
                    pay:         $pay,
                    status:      $status,
                );
            }

            // ── B04 cancellation line (same period as invoice) ──────────────
            if ($inv->status === 'voided' && ! empty($inv->ncf_void)) {
                $voidYear  = (int) Carbon::parse($inv->voided_at ?? $inv->invoice_date)->format('Y');
                $voidMonth = (int) Carbon::parse($inv->voided_at ?? $inv->invoice_date)->format('m');

                if ($voidYear === $year && $voidMonth === $month) {
                    $voidDate = $inv->voided_at
                        ? Carbon::parse($inv->voided_at)->format('Ymd')
                        : $date;

                    $lines[] = $this->formatLine(
                        clientDoc:   $clientDoc,
                        clientType:  $clientType,
                        ncf:         str_replace('-', '', $inv->ncf_void),
                        modifiedNcf: $ncf,
                        date:        $voidDate,
                        amount:      $this->fmt(-(float) $inv->total),
                        itbis:       $this->fmt(-(float) $inv->tax_amount),
                        pay:         $this->zeroPay(),
                        status:      0,
                    );
                }
            }
        }

        // B04s for invoices voided THIS period but issued in a prior period
        $voidedPrior = Invoice::where('clinic_id', $clinic->id)
            ->whereNotNull('ncf_void')
            ->where('status', 'voided')
            ->whereYear('voided_at', $year)
            ->whereMonth('voided_at', $month)
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('invoice_date', '!=', $year)
                  ->orWhereMonth('invoice_date', '!=', $month);
            })
            ->with('patient:id,client_document')
            ->orderBy('voided_at')
            ->orderBy('id')
            ->get();

        foreach ($voidedPrior as $inv) {
            $document = trim($inv->patient->client_document ?? '');
            if (empty($document) || empty($inv->ncf_void)) continue;

            $lines[] = $this->formatLine(
                clientDoc:   preg_replace('/\D/', '', $document),
                clientType:  $this->detectClientType($document),
                ncf:         str_replace('-', '', $inv->ncf_void),
                modifiedNcf: str_replace('-', '', $inv->ncf),
                date:        Carbon::parse($inv->voided_at)->format('Ymd'),
                amount:      $this->fmt(-(float) $inv->total),
                itbis:       $this->fmt(-(float) $inv->tax_amount),
                pay:         $this->zeroPay(),
                status:      0,
            );
        }

        return [$lines, $errors];
    }

    // ── Line formatter — 24 campos DGII 607 ──────────────────────────────────

    private function formatLine(
        string $clientDoc,
        int    $clientType,
        string $ncf,
        string $modifiedNcf,
        string $date,
        string $amount,
        string $itbis,
        array  $pay,
        int    $status,
    ): string {
        return implode('|', [
            $clientDoc,       //  1  RNC / Cédula / Pasaporte
            $clientType,      //  2  Tipo identificación
            $ncf,             //  3  NCF (sin guión)
            $modifiedNcf,     //  4  NCF modificado
            '01',             //  5  Tipo de ingreso
            $date,            //  6  Fecha comprobante (YYYYMMDD)
            '',               //  7  Fecha retención
            $amount,          //  8  Monto facturado
            $itbis,           //  9  ITBIS facturado
            '0.00',           // 10  ITBIS retenido por terceros
            '0.00',           // 11  ITBIS percibido
            '0.00',           // 12  Retención renta por terceros
            '0.00',           // 13  ISR percibido
            '0.00',           // 14  Impuesto Selectivo al Consumo (ISC)
            '0.00',           // 15  Otros impuestos / tasas
            '0.00',           // 16  Propina legal
            $pay['cash'],     // 17  Efectivo
            $pay['transfer'], // 18  Cheque / Transferencia / Depósito
            $pay['card'],     // 19  Tarjeta débito / crédito
            $pay['credit'],   // 20  Venta a crédito
            '0.00',           // 21  Bonos / certificados de regalo
            '0.00',           // 22  Permuta
            $pay['other'],    // 23  Otras formas de venta
            $status,          // 24  Estatus (1=válido, 0=anulado)
        ]);
    }

    // ── Payment breakdown ─────────────────────────────────────────────────────

    /**
     * Returns amounts per payment method for the 607.
     * Positive payments only (negative = refunds, already handled via B04).
     * Any unpaid balance → Venta a Crédito (field 20).
     */
    private function paymentBreakdown(Invoice $invoice): array
    {
        $pay = $this->zeroPay();

        foreach ($invoice->payments as $payment) {
            $amount = (float) $payment->amount;
            if ($amount <= 0) continue; // skip refund entries

            match ($payment->method) {
                'cash'     => $pay['cash']     += $amount,
                'transfer' => $pay['transfer'] += $amount,
                'card'     => $pay['card']     += $amount,
                'insurance'=> $pay['credit']   += $amount,
                default    => $pay['other']    += $amount,
            };
        }

        // Unpaid balance → Venta a Crédito
        $totalPaid = $pay['cash'] + $pay['transfer'] + $pay['card'] + $pay['credit'] + $pay['other'];
        $remaining = (float) $invoice->total - $totalPaid;
        if ($remaining > 0.001) {
            $pay['credit'] += $remaining;
        }

        // Format all values
        return array_map(fn($v) => $this->fmt($v), $pay);
    }

    private function zeroPay(): array
    {
        return [
            'cash'     => '0.00',
            'transfer' => '0.00',
            'card'     => '0.00',
            'credit'   => '0.00',
            'other'    => '0.00',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateInvoice(Invoice $inv): ?string
    {
        $document = trim($inv->patient->client_document ?? '');
        if (empty($document)) {
            return "Factura {$inv->invoice_number}: paciente sin cédula/RNC.";
        }
        if (empty($inv->ncf)) {
            return "Factura {$inv->invoice_number}: sin NCF.";
        }
        if (! $inv->invoice_date) {
            return "Factura {$inv->invoice_number}: fecha inválida.";
        }
        return null;
    }

    private function fmt(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /**
     * 11 digits → Cédula (1) · 9 digits → RNC (2) · other → Extranjero (3)
     */
    private function detectClientType(string $document): int
    {
        $digits = preg_replace('/\D/', '', $document);
        return match (strlen($digits)) {
            11 => 1,
            9  => 2,
            default => 3,
        };
    }
}
