<?php

use App\Models\{Student, Teacher, Course, Payment, Enrollment, Attendance};
use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new
#[Layout('components.layouts.app')]
class extends Component
{
    public string $period = 'month'; // month, quarter, year

    public function with(): array
    {
        $from = match($this->period) {
            'month'   => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year'    => now()->startOfYear(),
            default   => now()->startOfMonth(),
        };

        $revenueByMonth = collect(range(11, 0))->map(function ($i) {
            $date = now()->subMonths($i);
            return [
                'month'   => $date->format('M Y'),
                'revenue' => Payment::where('status','completed')
                    ->whereYear('paid_at', $date->year)
                    ->whereMonth('paid_at', $date->month)
                    ->sum('amount'),
                'count'   => Payment::where('status','completed')
                    ->whereYear('paid_at', $date->year)
                    ->whereMonth('paid_at', $date->month)
                    ->count(),
            ];
        });

        $enrollmentsByMonth = collect(range(11, 0))->map(function ($i) {
            $date = now()->subMonths($i);
            return [
                'month' => $date->format('M Y'),
                'count' => Enrollment::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        });

        $revenueChart = [
            'type' => 'line',
            'data' => [
                'labels'   => $revenueByMonth->pluck('month')->toArray(),
                'datasets' => [
                    [
                        'label'           => 'Revenue (DA)',
                        'data'            => $revenueByMonth->pluck('revenue')->toArray(),
                        'borderColor'     => '#6366f1',
                        'backgroundColor' => 'rgba(99,102,241,0.1)',
                        'fill'            => true,
                        'tension'         => 0.4,
                    ],
                ],
            ],
        ];

        $enrollmentChart = [
            'type' => 'bar',
            'data' => [
                'labels'   => $enrollmentsByMonth->pluck('month')->toArray(),
                'datasets' => [[
                    'label'           => 'New Enrollments',
                    'data'            => $enrollmentsByMonth->pluck('count')->toArray(),
                    'backgroundColor' => 'rgba(34,197,94,0.7)',
                    'borderRadius'    => 4,
                ]],
            ],
        ];

        $topCourses = Course::withCount('enrollments')
            ->orderByDesc('enrollments_count')
            ->take(5)
            ->get();

        $topTeachers = Teacher::withCount(['courses as students_count' => fn($q) =>
            $q->join('enrollments','courses.id','=','enrollments.course_id')
        ])->with('user')->orderByDesc('students_count')->take(5)->get();

        return compact(
            'revenueByMonth', 'revenueChart', 'enrollmentChart',
            'topCourses', 'topTeachers'
        );
    }
}; ?>

<div>
<x-header :title="__('lms.reports')" separator>
        <x-slot:actions>
            <x-group wire:model.live="period"
                :options="[
                    ['id'=>'month',   'name'=>'Month'],
                    ['id'=>'quarter', 'name'=>'Quarter'],
                    ['id'=>'year',    'name'=>'Year'],
                ]"
                class="[&:checked]:!btn-primary btn-sm" />
        </x-slot:actions>
    </x-header>

    {{-- Summary KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-stat :title="__('lms.total_students')"
                :value="Student::count()"
                icon="o-users" color="text-primary" />
        <x-stat :title="__('lms.active_courses')"
                :value="Course::where('status','published')->count()"
                icon="o-cube" color="text-secondary" />
        <x-stat :title="__('lms.total_revenue')"
                :value="number_format(Payment::where('status','completed')->sum('amount')) . ' DA'"
                icon="o-banknotes" color="text-success" />
        <x-stat title="Enrollments"
                :value="Enrollment::count()"
                icon="o-academic-cap" color="text-warning" />
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <x-card :title="__('lms.total_revenue')" shadow>
            <x-chart wire:model="revenueChart" class="h-56" />
        </x-card>
        <x-card title="New Enrollments" shadow>
            <x-chart wire:model="enrollmentChart" class="h-56" />
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Courses --}}
        <x-card title="Top Courses" shadow>
            <div class="space-y-3">
                @foreach($topCourses as $i => $course)
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center shrink-0">
                            {{ $i + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate">{{ $course->translated_title }}</p>
                        </div>
                        <x-badge :value="$course->enrollments_count . ' students'" class="badge-soft badge-primary" />
                    </div>
                @endforeach
            </div>
        </x-card>

        {{-- Top Teachers --}}
        <x-card title="Top Teachers" shadow>
            <div class="space-y-3">
                @foreach($topTeachers as $i => $teacher)
                    <div class="flex items-center gap-3">
                        <div class="avatar">
                            <div class="w-9 rounded-full">
                                <img src="{{ $teacher->user->avatar_url }}" alt="">
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate">{{ $teacher->user->full_name }}</p>
                            <div class="flex items-center gap-1 text-xs text-warning">
                                @for($s = 0; $s < 5; $s++)
                                    <span>{{ $s < floor($teacher->rating) ? '★' : '☆' }}</span>
                                @endfor
                                <span class="text-base-content/50">({{ number_format($teacher->rating, 1) }})</span>
                            </div>
                        </div>
                        <x-badge :value="$teacher->courses->count() . ' courses'" class="badge-soft badge-secondary" />
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

</div>
