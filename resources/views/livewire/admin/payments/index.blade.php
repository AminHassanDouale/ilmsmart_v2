<?php

use App\Models\{Payment, User, Course};
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use WithPagination, Toast;

    public string $search  = '';
    public string $status  = '';
    public string $method  = '';
    public string $from    = '';
    public string $to      = '';
    public bool   $drawer  = false;
    public bool   $modal   = false;

    // New payment form
    public ?int    $form_user_id   = null;
    public float   $form_amount    = 0;
    public string  $form_method    = 'cash';
    public string  $form_currency  = 'DZD';
    public string  $form_notes     = '';
    public ?int    $form_course_id = null;

    public function with(): array
    {
        return [
            'payments' => Payment::query()
                ->with(['user', 'course'])
                ->when($this->search, fn($q) => $q->whereHas('user', fn($uq) =>
                    $uq->where('name', 'like', "%{$this->search}%")))
                ->when($this->status, fn($q) => $q->where('status', $this->status))
                ->when($this->method, fn($q) => $q->where('method', $this->method))
                ->when($this->from,   fn($q) => $q->whereDate('created_at', '>=', $this->from))
                ->when($this->to,     fn($q) => $q->whereDate('created_at', '<=', $this->to))
                ->latest()->paginate(15),

            'total_completed' => Payment::where('status','completed')->sum('amount'),
            'total_pending'   => Payment::where('status','pending')->sum('amount'),
            'count_today'     => Payment::whereDate('created_at', today())->count(),

            'users'    => User::orderBy('name')->get(['id','name','email']),
            'courses'  => Course::where('status','published')->get(['id','title']),

            'headers'  => [
                ['key' => 'payment_reference', 'label' => 'Ref #'],
                ['key' => 'user.name',         'label' => __('lms.students')],
                ['key' => 'amount',            'label' => __('lms.amount')],
                ['key' => 'method',            'label' => __('lms.payment_method')],
                ['key' => 'status',            'label' => __('lms.payment_status')],
                ['key' => 'paid_at',           'label' => __('lms.created_at')],
                ['key' => 'actions',           'label' => __('lms.actions'), 'class' => 'w-20'],
            ],
        ];
    }

    public function save(): void
    {
        $this->validate([
            'form_user_id' => 'required|exists:users,id',
            'form_amount'  => 'required|numeric|min:1',
            'form_method'  => 'required',
        ]);

        Payment::create([
            'user_id'   => $this->form_user_id,
            'amount'    => $this->form_amount,
            'currency'  => $this->form_currency,
            'method'    => $this->form_method,
            'course_id' => $this->form_course_id ?: null,
            'notes'     => $this->form_notes,
            'status'    => 'completed',
            'paid_at'   => now(),
            'received_by' => auth()->id(),
        ]);

        $this->success(__('lms.save') . ' — OK');
        $this->modal = false;
        $this->reset(['form_user_id','form_amount','form_method','form_notes','form_course_id']);
    }

    public function markPaid(Payment $payment): void
    {
        $payment->update(['status' => 'completed', 'paid_at' => now()]);
        $this->success('Marked as paid!');
    }
}; ?>

<div>
<x-header :title="__('lms.payments')" separator>
        <x-slot:actions>
            <x-button :label="__('lms.filters')" icon="o-funnel" @click="$wire.drawer = true" class="btn-ghost" />
            <x-button :label="__('lms.add')" icon="o-plus" wire:click="$set('modal', true)" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-stat title="Completed" :value="number_format($total_completed) . ' DA'"
                icon="o-check-circle" color="text-success" />
        <x-stat title="Pending" :value="number_format($total_pending) . ' DA'"
                icon="o-clock" color="text-warning" />
        <x-stat title="Today" :value="$count_today . ' payments'"
                icon="o-calendar-days" color="text-primary" />
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <x-input wire:model.live.debounce="search" :placeholder="__('lms.search')"
                 icon="o-magnifying-glass" clearable />
    </div>

    <x-card shadow>
        <x-table :headers="$headers" :rows="$payments" with-pagination striped>

            @scope('cell_amount', $payment)
                <span class="font-bold">{{ number_format($payment->amount) }}</span>
                <span class="text-xs text-base-content/50">{{ $payment->currency }}</span>
            @endscope

            @scope('cell_method', $payment)
                <x-badge :value="$payment->method" class="badge-soft badge-neutral capitalize" />
            @endscope

            @scope('cell_status', $payment)
                <x-badge :value="$payment->status"
                         class="{{ match($payment->status) {
                             'completed' => 'badge-success',
                             'pending'   => 'badge-warning',
                             'failed'    => 'badge-error',
                             default     => 'badge-neutral'
                         } }} badge-soft capitalize" />
            @endscope

            @scope('cell_paid_at', $payment)
                {{ $payment->paid_at?->format('d/m/Y H:i') ?? $payment->created_at->format('d/m/Y') }}
            @endscope

            @scope('cell_actions', $payment)
                @if($payment->status === 'pending')
                    <x-button icon="o-check" wire:click="markPaid({{ $payment->id }})"
                              class="btn-ghost btn-xs text-success" />
                @endif
            @endscope

        </x-table>
    </x-card>

    {{-- Add Payment Modal --}}
    <x-modal wire:model="modal" title="Add Payment" class="backdrop-blur">
        <x-form wire:submit="save">
            <x-choices :label="__('lms.students')" wire:model="form_user_id"
                       :options="$users" option-value="id" option-label="name"
                       option-sub-label="email" single />
            <x-input :label="__('lms.amount')" wire:model="form_amount"
                     type="number" min="0" prefix="DA" />
            <x-select :label="__('lms.payment_method')" wire:model="form_method"
                      :options="[
                          ['id'=>'cash',     'name'=>'Cash'],
                          ['id'=>'card',     'name'=>'Card'],
                          ['id'=>'transfer', 'name'=>'Transfer'],
                          ['id'=>'ccp',      'name'=>'CCP'],
                          ['id'=>'online',   'name'=>'Online'],
                      ]" />
            <x-choices label="Course (optional)" wire:model="form_course_id"
                       :options="$courses" option-value="id" option-label="title"
                       single clearable />
            <x-textarea :label="__('lms.notes') ?? 'Notes'" wire:model="form_notes" rows="2" />

            <x-slot:actions>
                <x-button :label="__('lms.cancel')" @click="$wire.modal = false" />
                <x-button :label="__('lms.save')" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    {{-- Filter Drawer --}}
    <x-drawer wire:model="drawer" :title="__('lms.filters')" right class="w-72">
        <div class="space-y-4">
            <x-select :label="__('lms.payment_status')" wire:model.live="status"
                      :options="[
                          ['id'=>'pending',   'name'=>'Pending'],
                          ['id'=>'completed', 'name'=>'Completed'],
                          ['id'=>'failed',    'name'=>'Failed'],
                      ]" placeholder="All" />
            <x-select :label="__('lms.payment_method')" wire:model.live="method"
                      :options="[
                          ['id'=>'cash',     'name'=>'Cash'],
                          ['id'=>'card',     'name'=>'Card'],
                          ['id'=>'transfer', 'name'=>'Transfer'],
                          ['id'=>'ccp',      'name'=>'CCP'],
                      ]" placeholder="All" />
            <x-datepicker :label="__('lms.created_at') . ' (from)'" wire:model.live="from" icon="o-calendar" />
            <x-datepicker :label="__('lms.created_at') . ' (to)'"   wire:model.live="to"   icon="o-calendar" />
        </div>
        <x-slot:actions>
            <x-button :label="__('lms.cancel')" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>

</div>
