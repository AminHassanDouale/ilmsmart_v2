<?php

use App\Models\{User, Student, ParentModel};
use Illuminate\Auth\Events\Registered;
use Livewire\Attributes\{Layout, Rule, Title};
use Livewire\Volt\Component;

new
#[Layout('components.layouts.guest')]
#[Title('Register')]
class extends Component
{
    #[Rule('required|string|max:100')]
    public string $first_name = '';

    #[Rule('required|string|max:100')]
    public string $last_name = '';

    #[Rule('required|email|unique:users,email')]
    public string $email = '';

    #[Rule('nullable|string|max:20')]
    public string $phone = '';

    #[Rule('required|min:8|confirmed')]
    public string $password = '';

    #[Rule('required')]
    public string $password_confirmation = '';

    #[Rule('required|in:student,parent,tutor')]
    public string $role = 'student';

    #[Rule('in:fr,ar,en')]
    public string $language = 'fr';

    public function register(): void
    {
        $this->validate();

        $user = User::create([
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'name'       => $this->first_name . ' ' . $this->last_name,
            'email'      => $this->email,
            'phone'      => $this->phone ?: null,
            'password'   => bcrypt($this->password),
            'role'       => $this->role,
            'language'   => $this->language,
            'status'     => 'active',
        ]);

        // Create profile based on role
        if ($this->role === 'student') {
            Student::create([
                'user_id'        => $user->id,
                'student_number' => 'STU-' . strtoupper(uniqid()),
                'student_type'   => 'individual',
            ]);
        } elseif ($this->role === 'parent') {
            ParentModel::create(['user_id' => $user->id]);
        }

        event(new Registered($user));

        auth()->login($user);
        session(['locale' => $this->language]);

        $this->redirect('/dashboard', navigate: true);
    }
}; ?>

<div>
    <x-card shadow class="rounded-2xl">
        <div class="text-center mb-6">
            <h2 class="text-xl font-bold">{{ __('lms.register') }}</h2>
            <p class="text-sm text-base-content/60 mt-1">Create your account to get started</p>
        </div>

        <x-form wire:submit="register">
            <div class="grid grid-cols-2 gap-3">
                <x-input :label="__('lms.name') . ' (Prénom)'" wire:model="first_name"
                         placeholder="Mohamed" required />
                <x-input :label="__('lms.name') . ' (Nom)'" wire:model="last_name"
                         placeholder="Benali" required />
            </div>

            <x-input :label="__('lms.email_label')" wire:model="email"
                     type="email" icon="o-envelope" placeholder="email@example.com" required />

            <x-input :label="__('lms.phone')" wire:model="phone"
                     icon="o-phone" placeholder="0555 00 00 00" />

            <x-select :label="'I am a...'" wire:model="role"
                      :options="[
                          ['id'=>'student', 'name'=> '🎓 ' . __('lms.role_student')],
                          ['id'=>'parent',  'name'=> '👨‍👩‍👧 ' . __('lms.role_parent')],
                          ['id'=>'tutor',   'name'=> '💡 ' . __('lms.role_tutor')],
                      ]" />

            <x-select :label="__('lms.language')" wire:model="language"
                      :options="[
                          ['id'=>'fr', 'name'=>'🇫🇷 Français'],
                          ['id'=>'ar', 'name'=>'🇩🇿 العربية'],
                          ['id'=>'en', 'name'=>'🇬🇧 English'],
                      ]" />

            <x-password :label="__('lms.password')" wire:model="password" clearable right />
            <x-password label="Confirm Password" wire:model="password_confirmation" only-password />

            <x-slot:actions>
                <x-button :label="__('lms.register')" class="btn-primary w-full"
                          type="submit" spinner="register" />
            </x-slot:actions>
        </x-form>

        <div class="text-center mt-4 text-sm">
            {{ __('lms.have_account') }}
            <a href="/login" class="text-primary font-semibold hover:underline" wire:navigate>
                {{ __('lms.login') }}
            </a>
        </div>
    </x-card>

    <div class="flex justify-center gap-3 mt-4">
        @foreach(['fr' => '🇫🇷 FR', 'ar' => '🇩🇿 AR', 'en' => '🇬🇧 EN'] as $code => $label)
            <a href="/language/{{ $code }}"
               class="text-xs {{ app()->getLocale() === $code ? 'font-bold text-primary' : 'text-base-content/50 hover:text-base-content' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
