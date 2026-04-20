<?php
use App\Models\Payment;
use Livewire\Attributes\{Layout, Title};
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('Mes paiements')]
class extends Component {
    use WithPagination;

    public function with(): array {
        return [
            'payments' => Payment::where('user_id', auth()->id())->with('subscription.plan','course')->latest()->paginate(15),
            'total'    => Payment::where('user_id', auth()->id())->where('status','completed')->sum('amount'),
        ];
    }
} ?>

<div>
    <x-header title="Mes paiements" subtitle="Historique de vos paiements" separator />

    <x-stat title="Total payé" :value="number_format($total).' DZD'" icon="o-banknotes" color="text-success" class="mb-6 max-w-xs" />

    <x-card>
        <x-table :headers="[['key'=>'ref','label'=>'Référence'],['key'=>'description','label'=>'Description'],['key'=>'amount','label'=>'Montant'],['key'=>'status','label'=>'Statut'],['key'=>'paid_at','label'=>'Date']]" :rows="$payments">
            @scope('cell_ref', $p)
                <span class="font-mono text-xs">{{ $p->payment_reference }}</span>
            @endscope
            @scope('cell_description', $p)
                @if($p->subscription) Abonnement — {{ $p->subscription->plan->name }}
                @elseif($p->course) Cours — {{ $p->course->title }}
                @else Paiement @endif
            @endscope
            @scope('cell_amount', $p)
                <span class="font-semibold">{{ number_format($p->amount) }} {{ $p->currency }}</span>
            @endscope
            @scope('cell_status', $p)
                @php $colors = ['completed'=>'badge-success','pending'=>'badge-warning','failed'=>'badge-error','refunded'=>'badge-ghost']; @endphp
                <x-badge :value="$p->status" class="{{ $colors[$p->status] ?? 'badge-ghost' }}" />
            @endscope
            @scope('cell_paid_at', $p)
                {{ $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->format('d/m/Y') : ($p->created_at->format('d/m/Y')) }}
            @endscope
        </x-table>
        {{ $payments->links() }}
    </x-card>
</div>
