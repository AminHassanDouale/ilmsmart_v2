<?php

use App\Models\Plan;
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use Toast;

    public bool  $modal   = false;
    public ?Plan $editing = null;

    public string $form_name          = '';
    public string $form_name_ar       = '';
    public string $form_name_fr       = '';
    public string $form_name_en       = '';
    public string $form_description   = '';
    public float  $form_price         = 0;
    public string $form_billing_cycle = 'monthly';
    public bool   $form_unlimited     = false;
    public ?int   $form_courses_limit = null;
    public bool   $form_live_classes  = false;
    public bool   $form_tutoring      = false;
    public bool   $form_is_active     = true;

    public function with(): array
    {
        return [
            'plans' => Plan::withCount('subscriptions')->orderBy('order')->get(),
        ];
    }

    public function create(): void
    {
        $this->reset(['form_name','form_name_ar','form_name_fr','form_name_en','form_description',
                      'form_price','form_billing_cycle','form_unlimited','form_courses_limit',
                      'form_live_classes','form_tutoring']);
        $this->form_is_active     = true;
        $this->form_billing_cycle = 'monthly';
        $this->editing = null;
        $this->modal = true;
    }

    public function edit(Plan $plan): void
    {
        $this->editing             = $plan;
        $this->form_name           = $plan->name;
        $this->form_name_ar        = $plan->name_ar ?? '';
        $this->form_name_fr        = $plan->name_fr ?? '';
        $this->form_name_en        = $plan->name_en ?? '';
        $this->form_description    = $plan->description ?? '';
        $this->form_price          = $plan->price;
        $this->form_billing_cycle  = $plan->billing_cycle;
        $this->form_unlimited      = $plan->unlimited_courses;
        $this->form_courses_limit  = $plan->courses_limit;
        $this->form_live_classes   = $plan->live_classes;
        $this->form_tutoring       = $plan->private_tutoring;
        $this->form_is_active      = $plan->is_active;
        $this->modal = true;
    }

    public function save(): void
    {
        $this->validate([
            'form_name'          => 'required|string|max:100',
            'form_price'         => 'required|numeric|min:0',
            'form_billing_cycle' => 'required',
        ]);

        $data = [
            'name'              => $this->form_name,
            'name_ar'           => $this->form_name_ar ?: null,
            'name_fr'           => $this->form_name_fr ?: null,
            'name_en'           => $this->form_name_en ?: null,
            'description'       => $this->form_description ?: null,
            'price'             => $this->form_price,
            'billing_cycle'     => $this->form_billing_cycle,
            'unlimited_courses' => $this->form_unlimited,
            'courses_limit'     => $this->form_unlimited ? null : $this->form_courses_limit,
            'live_classes'      => $this->form_live_classes,
            'private_tutoring'  => $this->form_tutoring,
            'is_active'         => $this->form_is_active,
        ];

        if ($this->editing) {
            $this->editing->update($data);
            $this->success('Plan updated!');
        } else {
            Plan::create($data);
            $this->success('Plan created!');
        }

        $this->modal = false;
    }

    public function delete(Plan $plan): void
    {
        if ($plan->subscriptions()->count() > 0) {
            $this->error('Cannot delete: plan has active subscriptions.');
            return;
        }
        $plan->delete();
        $this->success('Deleted.');
    }
}; ?>

<div>
<x-header :title="__('lms.plans')" separator>
        <x-slot:actions>
            <x-button :label="__('lms.create')" icon="o-plus" wire:click="create" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($plans as $plan)
            <x-card shadow class="{{ $plan->is_active ? '' : 'opacity-60' }} hover:shadow-lg transition-all">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="text-lg font-bold">{{ $plan->translated_name }}</h3>
                        <p class="text-xs text-base-content/60 capitalize">{{ $plan->billing_cycle }}</p>
                    </div>
                    <x-toggle :value="$plan->is_active" wire:click="$set('editing', {{ $plan->id }})" />
                </div>

                <div class="text-3xl font-black text-primary mb-4">
                    {{ number_format($plan->price) }} <span class="text-base font-normal text-base-content/60">DA</span>
                </div>

                <div class="space-y-2 text-sm mb-4">
                    <div class="flex items-center gap-2 {{ $plan->unlimited_courses ? 'text-success' : '' }}">
                        <x-icon name="{{ $plan->unlimited_courses ? 'o-check-circle' : 'o-x-circle' }}"
                                class="w-4 h-4" />
                        {{ $plan->unlimited_courses ? 'Unlimited courses' : ($plan->courses_limit . ' courses') }}
                    </div>
                    <div class="flex items-center gap-2 {{ $plan->live_classes ? 'text-success' : 'text-base-content/40' }}">
                        <x-icon name="{{ $plan->live_classes ? 'o-check-circle' : 'o-x-circle' }}"
                                class="w-4 h-4" />
                        Live classes
                    </div>
                    <div class="flex items-center gap-2 {{ $plan->private_tutoring ? 'text-success' : 'text-base-content/40' }}">
                        <x-icon name="{{ $plan->private_tutoring ? 'o-check-circle' : 'o-x-circle' }}"
                                class="w-4 h-4" />
                        Private tutoring
                    </div>
                </div>

                <x-badge :value="$plan->subscriptions_count . ' subscribers'" class="badge-soft badge-primary mb-3" />

                <div class="flex gap-2">
                    <x-button icon="o-pencil-square" wire:click="edit({{ $plan->id }})"
                              class="btn-ghost btn-sm flex-1" :label="__('lms.edit')" />
                    <x-button icon="o-trash" wire:click="delete({{ $plan->id }})"
                              wire:confirm="Delete?" class="btn-ghost btn-sm text-error" />
                </div>
            </x-card>
        @empty
            <div class="col-span-3">
                <x-icon name="o-star" label="No plans created yet" class="h-64" />
            </div>
        @endforelse
    </div>

    <x-modal wire:model="modal" :title="$editing ? 'Edit Plan' : 'Create Plan'" class="backdrop-blur max-w-2xl">
        <x-form wire:submit="save">
            <div class="grid grid-cols-2 gap-4">
                <x-input label="Name (Default)" wire:model="form_name"   required />
                <x-input label="Name (Arabic)"  wire:model="form_name_ar" />
                <x-input label="Name (French)"  wire:model="form_name_fr" />
                <x-input label="Name (English)" wire:model="form_name_en" />
            </div>
            <x-textarea label="Description" wire:model="form_description" rows="2" />
            <div class="grid grid-cols-2 gap-4">
                <x-input label="Price (DA)" wire:model="form_price" type="number" min="0" prefix="DA" />
                <x-select label="Billing Cycle" wire:model="form_billing_cycle"
                          :options="[
                              ['id'=>'monthly',   'name'=>'Monthly'],
                              ['id'=>'quarterly', 'name'=>'Quarterly'],
                              ['id'=>'yearly',    'name'=>'Yearly'],
                              ['id'=>'once',      'name'=>'One Time'],
                          ]" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <x-toggle label="Unlimited Courses" wire:model.live="form_unlimited" />
                @if(!$form_unlimited)
                    <x-input label="Courses Limit" wire:model="form_courses_limit" type="number" min="1" />
                @endif
            </div>
            <x-toggle label="Includes Live Classes"    wire:model="form_live_classes" />
            <x-toggle label="Includes Private Tutoring" wire:model="form_tutoring" />
            <x-toggle label="Active" wire:model="form_is_active" />

            <x-slot:actions>
                <x-button :label="__('lms.cancel')" @click="$wire.modal = false" />
                <x-button :label="__('lms.save')" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

</div>
