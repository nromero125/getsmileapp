<?php

namespace App\Imports;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InvoicesImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped  = 0;
    public array $errors = [];

    private array $patientCache  = [];
    /** @var array<string, Invoice> */
    private array $invoiceBuffer = [];

    public function __construct(private int $clinicId) {}

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2;

            // Resolve patient
            $patientRef = trim($row['email_paciente'] ?? $row['patient_email'] ?? $row['cedula_paciente'] ?? $row['patient_document'] ?? '');
            $patientId  = $this->resolvePatient($patientRef);

            if (! $patientId) {
                $this->errors[] = "Fila {$rowNum}: paciente '{$patientRef}' no encontrado.";
                $this->skipped++;
                continue;
            }

            // Invoice identifier — if blank, auto-generate one per row
            $invoiceRef = trim($row['numero_factura'] ?? $row['invoice_number'] ?? '');

            // Parse date
            try {
                $invoiceDate = Carbon::parse($row['fecha_factura'] ?? $row['invoice_date'] ?? now())->toDateString();
                $dueDate     = isset($row['fecha_vencimiento']) && $row['fecha_vencimiento']
                    ? Carbon::parse($row['fecha_vencimiento'] ?? $row['due_date'])->toDateString()
                    : null;
            } catch (\Throwable) {
                $this->errors[] = "Fila {$rowNum}: fecha inválida.";
                $this->skipped++;
                continue;
            }

            $itemDesc  = trim($row['descripcion_item'] ?? $row['item_description'] ?? 'Servicio');
            $itemPrice = (float) ($row['precio_item'] ?? $row['item_price'] ?? 0);
            $itemQty   = max(1, (int) ($row['cantidad'] ?? $row['quantity'] ?? 1));
            $taxPct    = (float) ($row['itbis_pct'] ?? $row['tax_percent'] ?? 0);
            $amtPaid   = (float) ($row['monto_pagado'] ?? $row['amount_paid'] ?? 0);

            // Buffer rows by invoice_number to group items
            $key = $invoiceRef ?: "row_{$rowNum}_{$patientId}_{$invoiceDate}";

            if (! isset($this->invoiceBuffer[$key])) {
                $this->invoiceBuffer[$key] = [
                    'patient_id'   => $patientId,
                    'invoice_date' => $invoiceDate,
                    'due_date'     => $dueDate,
                    'tax_percent'  => $taxPct,
                    'amount_paid'  => $amtPaid,
                    'notes'        => trim($row['notas'] ?? $row['notes'] ?? '') ?: null,
                    'status'       => $this->normalizeStatus($row['estado'] ?? $row['status'] ?? null, $amtPaid),
                    'items'        => [],
                ];
            }

            $this->invoiceBuffer[$key]['items'][] = [
                'description' => $itemDesc,
                'unit_price'  => $itemPrice,
                'quantity'    => $itemQty,
                'subtotal'    => $itemPrice * $itemQty,
            ];
        }

        $this->flushInvoices();
    }

    private function flushInvoices(): void
    {
        foreach ($this->invoiceBuffer as $data) {
            $subtotal = array_sum(array_column($data['items'], 'subtotal'));
            $taxAmt   = round($subtotal * ($data['tax_percent'] / 100), 2);
            $total    = $subtotal + $taxAmt;
            $paid     = min($data['amount_paid'], $total);

            $invoice = Invoice::create([
                'clinic_id'       => $this->clinicId,
                'patient_id'      => $data['patient_id'],
                'invoice_number'  => Invoice::generateNumber($this->clinicId),
                'invoice_date'    => $data['invoice_date'],
                'due_date'        => $data['due_date'],
                'status'          => $data['status'],
                'subtotal'        => $subtotal,
                'discount_amount' => 0,
                'discount_percent'=> 0,
                'tax_percent'     => $data['tax_percent'],
                'tax_amount'      => $taxAmt,
                'total'           => $total,
                'amount_paid'     => $paid,
                'notes'           => $data['notes'],
            ]);

            foreach ($data['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $item['description'],
                    'unit_price'  => $item['unit_price'],
                    'quantity'    => $item['quantity'],
                    'subtotal'    => $item['subtotal'],
                ]);
            }

            $this->imported++;
        }
    }

    private function resolvePatient(string $ref): ?int
    {
        if (! $ref) return null;
        if (isset($this->patientCache[$ref])) return $this->patientCache[$ref];

        $patient = Patient::where('clinic_id', $this->clinicId)
            ->where(fn($q) => $q->where('email', $ref)->orWhere('client_document', $ref))
            ->first();

        $this->patientCache[$ref] = $patient?->id;
        return $patient?->id;
    }

    private function normalizeStatus(?string $status, float $amtPaid): string
    {
        $map = ['draft' => 'draft', 'borrador' => 'draft', 'sent' => 'sent', 'enviada' => 'sent',
                'paid' => 'paid', 'pagada' => 'paid', 'partial' => 'partial', 'parcial' => 'partial',
                'overdue' => 'overdue', 'vencida' => 'overdue', 'cancelled' => 'cancelled', 'cancelada' => 'cancelled'];

        if ($status && isset($map[strtolower($status)])) return $map[strtolower($status)];
        return $amtPaid > 0 ? 'partial' : 'sent';
    }
}
