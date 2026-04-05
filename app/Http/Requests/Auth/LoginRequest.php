<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LoginRequest extends FormRequest
{
    private const DUMMY_PASSWORD_HASH = '$2y$12$RXOe5P/SU2iAva4Ndd7GtOPr0PYu3lwhf5bPkjTN/Gmg5BtiEG.jW';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $password = $this->string('password')->toString();
        $user = User::query()
            ->where('email', Str::lower($this->string('email')->toString()))
            ->first();

        $passwordIsValid = Hash::check($password, $user?->password ?? self::DUMMY_PASSWORD_HASH);

        if (! $user || ! $passwordIsValid) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'device_name' => $this->resolveDeviceName(),
        ]);
    }

    private function resolveDeviceName(): string
    {
        $providedDeviceName = trim((string) $this->input('device_name'));

        if ($providedDeviceName !== '') {
            return $providedDeviceName;
        }

        $headerDeviceName = trim((string) $this->header('X-Device-Name'));

        if ($headerDeviceName !== '') {
            return Str::limit($headerDeviceName, 255, '');
        }

        $userAgent = preg_replace('/\s+/', ' ', trim((string) $this->userAgent()));

        if (is_string($userAgent) && $userAgent !== '') {
            return Str::limit($userAgent, 255, '');
        }

        return 'unknown-device';
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw new HttpResponseException(response()->json([
            'message' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ], Response::HTTP_TOO_MANY_REQUESTS));
    }

    private function throttleKey(): string
    {
        return Str::transliterate($this->string('email')->toString()).'|'.$this->ip();
    }
}
