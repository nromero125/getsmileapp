<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $clinic = $request->user()?->clinic;

        $subscription = null;
        if ($clinic) {
            $trialDaysLeft = $clinic->trial_ends_at
                ? max(0, (int) now()->diffInDays($clinic->trial_ends_at, false))
                : 0;

            $subscription = [
                'on_trial'        => $clinic->onLocalTrial(),
                'trial_days_left' => $trialDaysLeft,
                'trial_ends_at'   => $clinic->trial_ends_at?->toDateString(),
                'subscribed'      => $clinic->subscribed('default'),
                'active'          => $clinic->hasActiveAccess(),
                'active_users'    => $clinic->activeUserCount(),
                'seats_included'  => 3,
                'extra_seats'     => $clinic->extraSeatCount(),
            ];
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user'   => $request->user(),
                'clinic' => $clinic,
            ],
            'can' => [
                'clinical' => (bool) $request->user()?->can('clinical'),
                'billing'  => (bool) $request->user()?->can('billing'),
                'admin'    => (bool) $request->user()?->can('admin'),
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
            ],
            'subscription' => $subscription,
            'wa_unread'    => fn() => $clinic?->wa_plan
                ? DB::table('whatsapp_messages')
                    ->where('clinic_id', $clinic->id)
                    ->where('direction', 'in')
                    ->whereNull('read_at')
                    ->count()
                : 0,
        ]);
    }
}
