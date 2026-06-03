<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
class extends Component {
    use WithPagination;

    public string $filter = 'all';

    public function with(): array
    {
        $user = auth()->user();
        $query = $user->notifications();

        if ($this->filter === 'unread')   $query = $user->unreadNotifications();
        if ($this->filter === 'read')     $query = $user->readNotifications();

        return [
            'notifications' => $query->latest()->paginate(20),
            'unreadCount'   => $user->unreadNotifications()->count(),
            'totalCount'    => $user->notifications()->count(),
        ];
    }

    public function markRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->success('All notifications marked as read.');
    }

    public function delete(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->delete();
        $this->warning('Notification removed.');
    }

    public function clearAll(): void
    {
        auth()->user()->notifications()->delete();
        $this->warning('All notifications cleared.');
    }

    public function setFilter(string $f): void
    {
        $this->filter = $f;
        $this->resetPage();
    }
};
?>

<div>
    <x-header title="Notifications" subtitle="Your activity and updates">
        <x-slot:actions>
            <x-button label="Mark all read" icon="o-check" wire:click="markAllRead"
                      class="btn-ghost btn-sm" :disabled="$unreadCount === 0" />
            <x-button label="Clear all" icon="o-trash" wire:click="clearAll"
                      wire:confirm="Delete all notifications permanently?"
                      class="btn-ghost btn-sm text-error" :disabled="$totalCount === 0" />
        </x-slot:actions>
    </x-header>

    {{-- Filter tabs --}}
    <div class="flex gap-2 mb-6 flex-wrap">
        <button wire:click="setFilter('all')"
                @class(['btn btn-sm', 'btn-primary' => $filter === 'all', 'btn-ghost' => $filter !== 'all'])>
            All
            <span class="badge badge-sm ml-1">{{ $totalCount }}</span>
        </button>
        <button wire:click="setFilter('unread')"
                @class(['btn btn-sm', 'btn-primary' => $filter === 'unread', 'btn-ghost' => $filter !== 'unread'])>
            Unread
            @if($unreadCount > 0)
                <span class="badge badge-sm badge-error ml-1">{{ $unreadCount }}</span>
            @endif
        </button>
        <button wire:click="setFilter('read')"
                @class(['btn btn-sm', 'btn-primary' => $filter === 'read', 'btn-ghost' => $filter !== 'read'])>
            Read
        </button>
    </div>

    {{-- List --}}
    @if($notifications->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-bell-slash" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No notifications</p>
            <p class="text-sm text-base-content/40">You're all caught up!</p>
        </x-card>
    @else
        <div class="space-y-2">
            @foreach($notifications as $n)
                @php $d = $n->data; @endphp
                <x-card @class([
                    'group transition-all hover:shadow-md',
                    '!bg-primary/5 !border-primary/20' => !$n->read_at,
                ])>
                    <div class="flex items-start gap-3 sm:gap-4">
                        {{-- Icon --}}
                        <div @class([
                            'w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shrink-0',
                            'bg-primary/10 text-primary' => !$n->read_at,
                            'bg-base-200 text-base-content/60' => $n->read_at,
                        ])>
                            <x-icon name="{{ $d['icon'] ?? 'o-bell' }}" class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 flex-wrap">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-sm sm:text-base">{{ $d['title'] ?? 'Notification' }}</p>
                                    <p class="text-xs sm:text-sm text-base-content/60 mt-1">{{ $d['message'] ?? '' }}</p>
                                </div>
                                @if(!$n->read_at)
                                    <span class="badge badge-primary badge-xs shrink-0">New</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-3 mt-2 flex-wrap">
                                <span class="text-xs text-base-content/40">{{ $n->created_at->diffForHumans() }}</span>
                                @if(isset($d['progress']))
                                    <span class="badge badge-success badge-xs">{{ $d['progress'] }}% complete</span>
                                @endif
                                @if(isset($d['url']))
                                    <a href="{{ $d['url'] }}" wire:click="markRead('{{ $n->id }}')"
                                       class="text-xs text-primary hover:underline font-medium">
                                        View →
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-col gap-1 shrink-0">
                            @if(!$n->read_at)
                                <button wire:click="markRead('{{ $n->id }}')"
                                        class="btn btn-ghost btn-xs" title="Mark as read">
                                    <x-icon name="o-check" class="w-4 h-4" />
                                </button>
                            @endif
                            <button wire:click="delete('{{ $n->id }}')"
                                    class="btn btn-ghost btn-xs text-error" title="Delete">
                                <x-icon name="o-trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</div>
