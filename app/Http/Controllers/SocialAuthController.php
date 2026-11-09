<?php

namespace App\Http\Controllers;

use App\Models\usuario;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class SocialAuthController extends Controller
{
    private const MOBILE_CALLBACK_URI = 'bookcut://oauth/callback';

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $registrationId = $request->query('registration');
        if ($registrationId !== null) {
            if (
                ! is_string($registrationId)
                || ! is_array(Cache::get($this->registrationCacheKey($registrationId)))
            ) {
                return $this->redirectToApp(['error' => 'registration_expired']);
            }

            $request->session()->put('social_auth.registration', $registrationId);
        } else {
            $request->session()->forget('social_auth.registration');
        }

        if (
            ! config("services.{$provider}.client_id")
            || ! config("services.{$provider}.client_secret")
        ) {
            return $this->redirectToApp(['error' => 'provider_not_configured']);
        }

        $request->session()->put('social_auth.provider', $provider);

        return Socialite::driver($provider)->scopes(['email'])->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->redirectToApp([
                'error' => $request->query('error') === 'access_denied'
                    ? 'cancelled'
                    : 'provider_error',
            ]);
        }

        if ($request->session()->pull('social_auth.provider') !== $provider) {
            return $this->redirectToApp(['error' => 'invalid_state']);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            return $this->redirectToApp(['error' => 'invalid_state']);
        } catch (GuzzleException) {
            return $this->redirectToApp(['error' => 'provider_error']);
        } catch (\InvalidArgumentException) {
            return $this->redirectToApp(['error' => 'provider_error']);
        }

        $email = strtolower(trim((string) $socialUser->getEmail()));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->redirectToApp(['error' => 'email_unavailable']);
        }

        if (
            $provider === 'google'
            && ! filter_var(
                $socialUser->user['email_verified'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            )
        ) {
            return $this->redirectToApp(['error' => 'email_unverified']);
        }

        $user = usuario::where('correo', $email)->first();
        if (! $user) {
            $registrationId = $request->session()->pull('social_auth.registration');
            $registration = is_string($registrationId)
                ? Cache::pull($this->registrationCacheKey($registrationId))
                : null;

            if (! is_array($registration)) {
                return $this->redirectToApp(['error' => 'account_not_found']);
            }

            if (! $this->emailDomainAllowed($email)) {
                return $this->redirectToApp(['error' => 'email_domain_not_allowed']);
            }

            $nombre = trim((string) $socialUser->getName());
            if ($nombre === '') {
                $nombre = strstr($email, '@', true) ?: $email;
            }

            $user = usuario::firstOrCreate(
                ['correo' => $email],
                [
                    'Nombre' => mb_substr($nombre, 0, 255),
                    'telefono' => $registration['telefono'],
                    'contrasenha' => Hash::make(Str::random(64)),
                    'Roles_IDRol' => 3,
                    'terminos_aceptados' => true,
                ],
            );
        } else {
            $registrationId = $request->session()->pull('social_auth.registration');
            if (is_string($registrationId)) {
                Cache::forget($this->registrationCacheKey($registrationId));
            }
        }

        $code = Str::random(64);
        Cache::put(
            $this->cacheKey($code),
            $user->getKey(),
            now()->addMinute(),
        );

        return $this->redirectToApp(['code' => $code]);
    }

    public function exchange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|min:32|max:128',
        ]);

        $userId = Cache::pull($this->cacheKey($validated['code']));
        if (! $userId) {
            return response()->json([
                'result' => 'error',
                'message' => 'El inicio de sesión expiró. Inténtalo de nuevo.',
            ], 401);
        }

        $user = usuario::find($userId);
        if (! $user) {
            return response()->json([
                'result' => 'error',
                'message' => 'No se encontró la cuenta asociada.',
            ], 401);
        }

        $user->tokens()->where('name', 'book_cut_app')->delete();

        return response()->json([
            'result' => 'ok',
            'user' => $user,
            'token' => $user->createToken('book_cut_app')->plainTextToken,
        ]);
    }

    public function startRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telefono' => 'required|string|max:45',
            'terminos_aceptados' => 'required|accepted',
        ]);

        $registrationId = Str::random(64);
        Cache::put(
            $this->registrationCacheKey($registrationId),
            ['telefono' => trim($validated['telefono'])],
            now()->addMinutes(10),
        );

        return response()->json([
            'result' => 'ok',
            'registration' => $registrationId,
        ]);
    }

    private function cacheKey(string $code): string
    {
        return 'social_login:'.hash('sha256', $code);
    }

    private function registrationCacheKey(string $registrationId): string
    {
        return 'social_registration:'.hash('sha256', $registrationId);
    }

    private function emailDomainAllowed(string $email): bool
    {
        $domain = substr($email, strrpos($email, '@') + 1);

        return in_array($domain, ['gmail.com', 'hotmail.com', 'outlook.com', 'sena.edu.co'], true);
    }

    private function redirectToApp(array $parameters): RedirectResponse
    {
        return redirect()->away(
            self::MOBILE_CALLBACK_URI.'?'.http_build_query($parameters),
        );
    }
}
