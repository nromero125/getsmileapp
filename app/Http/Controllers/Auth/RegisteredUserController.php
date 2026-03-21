<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'clinic_name' => 'required|string|max:255',
            'name'        => 'required|string|max:255',
            'email'       => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password'    => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $baseSlug = Str::slug($request->clinic_name);
        $slug = $baseSlug . '-' . Str::lower(Str::random(5));

        $clinic = Clinic::create([
            'name'          => $request->clinic_name,
            'slug'          => $slug,
            'email'         => $request->email,
            'is_active'     => true,
            'trial_ends_at' => now()->addDays(14),
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'clinic_id' => $clinic->id,
            'role'      => 'admin',
        ]);

        $clinic->seedDefaultCatalog();

        event(new Registered($user));
        Auth::login($user);

        // Redirect to subscription checkout to collect payment method (billed after trial)
        return redirect()->route('subscription.checkout');
    }
}
