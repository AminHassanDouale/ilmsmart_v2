<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\{Layout, Rule, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.guest')]
#[Title('Login')]
class extends Component
{
    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|min:8')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Auth::user()->update(['last_login_at' => now()]);

        session()->regenerate();
        $this->redirect('/dashboard', navigate: true);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) return;

        event(new Lockout(request()));
        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}; ?>

<div>
    <x-card shadow class="rounded-2xl">
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold">{{ __('lms.welcome_back') }}</h2>
            <p class="text-sm text-base-content/60 mt-1">{{ __('lms.sign_in_subtitle') }}</p>
        </div>

        <x-form wire:submit="login">
            <x-input :label="__('lms.email_label')" wire:model="email"
                     type="email" icon="o-envelope" autofocus
                     :placeholder="__('lms.email')" />

            <x-password :label="__('lms.password')" wire:model="password"
                        clearable right />

            <div class="flex items-center justify-between">
                <x-checkbox :label="__('lms.remember_me')" wire:model="remember" />
                <a href="/forgot-password" class="text-sm text-primary hover:underline">
                    {{ __('lms.forgot_password') }}
                </a>
            </div>

            <x-slot:actions>
                <x-button :label="__('lms.login')" class="btn-primary w-full"
                          type="submit" spinner="login" />
            </x-slot:actions>
        </x-form>

        <div class="text-center mt-4 text-sm">
            {{ __('lms.no_account') }}
            <a href="/register" class="text-primary font-semibold hover:underline" wire:navigate>
                {{ __('lms.register') }}
            </a>
        </div>
    </x-card>

    {{-- Language switcher --}}
    <div class="flex justify-center gap-3 mt-4">
        @foreach(['fr' => '🇫🇷 FR', 'ar' => '🇩🇿 AR', 'en' => '🇬🇧 EN'] as $code => $label)
            <a href="/language/{{ $code }}"
               class="text-xs {{ app()->getLocale() === $code ? 'font-bold text-primary' : 'text-base-content/50 hover:text-base-content' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
