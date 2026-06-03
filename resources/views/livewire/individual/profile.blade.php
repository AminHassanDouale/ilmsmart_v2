<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

new
#[Layout('components.layouts.app')]
class extends Component {
    use WithFileUploads;

    public string $firstName = '';
    public string $lastName  = '';
    public string $email     = '';
    public string $phone     = '';
    public string $timezone  = '';
    public string $language  = 'en';
    public $avatar           = null;

    public string $currentPassword = '';
    public string $newPassword     = '';
    public string $confirmPassword = '';

    public function mount(): void
    {
        $u = auth()->user();
        $this->firstName = $u->first_name ?? '';
        $this->lastName  = $u->last_name ?? '';
        $this->email     = $u->email;
        $this->phone     = $u->phone ?? '';
        $this->timezone  = $u->timezone ?? 'Africa/Algiers';
        $this->language  = $u->language ?? 'en';
    }

    public function saveProfile(): void
    {
        $this->validate([
            'firstName' => 'required|min:2|max:100',
            'lastName'  => 'required|min:2|max:100',
            'email'     => 'required|email|unique:users,email,' . auth()->id(),
            'phone'     => 'nullable|string|max:30',
        ]);

        $data = [
            'first_name' => $this->firstName,
            'last_name'  => $this->lastName,
            'name'       => trim($this->firstName . ' ' . $this->lastName),
            'email'      => $this->email,
            'phone'      => $this->phone,
            'timezone'   => $this->timezone,
            'language'   => $this->language,
        ];

        if ($this->avatar) {
            $data['avatar'] = $this->avatar->store('avatars', 'public');
        }

        auth()->user()->update($data);
        $this->success('Profile updated.');
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword'     => 'required|min:8|confirmed:confirmPassword',
            'confirmPassword' => 'required',
        ]);

        $u = auth()->user();
        if (!\Hash::check($this->currentPassword, $u->password)) {
            $this->addError('currentPassword', 'Incorrect current password.');
            return;
        }

        $u->update(['password' => $this->newPassword]);
        $this->reset(['currentPassword','newPassword','confirmPassword']);
        $this->success('Password changed.');
    }
};
?>

<div>
    <x-header title="Profile" subtitle="Manage your personal information" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Avatar + summary --}}
        <div class="lg:col-span-1">
            <x-card class="text-center">
                <div class="avatar mx-auto mb-3">
                    <div class="w-24 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                        <img src="{{ auth()->user()->avatar_url }}" alt="">
                    </div>
                </div>
                <h3 class="font-bold">{{ auth()->user()->full_name }}</h3>
                <p class="text-sm text-base-content/60">{{ auth()->user()->email }}</p>
                <span class="badge badge-info badge-sm mt-2 capitalize">{{ auth()->user()->role }}</span>

                <div class="divider my-3"></div>

                <x-file label="Update Avatar" wire:model="avatar" accept="image/*" />
                @if($avatar)
                    <img src="{{ $avatar->temporaryUrl() }}" class="w-20 h-20 rounded-full object-cover mx-auto mt-2" alt="">
                @endif
            </x-card>
        </div>

        {{-- Forms --}}
        <div class="lg:col-span-2 space-y-4">
            <x-card title="Personal Information">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input label="First Name" wire:model="firstName" required />
                    <x-input label="Last Name" wire:model="lastName" required />
                    <x-input label="Email" wire:model="email" type="email" required />
                    <x-input label="Phone" wire:model="phone" />
                    <x-input label="Timezone" wire:model="timezone" />
                    <x-select label="Language" wire:model="language" :options="[
                        ['id'=>'en','name'=>'English'],
                        ['id'=>'ar','name'=>'العربية'],
                        ['id'=>'fr','name'=>'Français'],
                    ]" option-value="id" option-label="name" />
                </div>
                <x-slot:actions>
                    <x-button label="Save Changes" icon="o-check" wire:click="saveProfile" class="btn-primary" />
                </x-slot:actions>
            </x-card>

            <x-card title="Change Password">
                <div class="space-y-3">
                    <x-input label="Current Password" wire:model="currentPassword" type="password" />
                    <x-input label="New Password" wire:model="newPassword" type="password" />
                    <x-input label="Confirm New Password" wire:model="confirmPassword" type="password" />
                </div>
                <x-slot:actions>
                    <x-button label="Update Password" icon="o-key" wire:click="changePassword" class="btn-primary" />
                </x-slot:actions>
            </x-card>
        </div>
    </div>
</div>
