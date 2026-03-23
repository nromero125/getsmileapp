<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Treatment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;

        [$start, $end, $prevStart, $prevEnd] = $this->resolvePeriod($request);

        // ── Revenue KPIs ─────────────────────────────────────────────────────
        $invoiced  = (float) Invoice::where('clinic_id', $clinicId)
            ->whereBetween('invoice_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $collected = (float) Payment::where('clinic_id', $clinicId)
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount'); // refunds are stored as negative, so they net out automatically

        $pending = (float) Invoice::where('clinic_id', $clinicId)
            ->whereIn('status', ['pending', 'partial'])
            ->sum(DB::raw('total - amount_paid'));

        $invoiceCount = Invoice::where('clinic_id', $clinicId)
            ->whereBetween('invoice_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->count();

        $avgTicket = $invoiceCount > 0 ? round($invoiced / $invoiceCount, 2) : 0;

        // Previous period revenue (for comparison)
        $prevCollected = (float) Payment::where('clinic_id', $clinicId)
            ->whereBetween('payment_date', [$prevStart, $prevEnd])
            ->sum('amount');

        $revenueChange = $prevCollected > 0
            ? round((($collected - $prevCollected) / $prevCollected) * 100, 1)
            : null;

        // ── Appointment KPIs ──────────────────────────────────────────────────
        $apptTotal = Appointment::where('clinic_id', $clinicId)
            ->whereBetween('appointment_date', [$start, $end])
            ->count();

        $apptByStatus = Appointment::where('clinic_id', $clinicId)
            ->whereBetween('appointment_date', [$start, $end])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $prevApptTotal = Appointment::where('clinic_id', $clinicId)
            ->whereBetween('appointment_date', [$prevStart, $prevEnd])
            ->count();

        $apptChange = $prevApptTotal > 0
            ? round((($apptTotal - $prevApptTotal) / $prevApptTotal) * 100, 1)
            : null;

        // ── Patient KPIs ──────────────────────────────────────────────────────
        $newPatients = Patient::where('clinic_id', $clinicId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // ── Monthly trend ─────────────────────────────────────────────────────
        $trend = $this->buildTrend($clinicId, $start, $end);

        // ── Top treatments by revenue ─────────────────────────────────────────
        $topTreatments = DB::table('appointment_treatments as at')
            ->join('appointments as a', 'a.id', '=', 'at.appointment_id')
            ->join('treatments as t', 't.id', '=', 'at.treatment_id')
            ->where('a.clinic_id', $clinicId)
            ->whereBetween('a.appointment_date', [$start, $end])
            ->whereNotIn('a.status', ['cancelled', 'no_show'])
            ->select('t.name', DB::raw('count(*) as count'), DB::raw('sum(at.price * at.quantity) as revenue'))
            ->groupBy('t.id', 't.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // ── Dentist performance ───────────────────────────────────────────────
        $dentistStats = Appointment::where('appointments.clinic_id', $clinicId)
            ->whereBetween('appointment_date', [$start, $end])
            ->join('users', 'users.id', '=', 'appointments.dentist_id')
            ->select(
                'users.name',
                DB::raw('count(*) as appointments'),
                DB::raw('sum(case when appointments.status = "completed" then 1 else 0 end) as completed'),
                DB::raw('sum(case when appointments.status in ("cancelled","no_show") then 1 else 0 end) as cancelled'),
                DB::raw('coalesce(sum(appointments.total_cost),0) as revenue')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('appointments')
            ->get();

        // ── Payment methods ───────────────────────────────────────────────────
        $paymentMethods = Payment::where('payments.clinic_id', $clinicId)
            ->whereBetween('payment_date', [$start, $end])
            ->select('method', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('method')
            ->get()
            ->map(fn($r) => [
                'method' => $this->methodLabel($r->method),
                'count'  => $r->count,
                'total'  => (float) $r->total,
            ]);

        return Inertia::render('Reports/Index', [
            'period'    => $request->get('period', 'this_month'),
            'startDate' => $start->toDateString(),
            'endDate'   => $end->toDateString(),
            'revenue' => [
                'invoiced'      => $invoiced,
                'collected'     => $collected,
                'pending'       => $pending,
                'avg_ticket'    => $avgTicket,
                'change_pct'    => $revenueChange,
            ],
            'appointments' => [
                'total'      => $apptTotal,
                'by_status'  => $apptByStatus,
                'change_pct' => $apptChange,
            ],
            'new_patients'    => $newPatients,
            'trend'           => $trend,
            'top_treatments'  => $topTreatments,
            'dentist_stats'   => $dentistStats,
            'payment_methods' => $paymentMethods,
        ]);
    }

    private function resolvePeriod(Request $request): array
    {
        $period = $request->get('period', 'this_month');

        switch ($period) {
            case 'last_month':
                $start = Carbon::now()->subMonth()->startOfMonth();
                $end   = Carbon::now()->subMonth()->endOfMonth();
                $prevStart = $start->copy()->subMonth()->startOfMonth();
                $prevEnd   = $start->copy()->subMonth()->endOfMonth();
                break;
            case 'quarter':
                $start = Carbon::now()->startOfQuarter();
                $end   = Carbon::now()->endOfQuarter();
                $prevStart = $start->copy()->subQuarter()->startOfQuarter();
                $prevEnd   = $start->copy()->subQuarter()->endOfQuarter();
                break;
            case 'year':
                $start = Carbon::now()->startOfYear();
                $end   = Carbon::now()->endOfYear();
                $prevStart = $start->copy()->subYear()->startOfYear();
                $prevEnd   = $start->copy()->subYear()->endOfYear();
                break;
            case 'custom':
                $start = Carbon::parse($request->get('start', now()->startOfMonth()));
                $end   = Carbon::parse($request->get('end', now()->endOfMonth()))->endOfDay();
                $diff  = $start->diffInDays($end);
                $prevStart = $start->copy()->subDays($diff + 1);
                $prevEnd   = $start->copy()->subDay();
                break;
            default: // this_month
                $start = Carbon::now()->startOfMonth();
                $end   = Carbon::now()->endOfMonth();
                $prevStart = Carbon::now()->subMonth()->startOfMonth();
                $prevEnd   = Carbon::now()->subMonth()->endOfMonth();
        }

        return [$start, $end, $prevStart, $prevEnd];
    }

    private function buildTrend(int $clinicId, Carbon $start, Carbon $end): array
    {
        $months = [];
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $mStart = $cursor->copy()->startOfMonth();
            $mEnd   = $cursor->copy()->endOfMonth();

            $months[] = [
                'month'        => $cursor->isoFormat('MMM YY'),
                'revenue'      => (float) Payment::where('clinic_id', $clinicId)
                    ->whereBetween('payment_date', [$mStart, $mEnd])
                    ->sum('amount'),
                'appointments' => Appointment::where('clinic_id', $clinicId)
                    ->whereBetween('appointment_date', [$mStart, $mEnd])
                    ->count(),
            ];

            $cursor->addMonth();
        }

        return $months;
    }

    private function methodLabel(string $method): string
    {
        return match ($method) {
            'cash'         => 'Efectivo',
            'card'         => 'Tarjeta',
            'transfer'     => 'Transferencia',
            'check'        => 'Cheque',
            default        => ucfirst($method),
        };
    }
}
