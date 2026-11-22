<?php

namespace App\Http\Controllers\Web;

use App\Mail\RunnerSignInCodeMail;
use App\Models\Event;
use App\Models\RunnerAccount;
use App\Models\RunnerEmailChallenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class RunnerAccountController
{
    private const CODE_LIFETIME_MINUTES = 10;

    private const RESEND_DELAY_SECONDS = 60;

    private const CODE_ATTEMPTS = 5;

    public function login(Request $request): RedirectResponse|Response
    {
        $context = $this->eventContext($request->query());

        if (Auth::guard('runner')->check()) {
            return $this->redirectAfterLogin($context);
        }

        return $this->authPage($context);
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $context = $this->eventContext($request->all());
        if (Auth::guard('runner')->check()) {
            return $this->redirectAfterLogin($context);
        }

        $sessionKey = $this->sessionKey($request);
        $resending = ! $request->has('email');
        if ($resending) {
            $challenge = RunnerEmailChallenge::find($sessionKey);
            if (! $challenge) {
                return redirect()->route('runner.login', $this->contextParameters($context));
            }

            $context = $this->challengeContext($challenge);
            $request->merge(['email' => $challenge->email]);
        }

        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }
        $email = $request->validate(['email' => ['required', 'email', 'max:190']])['email'];
        $parameters = $this->contextParameters($context);
        $backToAuth = fn () => redirect()->route($resending ? 'runner.verify' : 'runner.login', $parameters);

        $mailer = config('mail.default');
        $transport = config("mail.mailers.{$mailer}.transport");
        $localLog = app()->isLocal() && $transport === 'log';
        if (in_array($transport, ['log', 'array'], true) && ! $localLog && ! app()->environment('testing')) {
            return $backToAuth()->withErrors(['email' => __('Email sign-in is temporarily unavailable. You can continue as a guest.')]);
        }

        $ipKey = 'runner-code-send:ip:'.$this->digest('ip', (string) $request->ip());
        $emailKey = 'runner-code-send:email:'.$this->digest('email', $email);

        if (RateLimiter::tooManyAttempts($ipKey, 15) || RateLimiter::tooManyAttempts($emailKey, 5)) {
            return $backToAuth()->withErrors(['email' => __('Too many sign-in codes requested. Please try again later.')]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now = now();
        $values = [
            'email' => $email,
            'code_hash' => $this->codeHash($sessionKey, $code),
            'event_slug' => $context['eventSlug'],
            'race_slug' => $context['raceSlug'],
            'kit' => $context['kit'],
            'return_to_registration' => $context['returnToRegistration'],
            'attempts' => 0,
            'expires_at' => $now->copy()->addMinutes(self::CODE_LIFETIME_MINUTES),
            'resend_at' => $now->copy()->addSeconds(self::RESEND_DELAY_SECONDS),
        ];

        // Keep one challenge per browser session. A concurrent resend must also pass the cooldown.
        $updated = RunnerEmailChallenge::query()
            ->whereKey($sessionKey)
            ->where('resend_at', '<=', $now)
            ->update($values);

        if (! $updated && ! RunnerEmailChallenge::query()->insertOrIgnore(['session_key' => $sessionKey, ...$values])) {
            return $backToAuth()->withErrors(['email' => __('Please wait a minute before requesting another code.')]);
        }

        if (RateLimiter::hit($ipKey, 3600) > 15 || RateLimiter::hit($emailKey, 3600) > 5) {
            RunnerEmailChallenge::query()->whereKey($sessionKey)->where('code_hash', $values['code_hash'])->delete();

            return redirect()->route('runner.login', $parameters)
                ->withErrors(['email' => __('Too many sign-in codes requested. Please try again later.')]);
        }

        try {
            Mail::to($email)->send(new RunnerSignInCodeMail($code, self::CODE_LIFETIME_MINUTES));
        } catch (Throwable $exception) {
            RunnerEmailChallenge::query()->whereKey($sessionKey)->where('code_hash', $values['code_hash'])->delete();
            Log::warning('Runner sign-in code could not be delivered.', ['exception' => $exception::class]);

            return redirect()->route('runner.login', $parameters)
                ->withInput(['email' => $email])
                ->withErrors(['email' => __('We could not send a sign-in code. Please try again.')]);
        }

        RunnerEmailChallenge::query()->where('expires_at', '<', $now->copy()->subDay())->limit(100)->delete();

        $status = $localLog
            ? __('The sign-in code was written to the local application log.')
            : __('A sign-in code is on its way to your email.');

        return redirect()->route('runner.verify', $parameters)->with('status', $status);
    }

    public function verify(Request $request): RedirectResponse|Response
    {
        $challenge = RunnerEmailChallenge::find($this->sessionKey($request));
        $context = $challenge ? $this->challengeContext($challenge) : $this->eventContext($request->query());

        if (Auth::guard('runner')->check()) {
            return $this->redirectAfterLogin($context);
        }

        if (! $challenge) {
            return redirect()->route('runner.login', $this->contextParameters($context));
        }

        return $this->authPage($context, $challenge);
    }

    public function verifyCode(Request $request): RedirectResponse
    {
        $context = $this->eventContext($request->all());
        if (Auth::guard('runner')->check()) {
            return $this->redirectAfterLogin($context);
        }

        $verifyKey = 'runner-code-verify:ip:'.$this->digest('ip', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($verifyKey, 30) || RateLimiter::hit($verifyKey, 60) > 30) {
            return redirect()->route('runner.verify', $this->contextParameters($context))
                ->withErrors(['code' => __('Too many verification attempts. Please try again in a minute.')]);
        }

        $code = $request->input('code');
        if (! is_string($code) || strlen($code) > 24 || ! preg_match('/\A[0-9]{6}\z/', $code = $this->normalizeDigits(trim($code)))) {
            return redirect()->route('runner.verify', $this->contextParameters($context))
                ->withErrors(['code' => __('Enter the six-digit code from your email.')]);
        }

        $sessionKey = $this->sessionKey($request);
        $result = DB::transaction(function () use ($sessionKey, $code) {
            $challenge = RunnerEmailChallenge::query()->whereKey($sessionKey)->lockForUpdate()->first();

            if (! $challenge) {
                return ['error' => __('This code has expired. Request a new code to continue.'), 'missing' => true];
            }
            if ($challenge->expires_at->isPast()) {
                return ['error' => __('This code has expired. Request a new code to continue.')];
            }
            if ($challenge->attempts >= self::CODE_ATTEMPTS) {
                return ['error' => __('This code has reached its attempt limit. Request a new code.')];
            }
            if (! hash_equals($challenge->code_hash, $this->codeHash($sessionKey, $code))) {
                $challenge->increment('attempts');
                $error = $challenge->attempts >= self::CODE_ATTEMPTS
                    ? __('This code has reached its attempt limit. Request a new code.')
                    : __('The code does not match. Check your latest email and try again.');

                return ['error' => $error];
            }

            $runner = RunnerAccount::firstOrCreate(
                ['email' => $challenge->email],
                ['email_verified_at' => now()],
            );
            $challenge->delete();

            return ['runner' => $runner, 'challenge' => $challenge];
        }, 3);

        if (! isset($result['runner'])) {
            return redirect()->route(isset($result['missing']) ? 'runner.login' : 'runner.verify', $this->contextParameters($context))
                ->withErrors(['code' => $result['error']]);
        }

        /** @var RunnerAccount $runner */
        $runner = $result['runner'];
        $context = $this->challengeContext($result['challenge']);

        // The session guard regenerates the session ID and CSRF token at login.
        Auth::guard('runner')->login($runner);

        if ($runner->wasRecentlyCreated) {
            return redirect()->route('runner.account', [...$this->contextParameters($context), 'setup' => 1]);
        }

        return $this->redirectAfterLogin($context);
    }

    public function account(Request $request): RedirectResponse|Response
    {
        $context = $this->eventContext($request->query());
        $runner = Auth::guard('runner')->user();
        if (! $runner) {
            return redirect()->route('runner.login', $this->contextParameters($context));
        }

        $registrations = $runner->registrations()
            ->select(['id', 'registration_code', 'bib_number', 'race_id', 'first_name', 'last_name',
                'kit_type', 'shirt_size', 'status', 'payment_status', 'created_at'])
            ->with('race.event')
            ->latest('id')
            ->paginate(10)
            ->appends($this->contextParameters($context));

        return response()->view('site.runner.account', [
            ...$context,
            'runner' => $runner,
            'registrations' => $registrations,
            'onboarding' => $request->boolean('setup'),
            'returnUrl' => $this->registrationUrl($context),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $context = $this->eventContext($request->all());
        $runner = Auth::guard('runner')->user();
        if (! $runner) {
            return redirect()->route('runner.login', $this->contextParameters($context));
        }

        $runner->update($request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/\A\+?[\p{Nd} ().-]+\z/u'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'team_name' => ['nullable', 'string', 'max:120'],
        ]));

        return $this->redirectAfterLogin($context)->with('status', __('Your runner profile has been saved.'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $context = $this->eventContext($request->all());
        Auth::guard('runner')->logout();
        $request->session()->regenerate(true);

        return redirect()->to($this->registrationUrl($context, guest: true) ?? route('home'));
    }

    private function authPage(array $context, ?RunnerEmailChallenge $challenge = null): Response
    {
        return response()->view('site.runner.auth', [
            ...$context,
            'returnUrl' => $this->registrationUrl($context, guest: true),
            'challengeEmail' => $challenge
                ? Str::substr($challenge->email, 0, 1).'•••@'.Str::afterLast($challenge->email, '@')
                : null,
            'challengeExpiresAt' => $challenge?->expires_at,
            'resendAt' => $challenge?->resend_at,
        ])->header('Cache-Control', 'private, no-store');
    }

    private function redirectAfterLogin(array $context): RedirectResponse
    {
        return redirect()->to($this->registrationUrl($context) ?? route('runner.account'));
    }

    private function registrationUrl(array $context, bool $guest = false): ?string
    {
        $params = array_filter([
            'race' => $context['raceSlug'],
            'kit' => $context['kit'],
            'guest' => $guest ? 1 : null,
        ]);

        if ($context['event']) {
            return route('events.register', ['event' => $context['eventSlug'], ...$params]);
        }

        return $context['returnToRegistration'] ? route('register', $params) : null;
    }

    private function challengeContext(RunnerEmailChallenge $challenge): array
    {
        return $this->eventContext([
            'event' => $challenge->event_slug,
            'race' => $challenge->race_slug,
            'kit' => $challenge->kit,
            'return' => $challenge->return_to_registration ? 'registration' : null,
        ]);
    }

    private function eventContext(array $input): array
    {
        $event = null;
        $slug = $input['event'] ?? null;
        if (is_string($slug) && strlen($slug) <= 200 && $slug !== '') {
            $event = Event::query()->where('slug', $slug)
                ->where('is_published', true)->where('registration_enabled', true)->first();
        }

        $eventSlug = $event?->slug;
        $raceSlug = null;
        $race = $input['race'] ?? null;
        if ($event && is_string($race) && strlen($race) <= 120) {
            $raceSlug = $event->races()->where('is_active', true)->where('slug', $race)->value('slug');
        }

        $returnToRegistration = ($input['return'] ?? null) === 'registration';
        $kit = $input['kit'] ?? null;
        $kit = in_array($kit, ['full', 'basic'], true) ? $kit : null;

        return compact('event', 'eventSlug', 'raceSlug', 'returnToRegistration', 'kit');
    }

    private function contextParameters(array $context): array
    {
        return array_filter([
            'event' => $context['eventSlug'],
            'race' => $context['raceSlug'],
            'kit' => $context['kit'],
            'return' => $context['returnToRegistration'] ? 'registration' : null,
        ]);
    }

    private function sessionKey(Request $request): string
    {
        return $this->digest('session', $request->session()->getId());
    }

    private function codeHash(string $sessionKey, string $code): string
    {
        return $this->digest('code', $sessionKey."\0".$code);
    }

    private function digest(string $purpose, string $value): string
    {
        return hash_hmac('sha256', "runner:{$purpose}\0".$value, Crypt::getKey());
    }

    private function normalizeDigits(string $code): string
    {
        return strtr($code, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }
}
