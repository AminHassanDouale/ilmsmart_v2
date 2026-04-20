<?php

use App\Models\{Attendance, Course, Enrollment, Student};
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Mary\Traits\Toast;

new
#[Layout('components.layouts.app')]
class extends Component
{
    use Toast;

    public ?int    $course_id   = null;
    public string  $date        = '';
    public array   $attendance  = [];

    public function mount(): void
    {
        $this->date = today()->format('Y-m-d');
    }

    public function with(): array
    {
        $teacher = auth()->user()->teacher;
        $courses = Course::where('teacher_id', $teacher?->id)
            ->where('status', 'published')
            ->get(['id','title']);

        $students = collect();
        if ($this->course_id) {
            $students = Student::whereHas('enrollments', fn($q) =>
                $q->where('course_id', $this->course_id)->where('status', 'active')
            )->with('user')->get();

            // Pre-fill today's attendance
            $existing = Attendance::where('course_id', $this->course_id)
                ->whereDate('date', $this->date)
                ->pluck('status', 'student_id');

            $this->attendance = [];
            foreach ($students as $student) {
                $this->attendance[$student->id] = $existing[$student->id] ?? 'present';
            }
        }

        return compact('courses', 'students');
    }

    public function save(): void
    {
        if (!$this->course_id || empty($this->attendance)) {
            $this->warning('Select a course first.');
            return;
        }

        foreach ($this->attendance as $studentId => $status) {
            Attendance::updateOrCreate(
                ['student_id' => $studentId, 'course_id' => $this->course_id, 'date' => $this->date],
                ['status' => $status]
            );
        }

        $this->success('Attendance saved!');
    }
}; ?>

<div>
<x-header :title="__('lms.attendance')" separator />

    <x-card shadow class="mb-4">
        <div class="flex flex-wrap gap-4">
            <x-select label="Course" wire:model.live="course_id"
                      :options="$courses" option-value="id" option-label="title"
                      placeholder="Select a course..." class="flex-1 min-w-48" />
            <x-datepicker label="Date" wire:model.live="date" icon="o-calendar" />
        </div>
    </x-card>

    @if($course_id && $students->count())
        <x-card shadow :title="__('lms.students') . ' (' . $students->count() . ')'">
            <div class="space-y-2">
                @foreach($students as $student)
                    <div class="flex items-center justify-between py-2 border-b border-base-200 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="avatar">
                                <div class="w-9 rounded-full">
                                    <img src="{{ $student->user->avatar_url }}" alt="">
                                </div>
                            </div>
                            <div>
                                <div class="font-semibold text-sm">{{ $student->user->full_name }}</div>
                                <div class="text-xs text-base-content/60">{{ $student->student_number }}</div>
                            </div>
                        </div>

                        <x-group wire:model="attendance.{{ $student->id }}"
                            :options="[
                                ['id'=>'present', 'name'=> __('lms.present')],
                                ['id'=>'absent',  'name'=> __('lms.absent')],
                                ['id'=>'late',    'name'=> __('lms.late')],
                                ['id'=>'excused', 'name'=> __('lms.excused')],
                            ]"
                            class="[&:checked]:!btn-primary btn-sm" />
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex justify-end">
                <x-button :label="__('lms.save')" icon="o-check-circle"
                          wire:click="save" class="btn-primary" spinner="save" />
            </div>
        </x-card>
    @elseif($course_id)
        <x-icon name="o-users" label="{{ __('lms.no_results') }}" class="h-48" />
    @else
        <div class="flex flex-col items-center justify-center h-48 text-base-content/40">
            <x-icon name="o-clipboard-document-check" class="w-16 h-16 mb-2" />
            <p>Select a course to take attendance</p>
        </div>
    @endif

</div>
