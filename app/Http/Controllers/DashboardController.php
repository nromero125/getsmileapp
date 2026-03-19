<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $today    = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        // KPI Stats
        $totalPatients    = Patient::where('clinic_id', $clinicId)->count();
        $appointmentsMonth = Appointment::where('clinic_id', $clinicId)
            ->whereBetween('appointment_date', [$monthStart, $monthEnd])
            ->count();

        $revenueMonth = Invoice::where('clinic_id', $clinicId)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->where('status', '!=', 'cancelled')
            ->sum('amount_paid');

        $pendingPayments = Invoice::where('clinic_id', $clinicId)
            ->whereIn('status', ['pending', 'partial'])
            ->sum(\DB::raw('total - amount_paid'));

        // Today's appointments
        $todayAppointments = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $today)
            ->with(['patient', 'dentist', 'treatments'])
            ->orderBy('appointment_date')
            ->get();

        // Upcoming (next 7 days)
        $upcomingAppointments = Appointment::where('clinic_id', $clinicId)
            ->whereBetween('appointment_date', [Carbon::tomorrow(), Carbon::now()->addDays(7)])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->with(['patient', 'dentist'])
            ->orderBy('appointment_date')
            ->take(10)
            ->get();

        // Monthly chart data (last 6 months)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $chartData[] = [
                'month'       => $month->format('M Y'),
                'appointments' => Appointment::where('clinic_id', $clinicId)
                    ->whereYear('appointment_date', $month->year)
                    ->whereMonth('appointment_date', $month->month)
                    ->count(),
                'revenue'     => (float) Invoice::where('clinic_id', $clinicId)
                    ->whereYear('invoice_date', $month->year)
                    ->whereMonth('invoice_date', $month->month)
                    ->sum('amount_paid'),
            ];
        }

        // Recent patients
        $recentPatients = Patient::where('clinic_id', $clinicId)
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_patients'    => $totalPatients,
                'appointments_month' => $appointmentsMonth,
                'revenue_month'     => (float) $revenueMonth,
                'pending_payments'  => (float) $pendingPayments,
            ],
            'today_appointments'    => $todayAppointments,
            'upcoming_appointments' => $upcomingAppointments,
            'chart_data'            => $chartData,
            'recent_patients'       => $recentPatients,
        ]);
    }
}
