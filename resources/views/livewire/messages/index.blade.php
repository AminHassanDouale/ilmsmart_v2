<?php

use App\Models\{Conversation, Message, User};
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use Toast;

    public ?int    $activeConversationId = null;
    public string  $newMessage  = '';
    public string  $searchUser  = '';
    public bool    $newChatModal = false;
    public ?int    $recipientId  = null;

    public function mount(): void
    {
        $first = auth()->user()->conversations()->latest('updated_at')->first();
        $this->activeConversationId = $first?->id;
    }

    public function with(): array
    {
        $conversations = auth()->user()->conversations()
            ->with(['lastMessage.user', 'participants'])
            ->orderByDesc('updated_at')
            ->get();

        $activeConversation = null;
        $messages           = collect();

        if ($this->activeConversationId) {
            $activeConversation = Conversation::with(['participants'])->find($this->activeConversationId);
            $messages = Message::where('conversation_id', $this->activeConversationId)
                ->with('user')
                ->orderBy('created_at')
                ->get();

            // Mark as read
            $activeConversation?->participants()->updateExistingPivot(auth()->id(), [
                'last_read_at' => now(),
            ]);
        }

        $users = User::where('id', '!=', auth()->id())
            ->when($this->searchUser, fn($q) => $q->where('name', 'like', "%{$this->searchUser}%"))
            ->take(10)
            ->get(['id','name','email','avatar']);

        return compact('conversations', 'activeConversation', 'messages', 'users');
    }

    public function openConversation(int $id): void
    {
        $this->activeConversationId = $id;
    }

    public function sendMessage(): void
    {
        $this->validate(['newMessage' => 'required|string|max:2000']);

        if (!$this->activeConversationId) return;

        Message::create([
            'conversation_id' => $this->activeConversationId,
            'user_id'         => auth()->id(),
            'body'            => $this->newMessage,
        ]);

        $this->reset('newMessage');
    }

    public function startConversation(): void
    {
        if (!$this->recipientId) return;

        // Check if conversation already exists
        $existing = auth()->user()->conversations()
            ->whereHas('participants', fn($q) => $q->where('user_id', $this->recipientId))
            ->where('type', 'direct')
            ->first();

        if ($existing) {
            $this->activeConversationId = $existing->id;
        } else {
            $conv = Conversation::create(['type' => 'direct']);
            $conv->participants()->attach([auth()->id(), $this->recipientId]);
            $this->activeConversationId = $conv->id;
        }

        $this->newChatModal = false;
        $this->success('Conversation started!');
    }
}; ?>

<div>
<x-header :title="__('lms.messages')" separator>
        <x-slot:actions>
            <x-button icon="o-plus" label="New Chat" wire:click="$set('newChatModal', true)" class="btn-primary" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 h-[70vh]">

        {{-- Conversations List --}}
        <x-card shadow class="lg:col-span-1 overflow-y-auto">
            <div class="space-y-1">
                @forelse($conversations as $conv)
                    @php
                        $other = $conv->participants->where('id', '!=', auth()->id())->first();
                        $unread = $conv->unreadCount(auth()->id());
                    @endphp
                    <div wire:click="openConversation({{ $conv->id }})"
                         class="flex items-center gap-3 p-3 rounded-xl cursor-pointer hover:bg-base-200 transition-colors
                                {{ $activeConversationId === $conv->id ? 'bg-primary/10 border border-primary/20' : '' }}">
                        <div class="avatar">
                            <div class="w-10 rounded-full">
                                <img src="{{ $other?->avatar_url ?? '' }}" alt="">
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between">
                                <p class="font-semibold text-sm truncate">{{ $other?->full_name ?? 'Group' }}</p>
                                <span class="text-xs text-base-content/40">{{ $conv->lastMessage?->created_at?->format('H:i') }}</span>
                            </div>
                            <p class="text-xs text-base-content/60 truncate">{{ $conv->lastMessage?->body }}</p>
                        </div>
                        @if($unread > 0)
                            <x-badge :value="$unread" class="badge-primary badge-sm" />
                        @endif
                    </div>
                @empty
                    <p class="text-center text-base-content/40 py-8">No conversations yet</p>
                @endforelse
            </div>
        </x-card>

        {{-- Message Area --}}
        <div class="lg:col-span-2 flex flex-col">
            @if($activeConversation)
                @php $other = $activeConversation->participants->where('id', '!=', auth()->id())->first(); @endphp

                {{-- Header --}}
                <x-card shadow class="mb-2">
                    <div class="flex items-center gap-3">
                        <div class="avatar">
                            <div class="w-10 rounded-full">
                                <img src="{{ $other?->avatar_url ?? '' }}" alt="">
                            </div>
                        </div>
                        <div>
                            <p class="font-bold">{{ $other?->full_name }}</p>
                            <p class="text-xs text-base-content/60 capitalize">{{ $other?->role }}</p>
                        </div>
                    </div>
                </x-card>

                {{-- Messages --}}
                <x-card shadow class="flex-1 overflow-y-auto mb-2" id="message-area">
                    <div class="space-y-3">
                        @foreach($messages as $msg)
                            <div class="chat {{ $msg->user_id === auth()->id() ? 'chat-end' : 'chat-start' }}">
                                <div class="chat-image avatar">
                                    <div class="w-8 rounded-full">
                                        <img src="{{ $msg->user->avatar_url }}" alt="">
                                    </div>
                                </div>
                                <div class="chat-bubble {{ $msg->user_id === auth()->id() ? 'chat-bubble-primary' : '' }}">
                                    {{ $msg->body }}
                                </div>
                                <div class="chat-footer text-xs opacity-50">
                                    {{ $msg->created_at->format('H:i') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                {{-- Input --}}
                <x-card shadow>
                    <div class="flex gap-2">
                        <x-input wire:model="newMessage" placeholder="Type a message..."
                                 class="flex-1" wire:keydown.enter="sendMessage" />
                        <x-button icon="o-paper-airplane" wire:click="sendMessage"
                                  class="btn-primary" spinner="sendMessage" />
                    </div>
                </x-card>
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-base-content/40">
                    <x-icon name="o-chat-bubble-left-right" class="w-16 h-16 mb-2" />
                    <p>Select a conversation</p>
                </div>
            @endif
        </div>
    </div>

    {{-- New Chat Modal --}}
    <x-modal wire:model="newChatModal" title="New Conversation" class="backdrop-blur">
        <x-input wire:model.live.debounce="searchUser" placeholder="Search users..."
                 icon="o-magnifying-glass" class="mb-4" />
        <div class="space-y-2">
            @foreach($users as $user)
                <div wire:click="$set('recipientId', {{ $user->id }})"
                     class="flex items-center gap-3 p-3 rounded-xl cursor-pointer hover:bg-base-200
                            {{ $recipientId === $user->id ? 'bg-primary/10 border border-primary/20' : '' }}">
                    <div class="avatar">
                        <div class="w-9 rounded-full">
                            <img src="{{ $user->avatar_url }}" alt="">
                        </div>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">{{ $user->full_name }}</p>
                        <p class="text-xs text-base-content/60">{{ $user->email }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.newChatModal = false" />
            <x-button label="Start Chat" class="btn-primary" wire:click="startConversation"
                      :disabled="!$recipientId" spinner="startConversation" />
        </x-slot:actions>
    </x-modal>

</div>
