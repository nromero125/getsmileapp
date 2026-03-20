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
            ->customData(['subscription_type' => 'default', 'clinic_id' => (string) $clinic->id])
            ->returnTo(route('dashboard'));

        return response()->json($checkout->toArray());
    }

    /**
     * Redirect to Paddle's billing portal.
     */
    public function billingPortal(Request $request)
    {
        return $request->user()->clinic->redirectToBillingPortal(route('dashboard'));
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
     * Update extra seats in Paddle when user count changes.
     * Called internally by StaffController.
     */
    public static function syncExtraSeats($clinic): void
    {
        $extraSeatPriceId = config('cashier.price_extra_seat');

        if (!$extraSeatPriceId || !$clinic->subscribed('default')) {
            return;
        }

        $subscription = $clinic->subscription('default');

        if (!$subscription || !$subscription->active()) {
            return;
        }

        $extraSeats = $clinic->extraSeatCount();
        $paddleSubId = $subscription->paddle_id;

        $baseUrl = config('cashier.sandbox')
            ? 'https://sandbox-api.paddle.com'
            : 'https://api.paddle.com';

        // Build subscription items: always include base, add extra seats if needed
        $items = [['price_id' => config('cashier.price_monthly'), 'quantity' => 1]];

        if ($extraSeats > 0) {
            $items[] = ['price_id' => $extraSeatPriceId, 'quantity' => $extraSeats];
        }

        Http::withToken(config('cashier.api_key'))
            ->patch("{$baseUrl}/subscriptions/{$paddleSubId}", [
                'items'                  => $items,
                'proration_billing_mode' => 'prorated_immediately',
            ]);
    }
}
