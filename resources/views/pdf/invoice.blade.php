<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1F2937; background: #fff; }
  .header { background: #0F1F3D; color: white; padding: 28px 40px; }
  .header-top { display: flex; justify-content: space-between; align-items: center; }
  .clinic-logo { max-height: 52px; max-width: 160px; object-fit: contain; }
  .clinic-name-fallback { font-size: 20px; font-weight: bold; color: #00BFA6; }
  .clinic-sub { font-size: 10px; color: #94A3B8; margin-top: 3px; }
  .invoice-title { text-align: right; }
  .invoice-title h1 { font-size: 26px; font-weight: bold; color: white; letter-spacing: 2px; }
  .invoice-title .num { font-size: 14px; color: #00BFA6; margin-top: 4px; }
  .header-bottom { display: flex; justify-content: space-between; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.1); }
  .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; margin-bottom: 4px; }
  .value { color: #E2E8F0; font-size: 12px; }
  .content { padding: 28px 40px; }
  .bill-to { display: flex; justify-content: space-between; margin-bottom: 24px; }
  .bill-block h3 { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6B7280; margin-bottom: 6px; }
  .bill-block p { font-size: 13px; font-weight: bold; color: #111827; }
  .bill-block span { font-size: 11px; color: #6B7280; display: block; margin-top: 2px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  thead tr { background: #F9FAFB; border-bottom: 2px solid #E5E7EB; }
  thead th { padding: 10px 12px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6B7280; font-weight: 600; }
  thead th.right { text-align: right; }
  tbody tr { border-bottom: 1px solid #F3F4F6; }
  tbody td { padding: 10px 12px; font-size: 12px; color: #374151; }
  tbody td.right { text-align: right; }
  .totals { width: 260px; margin-left: auto; }
  .totals table { margin: 0; }
  .totals td { padding: 5px 12px; }
  .totals .total-row { background: #0F1F3D; color: white; border-radius: 8px; }
  .totals .total-row td { padding: 10px 12px; font-weight: bold; font-size: 14px; }
  .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
  .status-paid    { background: #D1FAE5; color: #065F46; }
  .status-pending { background: #FEF3C7; color: #92400E; }
  .status-partial { background: #DBEAFE; color: #1E40AF; }
  .footer { border-top: 1px solid #E5E7EB; margin: 0 40px; padding: 18px 0; display: flex; justify-content: space-between; align-items: center; color: #9CA3AF; font-size: 10px; }
</style>
</head>
<body>

<div class="header">
  <div class="header-top">
    <div>
      @if($clinic->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($clinic->logo_path))
        <img src="{{ 'data:' . mime_content_type(\Illuminate\Support\Facades\Storage::disk('public')->path($clinic->logo_path)) . ';base64,' . base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($clinic->logo_path)) }}"
          class="clinic-logo" alt="{{ $clinic->name }}" />
        <div class="clinic-sub" style="margin-top:6px">{{ $clinic->name }}</div>
      @else
        <div class="clinic-name-fallback">{{ $clinic->name }}</div>
        @if($clinic->email || $clinic->phone)
          <div class="clinic-sub">{{ $clinic->email }}{{ $clinic->email && $clinic->phone ? '  ·  ' : '' }}{{ $clinic->phone }}</div>
        @endif
      @endif
    </div>
    <div class="invoice-title">
      <h1>FACTURA</h1>
      <div class="num">{{ $invoice->invoice_number }}</div>
      @if($invoice->ncf)
      <div style="margin-top:8px;background:rgba(0,191,166,0.15);border:1px solid rgba(0,191,166,0.4);border-radius:6px;padding:6px 10px;display:inline-block">
        <div style="font-size:8px;text-transform:uppercase;letter-spacing:1px;color:#94A3B8;margin-bottom:2px">NCF</div>
        <div style="font-size:13px;font-weight:bold;color:#00BFA6;letter-spacing:1.5px">{{ $invoice->ncf }}</div>
        <div style="font-size:9px;color:#94A3B8;margin-top:1px">{{ $invoice->ncf_type === 'B01' ? 'Crédito Fiscal' : 'Consumo' }}</div>
      </div>
      @endif
    </div>
  </div>
  <div class="header-bottom">
    <div>
      <div class="label">De</div>
      <div class="value">{{ $clinic->name }}</div>
      @if($clinic->address)<div class="value" style="font-size:11px;color:#94A3B8;margin-top:2px">{{ $clinic->address }}</div>@endif
      @if($clinic->phone)<div class="value" style="font-size:11px;color:#94A3B8">{{ $clinic->phone }}</div>@endif
      @if($clinic->tax_id)<div class="value" style="font-size:11px;color:#94A3B8">RNC: {{ $clinic->tax_id }}</div>@endif
    </div>
    <div style="text-align:right">
      <div class="label">Fecha de emisión</div>
      <div class="value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->locale('es')->isoFormat('D MMM YYYY') }}</div>
      @if($invoice->due_date)
      <div class="label" style="margin-top:8px">Fecha de vencimiento</div>
      <div class="value">{{ \Carbon\Carbon::parse($invoice->due_date)->locale('es')->isoFormat('D MMM YYYY') }}</div>
      @endif
    </div>
  </div>
</div>

<div class="content">
  <div class="bill-to">
    <div class="bill-block">
      <h3>Facturar a</h3>
      <p>{{ $invoice->patient->first_name }} {{ $invoice->patient->last_name }}</p>
      @if($invoice->patient->phone)<span>{{ $invoice->patient->phone }}</span>@endif
      @if($invoice->patient->email)<span>{{ $invoice->patient->email }}</span>@endif
    </div>
    <div style="text-align:right">
      <div class="label" style="text-align:right;margin-bottom:6px">Estado</div>
      @php
        $statusLabels = ['paid' => 'Pagada', 'pending' => 'Pendiente', 'partial' => 'Pago parcial', 'cancelled' => 'Cancelada', 'draft' => 'Borrador', 'refunded' => 'REEMBOLSADA', 'voided' => 'ANULADA'];
      @endphp
      <span class="status-badge status-{{ $invoice->status }}">{{ $statusLabels[$invoice->status] ?? strtoupper($invoice->status) }}</span>
      @if($invoice->ncf_void)
      <div style="margin-top:8px;background:#FEF2F2;border:1px solid #FECACA;border-radius:6px;padding:6px 10px;display:inline-block">
        <div style="font-size:8px;text-transform:uppercase;letter-spacing:1px;color:#9CA3AF;margin-bottom:2px">NCF Anulación</div>
        <div style="font-size:13px;font-weight:bold;color:#EF4444;letter-spacing:1.5px">{{ $invoice->ncf_void }}</div>
        <div style="font-size:9px;color:#9CA3AF;margin-top:1px">B04 — Nota de Crédito</div>
      </div>
      @endif
      <br><br>
      <div class="label" style="text-align:right;margin-bottom:4px">Saldo pendiente</div>
      <div style="font-size:20px;font-weight:bold;color:#0F1F3D">RD${{ number_format($invoice->total - $invoice->amount_paid, 2) }}</div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Descripción</th>
        <th class="right" style="width:60px">Cant.</th>
        <th class="right" style="width:100px">Precio unit.</th>
        <th class="right" style="width:100px">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->items as $item)
      <tr>
        <td>{{ $item->description }}</td>
        <td class="right">{{ $item->quantity }}</td>
        <td class="right">RD${{ number_format($item->unit_price, 2) }}</td>
        <td class="right">RD${{ number_format($item->total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="totals">
    <table>
      <tr><td style="color:#6B7280">Subtotal</td><td style="text-align:right">RD${{ number_format($invoice->subtotal, 2) }}</td></tr>
      @if($invoice->discount_amount > 0)
      <tr><td style="color:#059669">Descuento ({{ $invoice->discount_percent }}%)</td><td style="text-align:right;color:#059669">-RD${{ number_format($invoice->discount_amount, 2) }}</td></tr>
      @endif
      @if($invoice->tax_amount > 0)
      <tr><td style="color:#6B7280">Impuesto ({{ $invoice->tax_percent }}%)</td><td style="text-align:right">RD${{ number_format($invoice->tax_amount, 2) }}</td></tr>
      @endif
      @if($invoice->amount_paid > 0)
      <tr><td style="color:#059669">Pagado</td><td style="text-align:right;color:#059669">-RD${{ number_format($invoice->amount_paid, 2) }}</td></tr>
      @endif
      <tr class="total-row">
        <td>Saldo pendiente</td>
        <td style="text-align:right">RD${{ number_format($invoice->total - $invoice->amount_paid, 2) }}</td>
      </tr>
    </table>
  </div>

  @if($invoice->notes)
  <div style="margin-top:24px;padding:16px;background:#F9FAFB;border-radius:8px;border-left:3px solid #00BFA6">
    <div class="label">Notas</div>
    <p style="margin-top:6px;color:#374151;font-size:11px;line-height:1.6">{{ $invoice->notes }}</p>
  </div>
  @endif
</div>

<div class="footer">
  <span>{{ $clinic->name }}</span>
  <span>¡Gracias por confiar en {{ $clinic->name }}!</span>
  <span>Generada el {{ now()->locale('es')->isoFormat('D MMM YYYY') }}</span>
</div>

</body>
</html>
