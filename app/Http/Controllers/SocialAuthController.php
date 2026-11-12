<?php

namespace App\Http\Controllers;

use App\Models\usuario;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialAuthController extends Controller
{
    private const MOBILE_CALLBACK_URI = 'bookcut://oauth/callback';

    private const GOOGLE_PHONE_SCOPE = 'https://www.googleapis.com/auth/user.phonenumbers.read';

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $request->session()->put(
            'social_auth.client',
            $request->query('client') === 'web' ? 'web' : 'mobile',
        );

        $registrationId = $request->query('registration');
        if ($registrationId !== null) {
            $registration = is_string($registrationId)
                ? DB::table('auth_temporary_tokens')
                    ->where('token_hash', $this->temporaryTokenHash('social_registration', $registrationId))
                    ->where('purpose', 'social_registration')
                    ->where('expires_at', '>', now())
                    ->first(['payload'])
                : null;

            if (
                !is_string($registrationId)
                || !$registration
            ) {
                return $this->redirectToApp(['error' => 'registration_expired']);
            }

            $registrationPayload = json_decode($registration->payload, true);
            $request->session()->put('social_auth.registration', $registrationId);
            $request->session()->put(
                'social_auth.request_google_phone',
                !is_array($registrationPayload) || empty($registrationPayload['telefono']),
            );
        } else {
            $request->session()->forget('social_auth.registration');
            $request->session()->forget('social_auth.request_google_phone');
        }

        if (
            !config("services.{$provider}.client_id")
            || !config("services.{$provider}.client_secret")
        ) {
            return $this->redirectToApp(['error' => 'provider_not_configured']);
        }

        $request->session()->put('social_auth.provider', $provider);

        $scopes = ['email'];
        if ($provider === 'google' && $request->session()->get('social_auth.request_google_phone')) {
            $scopes[] = self::GOOGLE_PHONE_SCOPE;
        }

        return Socialite::driver($provider)->scopes($scopes)->redirect();
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
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->redirectToApp(['error' => 'email_unavailable']);
        }

        if (
            $provider === 'google'
            && !filter_var(
                $socialUser->user['email_verified'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            )
        ) {
            return $this->redirectToApp(['error' => 'email_unverified']);
        }

        $user = usuario::where('correo', $email)->first();
        if (!$user) {
            $registrationId = $request->session()->pull('social_auth.registration');
            $registration = is_string($registrationId)
                ? DB::transaction(function () use ($registrationId): ?array {
                    $tokenHash = $this->temporaryTokenHash('social_registration', $registrationId);
                    $record = DB::table('auth_temporary_tokens')
                        ->where('token_hash', $tokenHash)
                        ->where('purpose', 'social_registration')
                        ->where('expires_at', '>', now())
                        ->lockForUpdate()
                        ->first();

                    DB::table('auth_temporary_tokens')->where('token_hash', $tokenHash)->delete();

                    return $record
                        ? json_decode($record->payload, true, 512, JSON_THROW_ON_ERROR)
                        : null;
                })
                : null;

            if (!is_array($registration)) {
                return $this->redirectToApp(['error' => 'account_not_found']);
            }

            if (!$this->emailDomainAllowed($email)) {
                return $this->redirectToApp(['error' => 'email_domain_not_allowed']);
            }

            $nombre = trim((string) $socialUser->getName());
            if ($nombre === '') {
                $nombre = strstr($email, '@', true) ?: $email;
            }

            $telefono = trim((string) ($registration['telefono'] ?? ''));
            if ($telefono === '') {
                $telefono = $this->googlePhoneNumber($socialUser);
            }

            $user = usuario::firstOrCreate(
                ['correo' => $email],
                [
                    'Nombre' => mb_substr($nombre, 0, 255),
                    'telefono' => $telefono,
                    'contrasenha' => Hash::make(Str::random(64)),
                    'Roles_IDRol' => 3,
                    'terminos_aceptados' => true,
                ],
            );
        } else {
            $registrationId = $request->session()->pull('social_auth.registration');
            if (is_string($registrationId)) {
                DB::table('auth_temporary_tokens')
                    ->where('token_hash', $this->temporaryTokenHash('social_registration', $registrationId))
                    ->where('purpose', 'social_registration')
                    ->delete();
            }
        }

        $code = Str::random(64);
        $now = now();
        DB::table('auth_temporary_tokens')->where('expires_at', '<=', $now)->delete();
        DB::table('auth_temporary_tokens')->insert([
            'token_hash' => $this->temporaryTokenHash('social_login', $code),
            'purpose' => 'social_login',
            'payload' => json_encode(['user_id' => $user->getKey()], JSON_THROW_ON_ERROR),
            'attempts' => 0,
            'expires_at' => $now->copy()->addMinute(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->redirectToApp(['code' => $code]);
    }

    public function exchange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|min:32|max:128',
        ]);

        $tokenHash = $this->temporaryTokenHash('social_login', $validated['code']);
        $userId = DB::transaction(function () use ($tokenHash): ?int {
            $record = DB::table('auth_temporary_tokens')
                ->where('token_hash', $tokenHash)
                ->where('purpose', 'social_login')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            DB::table('auth_temporary_tokens')->where('token_hash', $tokenHash)->delete();

            if (!$record) {
                return null;
            }

            $payload = json_decode($record->payload, true, 512, JSON_THROW_ON_ERROR);

            return isset($payload['user_id']) ? (int) $payload['user_id'] : null;
        });

        if (!$userId) {
            return response()->json([
                'result' => 'error',
                'message' => 'El inicio de sesión expiró. Inténtalo de nuevo.',
            ], 401);
        }

        $user = usuario::find($userId);
        if (!$user) {
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
            'telefono' => 'nullable|string|max:45',
            'terminos_aceptados' => 'required|accepted',
        ]);

        $registrationId = Str::random(64);
        $now = now();
        DB::table('auth_temporary_tokens')->where('expires_at', '<=', $now)->delete();
        DB::table('auth_temporary_tokens')->insert([
            'token_hash' => $this->temporaryTokenHash('social_registration', $registrationId),
            'purpose' => 'social_registration',
            'payload' => json_encode([
                'telefono' => isset($validated['telefono']) ? trim($validated['telefono']) : null,
            ], JSON_THROW_ON_ERROR),
            'attempts' => 0,
            'expires_at' => $now->copy()->addMinutes(10),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'result' => 'ok',
            'registration' => $registrationId,
        ]);
    }

    private function temporaryTokenHash(string $purpose, string $token): string
    {
        return hash('sha256', $purpose . ':' . $token);
    }

    private function emailDomainAllowed(string $email): bool
    {
        $domain = substr($email, strrpos($email, '@') + 1);

        return in_array($domain, ['gmail.com', 'hotmail.com', 'outlook.com', 'sena.edu.co'], true);
    }

    private function googlePhoneNumber(SocialiteUser $socialUser): ?string
    {
        if (!is_string($socialUser->token) || $socialUser->token === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withToken($socialUser->token)
                ->timeout(5)
                ->get('https://people.googleapis.com/v1/people/me', [
                    'personFields' => 'phoneNumbers',
                ]);

            if (!$response->successful()) {
                return null;
            }

            $phoneNumbers = $response->json('phoneNumbers', []);
            if (!is_array($phoneNumbers)) {
                return null;
            }

            usort(
                $phoneNumbers,
                static fn(array $left, array $right): int => (int) data_get($right, 'metadata.primary', false)
                    <=> (int) data_get($left, 'metadata.primary', false),
            );

            foreach ($phoneNumbers as $phoneNumber) {
                $value = is_array($phoneNumber) ? trim((string) ($phoneNumber['value'] ?? '')) : '';
                if ($value !== '' && mb_strlen($value) <= 45) {
                    return $value;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function redirectToApp(array $parameters): RedirectResponse
    {
        if (session('social_auth.client') === 'web') {
            $frontendHost = parse_url((string) config('services.frontend_url'), PHP_URL_HOST);
            $apiHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            $frontendBase = $frontendHost && $frontendHost !== $apiHost
                ? rtrim((string) config('services.frontend_url'), '/')
                : 'https://book-cut.github.io';
            $frontendPath = trim((string) config('services.frontend_path', '/DIST-FRONT'), '/');

            if ($frontendPath === '' || $frontendPath === 'public' || str_starts_with($frontendPath, 'public/')) {
                $frontendPath = 'DIST-FRONT';
            }

            $frontendUrl = $frontendBase . '/' . $frontendPath;

            return redirect()->away(
                $frontendUrl . '/#/auth/google/callback?' . http_build_query($parameters),
            );
        }

        return redirect()->away(
            self::MOBILE_CALLBACK_URI . '?' . http_build_query($parameters),
        );
    }
}
