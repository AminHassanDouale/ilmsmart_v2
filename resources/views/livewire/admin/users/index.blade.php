<?php

use App\Models\User;
use App\Enums\UserRole;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use WithPagination, Toast;

    public string $search = '';
    public string $role   = '';
    public string $status = '';

    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function with(): array
    {
        return [
            'users' => User::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"))
                ->when($this->role,   fn($q) => $q->where('role', $this->role))
                ->when($this->status, fn($q) => $q->where('status', $this->status))
                ->orderBy($this->sortBy['column'], $this->sortBy['direction'])
                ->paginate(20),

            'roles'     => UserRole::options(),
            'role_counts' => collect(UserRole::cases())->mapWithKeys(fn($r) => [
                $r->value => User::where('role', $r->value)->count()
            ]),

            'headers' => [
                ['key' => 'full_name', 'label' => __('lms.name'), 'sortable' => true],
                ['key' => 'role',      'label' => 'Role'],
                ['key' => 'phone',     'label' => __('lms.phone')],
                ['key' => 'language',  'label' => 'Lang'],
                ['key' => 'status',    'label' => __('lms.status')],
                ['key' => 'last_login_at', 'label' => 'Last Login'],
                ['key' => 'actions',   'label' => __('lms.actions'), 'class' => 'w-24'],
            ],
        ];
    }

    public function toggleStatus(User $user): void
    {
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
        $this->success('Status updated!');
    }

    public function impersonate(User $user): void
    {
        session(['impersonate_id' => $user->id]);
        $this->success('Impersonating ' . $user->name);
    }

    public function delete(User $user): void
    {
        $user->delete();
        $this->success('User deleted.');
    }
}; ?>

<div>
<x-header :title="__('lms.all_users')" separator>
        <x-slot:actions>
            <x-button :label="__('lms.add')" icon="o-plus" link="/admin/users/create" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    {{-- Role filter chips --}}
    <div class="flex flex-wrap gap-2 mb-4">
        <x-button wire:click="$set('role','')"
                  :label="'All (' . array_sum($role_counts->toArray()) . ')'"
                  :class="$role === '' ? 'btn-primary btn-sm' : 'btn-ghost btn-sm'" />
        @foreach(App\Enums\UserRole::cases() as $r)
            <x-button wire:click="$set('role','{{ $r->value }}')"
                      :label="$r->label() . ' (' . ($role_counts[$r->value] ?? 0) . ')'"
                      :class="$role === $r->value ? 'btn-primary btn-sm' : 'btn-ghost btn-sm'" />
        @endforeach
    </div>

    {{-- Search --}}
    <div class="flex gap-3 mb-4">
        <x-input wire:model.live.debounce="search" :placeholder="__('lms.search')"
                 icon="o-magnifying-glass" clearable class="flex-1" />
        <x-select wire:model.live="status"
                  :options="[
                      ['id'=>'', 'name'=>'All'],
                      ['id'=>'active', 'name'=> __('lms.active')],
                      ['id'=>'inactive', 'name'=> __('lms.inactive')],
                      ['id'=>'suspended', 'name'=>'Suspended'],
                  ]" class="w-36" />
    </div>

    <x-card shadow>
        <x-table :headers="$headers" :rows="$users" :sort-by="$sortBy" with-pagination striped>

            @scope('cell_full_name', $user)
                <x-list-item :item="$user" value="full_name" sub-value="email"
                             no-separator no-hover class="!p-0">
                    <x-slot:avatar>
                        <div class="avatar">
                            <div class="w-9 rounded-full">
                                <img src="{{ $user->avatar_url }}" alt="">
                            </div>
                        </div>
                    </x-slot:avatar>
                </x-list-item>
            @endscope

            @scope('cell_role', $user)
                @php $roleEnum = App\Enums\UserRole::from($user->role); @endphp
                <x-badge :value="$roleEnum->label()" :class="$roleEnum->color() . ' badge-soft'" />
            @endscope

            @scope('cell_language', $user)
                <span class="text-lg">
                    {{ match($user->language) { 'ar' => '🇩🇿', 'fr' => '🇫🇷', 'en' => '🇬🇧', default => '🌐' } }}
                </span>
            @endscope

            @scope('cell_status', $user)
                <x-toggle :value="$user->status === 'active'"
                          wire:click="toggleStatus({{ $user->id }})" />
            @endscope

            @scope('cell_last_login_at', $user)
                <span class="text-xs text-base-content/50">
                    {{ $user->last_login_at?->diffForHumans() ?? 'Never' }}
                </span>
            @endscope

            @scope('cell_actions', $user)
                <div class="flex gap-1">
                    <x-button icon="o-pencil-square" :link="'/admin/users/'.$user->id.'/edit'"
                              class="btn-ghost btn-xs" />
                    <x-button icon="o-trash"
                              wire:click="delete({{ $user->id }})"
                              wire:confirm="{{ __('lms.confirm') }}?"
                              class="btn-ghost btn-xs text-error" />
                </div>
            @endscope
        </x-table>
    </x-card>

</div>
