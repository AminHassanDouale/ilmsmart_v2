<?php

namespace Tests\Feature;

use App\Models\{User, Course, Subject, Teacher, AcademicYear, Grade, Level};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    private function createCourse(): Course
    {
        $level   = Level::create(['name' => 'Moyen', 'order' => 1]);
        $grade   = Grade::create(['level_id' => $level->id, 'name' => '3AM', 'order' => 1]);
        $subject = Subject::create(['grade_id' => $grade->id, 'name' => 'Math', 'coefficient' => 2]);
        $year    = AcademicYear::create(['name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-06-30']);

        $user    = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create(['user_id' => $user->id, 'experience_years' => 5]);

        return Course::create([
            'subject_id'       => $subject->id,
            'teacher_id'       => $teacher->id,
            'academic_year_id' => $year->id,
            'title'            => 'Test Course',
            'status'           => 'published',
            'type'             => 'free',
        ]);
    }

    public function test_admin_can_view_courses(): void
    {
        $admin = User::factory()->admin()->create();
        $this->createCourse();

        $response = $this->actingAs($admin)->get('/admin/courses');
        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }
}
