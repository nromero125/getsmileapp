<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * Show the subscription checkout page (Paddle inline checkout).
     */
    public function checkout(Request $request)
    {
        $clinic = $request->user()->clinic;

        // Already subscribed — go to dashboard
        if ($clinic->subscribed('default')) {
            return redirect()->route('dashboard');
        }

        $trialDaysLeft = $clinic->trial_ends_at
            ? max(0, (int) now()->diffInDays($clinic->trial_ends_at, false))
            : 0;

        return Inertia::render('Subscription/Checkout', [
            'clientToken'   => config('cashier.client_side_token'),
            'priceId'       => config('cashier.price_monthly'),
            'isSandbox'     => config('cashier.sandbox'),
            'trialDaysLeft' => $trialDaysLeft,
            'clinicName'    => $clinic->name,
        ]);
    }

    /**
     * Generate a Paddle checkout URL and return it (called by frontend).
     */
    public function checkoutUrl(Request $request)
    {
        $clinic = $request->user()->clinic;

        $priceId = config('cashier.price_monthly');

        $checkout = $clinic
            ->subscribe($priceId)
            ->customData(['clinic_id' => (string) $clinic->id])
            ->returnTo(route('dashboard'));

        return response()->json($checkout->toArray());
    }

    /**
     * Redirect to Paddle's customer portal.
     */
    public function billingPortal(Request $request)
    {
        $clinic    = $request->user()->clinic;
        $customer  = $clinic->customer;

        if (! $customer?->paddle_id) {
            return redirect()->route('subscription.manage')->with('error', 'No se encontró un cliente en Paddle.');
        }

        $baseUrl = config('cashier.sandbox')
            ? 'https://sandbox-api.paddle.com'
            : 'https://api.paddle.com';

        $response = Http::withToken(config('cashier.api_key'))
            ->send('POST', "{$baseUrl}/customers/{$customer->paddle_id}/portal-sessions");

        if (! $response->successful()) {
            \Illuminate\Support\Facades\Log::error('PaddlePortal', ['status' => $response->status(), 'body' => $response->json()]);
            return redirect()->route('subscription.manage')->with('error', 'No se pudo acceder al portal de facturación.');
        }

        $url = $response->json('data.urls.general.overview');

        return redirect($url);
    }

    /**
     * Show the subscription management page.
     */
    public function manage(Request $request)
    {
        $clinic = $request->user()->clinic;
        $subscription = $clinic->subscription('default');

        $trialDaysLeft = $clinic->trial_ends_at
            ? max(0, (int) now()->diffInDays($clinic->trial_ends_at, false))
            : 0;

        $waPlans = [
            'basic'    => ['label' => 'Básico',    'price' => 10, 'msgs' => config('cashier.wa_quota.basic')],
            'standard' => ['label' => 'Estándar',  'price' => 20, 'msgs' => config('cashier.wa_quota.standard')],
            'pro'      => ['label' => 'Pro',        'price' => 30, 'msgs' => config('cashier.wa_quota.pro')],
        ];

        return Inertia::render('Subscription/Manage', [
            'subscription'      => $subscription?->only(['paddle_id', 'status', 'trial_ends_at', 'created_at']),
            'clinic'            => $clinic->only(['id', 'name']),
            'seatsIncluded'     => 3,
            'activeUsers'       => $clinic->activeUserCount(),
            'extraSeats'        => $clinic->extraSeatCount(),
            'onTrial'           => $clinic->onLocalTrial(),
            'trialDaysLeft'     => $trialDaysLeft,
            'subscribed'        => $clinic->subscribed('default'),
            'waPlan'            => $clinic->wa_plan,
            'waMessagesQuota'   => $clinic->wa_messages_quota,
            'waMessagesUsed'    => $clinic->wa_messages_used,
            'waMessagesResetAt' => $clinic->wa_messages_reset_at?->toDateString(),
            'waPlans'           => $waPlans,
        ]);
    }

    /**
     * Show the subscription required page (trial expired, no payment).
     */
    public function required()
    {
        return Inertia::render('Subscription/Required', [
            'clientToken' => config('cashier.client_side_token'),
            'priceId'     => config('cashier.price_monthly'),
            'isSandbox'   => config('cashier.sandbox'),
        ]);
    }

    /**
     * Activate or change the clinic's WhatsApp message plan.
     * PATCH /subscription/whatsapp-plan
     */
    public function updateWhatsAppPlan(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:basic,standard,pro,none',
        ]);

        $clinic = $request->user()->clinic;
        $plan   = $request->plan === 'none' ? null : $request->plan;

        $quota = $plan ? config("cashier.wa_quota.{$plan}") : 0;

        // Patch Paddle subscription
        if ($clinic->subscribed('default')) {
            static::syncWhatsAppPlan($clinic, $plan);
        }

        $clinic->update([
            'wa_plan'              => $plan,
            'wa_messages_quota'    => $quota,
            'wa_messages_used'     => 0,
            'wa_messages_reset_at' => now(),
        ]);

        $label = $plan ? ucfirst($plan) : 'desactivado';
        return back()->with('success', "Plan WhatsApp {$label} activado.");
    }

    /**
     * Update extra seats in Paddle when user count changes.
     * Called internally by StaffController.
     */
    public static function syncExtraSeats($clinic): void
    {
        $extraSeatPriceId = config('cashier.price_extra_seat');

        \Illuminate\Support\Facades\Log::info('SyncSeats:start', [
            'price_extra_seat' => $extraSeatPriceId,
            'price_monthly'    => config('cashier.price_monthly'),
            'subscribed'       => $clinic->subscribed('default'),
        ]);

        if (!$extraSeatPriceId || !$clinic->subscribed('default')) {
            \Illuminate\Support\Facades\Log::warning('SyncSeats:skipped', ['reason' => !$extraSeatPriceId ? 'no price' : 'not subscribed']);
            return;
        }

        $subscription = $clinic->subscription('default');

        if (!$subscription || !$subscription->active()) {
            \Illuminate\Support\Facades\Log::warning('SyncSeats:skipped', ['reason' => 'subscription not active', 'status' => $subscription?->status]);
            return;
        }

        $extraSeats  = $clinic->extraSeatCount();
        $paddleSubId = $subscription->paddle_id;

        $baseUrl = config('cashier.sandbox')
            ? 'https://sandbox-api.paddle.com'
            : 'https://api.paddle.com';

        $items = [['price_id' => config('cashier.price_monthly'), 'quantity' => 1]];

        if ($extraSeats > 0) {
            $items[] = ['price_id' => $extraSeatPriceId, 'quantity' => $extraSeats];
        }

        \Illuminate\Support\Facades\Log::info('SyncSeats:patch', [
            'sub'         => $paddleSubId,
            'items'       => $items,
            'extraSeats'  => $extraSeats,
            'activeCount' => $clinic->activeUserCount(),
        ]);

        $response = Http::withToken(config('cashier.api_key'))
            ->patch("{$baseUrl}/subscriptions/{$paddleSubId}", [
                'items'                  => $items,
                'proration_billing_mode' => 'prorated_immediately',
            ]);

        \Illuminate\Support\Facades\Log::info('SyncSeats:response', ['status' => $response->status(), 'body' => $response->json()]);
    }

    /**
     * Sync WhatsApp plan item on the Paddle subscription.
     */
    public static function syncWhatsAppPlan($clinic, ?string $plan): void
    {
        $subscription = $clinic->subscription('default');
        if (! $subscription?->active()) return;

        $baseUrl     = config('cashier.sandbox') ? 'https://sandbox-api.paddle.com' : 'https://api.paddle.com';
        $waPriceKeys = ['basic' => 'price_wa_basic', 'standard' => 'price_wa_standard', 'pro' => 'price_wa_pro'];

        $items = [
            ['price_id' => config('cashier.price_monthly'), 'quantity' => 1],
        ];

        $extraSeats = $clinic->extraSeatCount();
        if ($extraSeats > 0 && config('cashier.price_extra_seat')) {
            $items[] = ['price_id' => config('cashier.price_extra_seat'), 'quantity' => $extraSeats];
        }

        if ($plan && isset($waPriceKeys[$plan]) && config("cashier.{$waPriceKeys[$plan]}")) {
            $items[] = ['price_id' => config("cashier.{$waPriceKeys[$plan]}"), 'quantity' => 1];
        }

        Http::withToken(config('cashier.api_key'))
            ->patch("{$baseUrl}/subscriptions/{$subscription->paddle_id}", [
                'items'                  => $items,
                'proration_billing_mode' => 'prorated_immediately',
            ]);
    }

}
