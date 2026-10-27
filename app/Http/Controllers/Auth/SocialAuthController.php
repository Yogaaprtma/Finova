<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SocialAuthController extends Controller
{
    /**
     * The list of supported OAuth providers.
     *
     * @var array<int, string>
     */
    public const array SUPPORTED_PROVIDERS = [
        'google',
        'apple',
        'facebook',
    ];

    /**
     * Redirect the user to the OAuth provider authentication page.
     */
    public function redirect(string $provider): Response
    {
        $this->ensureProviderIsSupported($provider);

        try {
            return Socialite::driver($provider)->redirect();
        } catch (Throwable $e) {
            Log::warning("OAuth redirect failed for {$provider}: {$e->getMessage()}");

            return redirect()->route('login')->with(
                'status',
                'Authentication with ' . ucfirst($provider) . ' is not configured yet. Please check your credentials in .env.'
            );
        }
    }

    /**
     * Handle the callback returned by the OAuth provider.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            Log::warning("OAuth authentication failed for {$provider}: {$e->getMessage()}");

            return redirect()->route('login')->with(
                'status',
                'Authentication with '.ucfirst($provider).' was cancelled or failed. Please try again.'
            );
        }

        $providerId = (string) $socialUser->getId();
        if ($providerId === '') {
            return redirect()->route('login')->with(
                'status',
                'Failed to retrieve account details from '.ucfirst($provider).'.'
            );
        }

        /** @var User $user */
        $user = DB::transaction(function () use ($provider, $providerId, $socialUser) {
            // If already logged in, link provider to current user
            if (Auth::check()) {
                /** @var User $currentUser */
                $currentUser = Auth::user();

                $currentUser->socialAccounts()->updateOrCreate(
                    ['provider' => $provider],
                    [
                        'provider_id' => $providerId,
                        'avatar' => $socialUser->getAvatar(),
                    ]
                );

                return $currentUser;
            }

            // 1. Check if this social account is already linked
            $existingSocialAccount = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_id', $providerId)
                ->first();

            if ($existingSocialAccount) {
                if ($socialUser->getAvatar()) {
                    $existingSocialAccount->update(['avatar' => $socialUser->getAvatar()]);
                }

                return $existingSocialAccount->user;
            }

            // 2. Check if a user with the same email exists
            $email = $socialUser->getEmail();
            $name = $socialUser->getName() ?: ($socialUser->getNickname() ?: 'Finova User');

            $user = $email ? User::query()->where('email', $email)->first() : null;

            if (! $user) {
                // 3. Register a new user
                $fallbackEmail = $email ?: "{$provider}_{$providerId}@privaterelay.finova.app";

                $user = User::create([
                    'name' => $name,
                    'email' => $fallbackEmail,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)),
                ]);

                // Seed starter categories for new user
                app(DefaultCategorySeeder::class)->run($user->id);
            }

            // 4. Attach social account
            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => $providerId,
                'avatar' => $socialUser->getAvatar(),
            ]);

            return $user;
        });

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Ensure the given provider is supported.
     */
    protected function ensureProviderIsSupported(string $provider): void
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            abort(404, "Authentication provider [{$provider}] is not supported.");
        }
    }
}
