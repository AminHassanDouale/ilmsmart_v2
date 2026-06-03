<?php

use App\Models\Program;
use App\Models\ProgramSession;
use App\Models\Teacher;
use Livewire\Volt\Component;

new class extends Component {
    public Program $program;

    public bool $showModal = false;
    public ?int $editId = null;

    public string $title         = '';
    public string $description   = '';
    public string $dayOfWeek     = 'mon';
    public ?string $startTime    = null;
    public ?string $endTime      = null;
    public ?string $specificDate = null;
    public string $recurrence    = 'weekly';
    public string $location      = '';
    public string $meetingLink   = '';
    public ?int   $teacherId     = null;

    public function mount(Program $program): void
    {
        $this->program = $program;
    }

    public function sessions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->program->sessions()->with('teacher.user')->get();
    }

    public function teachers(): \Illuminate\Database\Eloquent\Collection
    {
        return Teacher::with('user')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['title','description','dayOfWeek','startTime','endTime',
                      'specificDate','recurrence','location','meetingLink','teacherId','editId']);
        $this->dayOfWeek  = 'mon';
        $this->recurrence = $this->program->schedule_type === 'daily' ? 'daily' : 'weekly';
        $this->showModal  = true;
    }

    public function openEdit(int $id): void
    {
        $s = ProgramSession::findOrFail($id);
        $this->editId       = $id;
        $this->title        = $s->title;
        $this->description  = $s->description ?? '';
        $this->dayOfWeek    = $s->day_of_week ?? 'mon';
        $this->startTime    = $s->start_time ? substr($s->start_time, 0, 5) : null;
        $this->endTime      = $s->end_time ? substr($s->end_time, 0, 5) : null;
        $this->specificDate = $s->specific_date?->format('Y-m-d');
        $this->recurrence   = $s->recurrence;
        $this->location     = $s->location ?? '';
        $this->meetingLink  = $s->meeting_link ?? '';
        $this->teacherId    = $s->teacher_id;
        $this->showModal    = true;
    }

    public function save(): void
    {
        $this->validate([
            'title'      => 'required|min:2|max:255',
            'recurrence' => 'required|in:once,daily,weekly,biweekly,monthly',
        ]);

        $data = [
            'program_id'    => $this->program->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'day_of_week'   => $this->recurrence === 'once' ? null : $this->dayOfWeek,
            'start_time'    => $this->startTime,
            'end_time'      => $this->endTime,
            'specific_date' => $this->recurrence === 'once' ? $this->specificDate : null,
            'recurrence'    => $this->recurrence,
            'location'      => $this->location,
            'meeting_link'  => $this->meetingLink,
            'teacher_id'    => $this->teacherId,
        ];

        if ($this->editId) {
            ProgramSession::findOrFail($this->editId)->update($data);
            $this->success('Session updated.');
        } else {
            $data['order'] = ProgramSession::where('program_id', $this->program->id)->max('order') + 1;
            ProgramSession::create($data);
            $this->success('Session added.');
        }

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        ProgramSession::findOrFail($id)->delete();
        $this->warning('Session deleted.');
    }

    public array $days = [
        'mon' => 'Monday',  'tue' => 'Tuesday',  'wed' => 'Wednesday',
        'thu' => 'Thursday','fri' => 'Friday',   'sat' => 'Saturday', 'sun' => 'Sunday',
    ];
};
?>

