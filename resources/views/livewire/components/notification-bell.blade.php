<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {

    public function notifications(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        if (!$user) return collect();

        return $user->notifications()->latest()->limit(10)->get();
    }

    public function unreadCount(): int
    {
        return auth()->user()?->unreadNotifications()->count() ?? 0;
    }

    public function markRead(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        auth()->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function clear(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->delete();
    }

    #[On('notification-received')]
    public function refresh(): void
    {
        // Triggered by other components to refresh the bell
    }

    public function timeAgo($date): string
    {
        return \Carbon\Carbon::parse($date)->diffForHumans(null, true);
    }
};
?>

<div wire:poll.30s>
    @php $count = $this->unreadCount(); $items = $this->notifications(); @endphp

    <x-dropdown right>
        <x-slot:trigger>
            <button class="btn btn-ghost btn-circle relative" aria-label="Notifications">
                <x-icon name="o-bell" class="w-5 h-5" />
                @if($count > 0)
                    <span class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-error text-[10px] font-bold text-white px-1">
                        {{ $count > 9 ? '9+' : $count }}
                    </span>
                @endif
            </button>
        </x-slot:trigger>

        <div class="w-80 sm:w-96 max-w-[calc(100vw-2rem)] -mx-2 -my-2">
            {{-- Header --}}
            <div class="flex items-center justify-between p-3 border-b border-base-300">
                <div class="flex items-center gap-2">
                    <x-icon name="o-bell" class="w-4 h-4" />
                    <span class="font-semibold text-sm">Notifications</span>
                    @if($count > 0)
                        <span class="badge badge-primary badge-sm">{{ $count }}</span>
                    @endif
                </div>
                @if($count > 0)
                    <button wire:click="markAllRead" class="text-xs text-primary hover:underline">Mark all read</button>
                @endif
            </div>

            {{-- List --}}
            <div class="max-h-96 overflow-y-auto">
                @forelse($items as $n)
                    @php $d = $n->data; @endphp
                    <div @class([
                        'group flex items-start gap-3 p-3 hover:bg-base-200 transition-colors border-b border-base-200/50',
                        'bg-primary/5' => !$n->read_at,
                    ])>
                        {{-- Icon --}}
                        <div @class([
                            'w-9 h-9 rounded-lg flex items-center justify-center shrink-0',
                            'bg-primary/10 text-primary' => !$n->read_at,
                            'bg-base-200 text-base-content/60' => $n->read_at,
                        ])>
                            <x-icon name="{{ $d['icon'] ?? 'o-bell' }}" class="w-5 h-5" />
                        </div>

                        {{-- Content --}}
                        <a href="{{ $d['url'] ?? '#' }}"
                           wire:click="markRead('{{ $n->id }}')"
                           class="flex-1 min-w-0">
                            <p class="text-sm font-medium leading-tight">{{ $d['title'] ?? 'Notification' }}</p>
                            <p class="text-xs text-base-content/60 mt-0.5 line-clamp-2">{{ $d['message'] ?? '' }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] text-base-content/40">{{ $this->timeAgo($n->created_at) }} ago</span>
                                @if(isset($d['progress']))
                                    <span class="text-[10px] text-success font-semibold">{{ $d['progress'] }}%</span>
                                @endif
                            </div>
                        </a>

                        {{-- Clear button --}}
                        <button wire:click="clear('{{ $n->id }}')"
                                class="opacity-0 group-hover:opacity-100 text-base-content/40 hover:text-error transition-opacity shrink-0"
                                aria-label="Clear notification">
                            <x-icon name="o-x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <x-icon name="o-bell-slash" class="w-10 h-10 mx-auto text-base-content/20 mb-2" />
                        <p class="text-sm text-base-content/40">No notifications yet</p>
                    </div>
                @endforelse
            </div>

            {{-- Footer --}}
            @if($items->isNotEmpty())
                <div class="p-2 border-t border-base-300 text-center">
                    <a href="/notifications" class="text-xs text-primary hover:underline">View all</a>
                </div>
            @endif
        </div>
    </x-dropdown>
</div>
