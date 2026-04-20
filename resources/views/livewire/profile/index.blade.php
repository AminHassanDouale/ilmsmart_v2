<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use WithFileUploads, Toast;

    public string  $first_name = '';
    public string  $last_name  = '';
    public string  $email      = '';
    public string  $phone      = '';
    public string  $language   = 'fr';
    public ?string $password   = null;
    public ?string $password_confirmation = null;
    public $avatar_file = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->first_name = $user->first_name ?? '';
        $this->last_name  = $user->last_name ?? '';
        $this->email      = $user->email;
        $this->phone      = $user->phone ?? '';
        $this->language   = $user->language ?? 'fr';
    }

    public function save(): void
    {
        $user = auth()->user();

        $this->validate([
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'phone'       => 'nullable|string|max:20',
            'language'    => 'in:fr,ar,en',
            'password'    => 'nullable|min:8|confirmed',
            'avatar_file' => 'nullable|image|max:2048',
        ]);

        $data = [
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'name'       => $this->first_name . ' ' . $this->last_name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'language'   => $this->language,
        ];

        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        if ($this->avatar_file) {
            $path = $this->avatar_file->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);
        session(['locale' => $this->language]);

        $this->success('Profile saved!');
        $this->reset(['password', 'password_confirmation', 'avatar_file']);
    }
}; ?>

<div>
<x-header :title="__('lms.profile')" separator />

    <div class="max-w-2xl mx-auto">
        <x-card shadow>
            <x-form wire:submit="save">

                {{-- Avatar --}}
                <div class="flex justify-center mb-4">
                    <x-file wire:model="avatar_file" accept="image/png,image/jpeg" crop-title-text="Crop Avatar">
                        <div class="avatar cursor-pointer">
                            <div class="w-24 rounded-full ring ring-primary ring-offset-2">
                                <img src="{{ auth()->user()->avatar_url }}" alt="">
                            </div>
                        </div>
                    </x-file>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input :label="__('lms.name') . ' (Prénom)'" wire:model="first_name" required />
                    <x-input :label="__('lms.name') . ' (Nom)'"    wire:model="last_name"  required />
                </div>

                <x-input :label="__('lms.email_label')" wire:model="email" type="email" icon="o-envelope" required />
                <x-input :label="__('lms.phone')" wire:model="phone" icon="o-phone" />

                <x-select :label="__('lms.language')" wire:model="language"
                          :options="[
                              ['id'=>'fr', 'name'=>'🇫🇷 Français'],
                              ['id'=>'ar', 'name'=>'🇩🇿 العربية'],
                              ['id'=>'en', 'name'=>'🇬🇧 English'],
                          ]" />

                <div class="divider text-sm">Change Password (optional)</div>

                <x-password :label="__('lms.password')" wire:model="password" clearable />
                <x-password label="Confirm Password"    wire:model="password_confirmation" only-password />

                <x-slot:actions>
                    <x-button :label="__('lms.save')" class="btn-primary w-full" type="submit" spinner="save" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>

</div>