<div>
    <x-header :title="'Sessions: ' . $program->title" subtitle="Weekly / daily schedule for this program">
        <x-slot:middle>
            <div class="flex items-center gap-2 text-sm text-base-content/50">
                <a href="{{ route('admin.programs') }}" wire:navigate class="hover:text-primary">Programs</a>
                <x-icon name="o-chevron-right" class="w-4 h-4" />
                <span class="truncate max-w-xs">{{ $program->title }}</span>
            </div>
        </x-slot:middle>
        <x-slot:actions>
            <a href="{{ route('admin.programs.courses', $program) }}" wire:navigate class="btn btn-sm">
                <x-icon name="o-book-open" class="w-4 h-4" /> Courses
            </a>
            <x-button label="Add Session" icon="o-plus" wire:click="openCreate" class="btn-primary" responsive />
        </x-slot:actions>
    </x-header>

    @php $sessions = $this->sessions(); @endphp

    {{-- Weekly grid view --}}
    @if($sessions->isEmpty())
        <x-card class="text-center py-16">
            <x-icon name="o-calendar" class="w-16 h-16 mx-auto text-base-content/20 mb-4" />
            <p class="text-base-content/50 text-lg">No sessions scheduled.</p>
            <x-button label="Add First Session" icon="o-plus" wire:click="openCreate" class="btn-primary mt-4" />
        </x-card>
    @else
        {{-- Calendar-style grid on desktop, list on mobile --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-3 mb-6">
            @foreach($days as $key => $name)
                @php $daySessions = $sessions->where('day_of_week', $key); @endphp
                <div class="border border-base-300 rounded-xl p-3 min-h-[150px]">
                    <h4 class="font-bold text-xs sm:text-sm mb-2 text-center {{ $daySessions->isNotEmpty() ? 'text-primary' : 'text-base-content/40' }}">
                        {{ strtoupper(substr($name, 0, 3)) }}
                    </h4>
                    <div class="space-y-1.5">
                        @foreach($daySessions as $s)
                            <div class="bg-primary/10 rounded-lg p-2 border border-primary/20 cursor-pointer hover:bg-primary/20 transition-colors"
                                 wire:click="openEdit({{ $s->id }})">
                                <p class="text-xs font-semibold leading-tight line-clamp-2">{{ $s->title }}</p>
                                <p class="text-[10px] text-base-content/60 mt-0.5">{{ $s->time_range }}</p>
                                @if($s->meeting_link)
                                    <p class="text-[10px] text-info mt-0.5 flex items-center gap-0.5">
                                        <x-icon name="o-video-camera" class="w-3 h-3" /> Online
                                    </p>
                                @endif
                            </div>
                        @endforeach
                        @if($daySessions->isEmpty())
                            <p class="text-[10px] text-base-content/30 text-center italic">—</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Full list with actions --}}
        <h3 class="font-semibold mb-3 mt-8">All Sessions</h3>
        <div class="space-y-2">
            @foreach($sessions as $s)
                <x-card class="!p-3 sm:!p-4">
                    <div class="flex items-start gap-3 flex-wrap sm:flex-nowrap">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex flex-col items-center justify-center shrink-0">
                            <span class="text-[10px] text-base-content/60 uppercase">{{ $s->day_label ? substr($s->day_label, 0, 3) : 'Once' }}</span>
                            <span class="text-sm font-bold text-primary">{{ $s->start_time ? substr($s->start_time, 0, 5) : '—' }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm sm:text-base">{{ $s->title }}</p>
                            <div class="flex items-center flex-wrap gap-2 mt-1 text-xs text-base-content/60">
                                <span class="capitalize">{{ $s->recurrence }}</span>
                                @if($s->day_label)
                                    <span>· {{ $s->day_label }}</span>
                                @endif
                                @if($s->start_time)
                                    <span>· {{ $s->time_range }}</span>
                                @endif
                                @if($s->location)
                                    <span class="flex items-center gap-0.5"><x-icon name="o-map-pin" class="w-3 h-3" /> {{ $s->location }}</span>
                                @endif
                                @if($s->teacher)
                                    <span class="flex items-center gap-0.5"><x-icon name="o-user" class="w-3 h-3" /> {{ $s->teacher->user->full_name }}</span>
                                @endif
                            </div>
                            @if($s->meeting_link)
                                <a href="{{ $s->meeting_link }}" target="_blank"
                                   class="text-xs text-info hover:underline flex items-center gap-1 mt-1">
                                    <x-icon name="o-video-camera" class="w-3 h-3" /> Join meeting
                                </a>
                            @endif
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <x-button icon="o-pencil" wire:click="openEdit({{ $s->id }})" class="btn-ghost btn-xs sm:btn-sm" />
                            <x-button icon="o-trash" wire:click="delete({{ $s->id }})"
                                      wire:confirm="Delete this session?"
                                      class="btn-ghost btn-xs sm:btn-sm text-error" />
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    {{-- Modal --}}
    <x-modal wire:model="showModal" title="{{ $editId ? 'Edit Session' : 'New Session' }}" max-width="2xl" class="backdrop-blur">
        <div class="space-y-4">
            <x-input label="Session Title" wire:model="title" placeholder="e.g. Tajweed Class" required />
            <x-textarea label="Description (optional)" wire:model="description" rows="2" />

            <x-select label="Recurrence" wire:model.live="recurrence" :options="[
                ['id'=>'once','name'=>'One-time (specific date)'],
                ['id'=>'daily','name'=>'Daily'],
                ['id'=>'weekly','name'=>'Weekly'],
                ['id'=>'biweekly','name'=>'Bi-weekly'],
                ['id'=>'monthly','name'=>'Monthly'],
            ]" option-value="id" option-label="name" />

            @if($recurrence === 'once')
                <x-input label="Date" wire:model="specificDate" type="date" />
            @else
                <x-select label="Day of Week" wire:model="dayOfWeek" :options="[
                    ['id'=>'mon','name'=>'Monday'],
                    ['id'=>'tue','name'=>'Tuesday'],
                    ['id'=>'wed','name'=>'Wednesday'],
                    ['id'=>'thu','name'=>'Thursday'],
                    ['id'=>'fri','name'=>'Friday'],
                    ['id'=>'sat','name'=>'Saturday'],
                    ['id'=>'sun','name'=>'Sunday'],
                ]" option-value="id" option-label="name" />
            @endif

            <div class="grid grid-cols-2 gap-4">
                <x-input label="Start Time" wire:model="startTime" type="time" />
                <x-input label="End Time" wire:model="endTime" type="time" />
            </div>

            <x-input label="Location" wire:model="location" placeholder="e.g. Room 101 or Online" />
            <x-input label="Meeting Link" wire:model="meetingLink" placeholder="https://zoom.us/j/..." />

            @php $teachers = $this->teachers(); @endphp
            @if($teachers->isNotEmpty())
                <x-select label="Teacher" wire:model="teacherId" placeholder="Select teacher..."
                    :options="$teachers->map(fn($t) => ['id'=>$t->id,'name'=>$t->user->full_name ?? 'Teacher #'.$t->id])->toArray()"
                    option-value="id" option-label="name" />
            @endif
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showModal = false" />
            <x-button label="{{ $editId ? 'Update' : 'Create' }}" icon="o-check" class="btn-primary" wire:click="save" />
        </x-slot:actions>
    </x-modal>
</div>
