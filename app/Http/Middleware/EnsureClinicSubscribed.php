<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $clinic = $user->clinic;

        if (!$clinic) {
            return $next($request);
        }

        \Illuminate\Support\Facades\Log::info('SubscriptionCheck', [
            'clinic_id'    => $clinic->id,
            'trial_ends_at'=> $clinic->trial_ends_at,
            'on_trial'     => $clinic->onLocalTrial(),
            'subscribed'   => $clinic->subscribed('default'),
            'sub_count'    => $clinic->subscriptions()->count(),
        ]);

        if ($clinic->hasActiveAccess()) {
            return $next($request);
        }

        // Trial expired and no subscription
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Suscripción requerida.'], 402);
        }

        return redirect()->route('subscription.required');
    }
}
