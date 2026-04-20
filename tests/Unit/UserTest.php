<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_name_attribute(): void
    {
        $user = User::factory()->make([
            'first_name' => 'Ahmed',
            'last_name'  => 'Boudiaf',
        ]);

        $this->assertEquals('Ahmed Boudiaf', $user->full_name);
    }

    public function test_role_helpers(): void
    {
        $admin   = User::factory()->make(['role' => 'admin']);
        $teacher = User::factory()->make(['role' => 'teacher']);
        $student = User::factory()->make(['role' => 'student']);
        $parent  = User::factory()->make(['role' => 'parent']);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($teacher->isTeacher());
        $this->assertTrue($student->isStudent());
        $this->assertTrue($parent->isParent());

        $this->assertFalse($student->isAdmin());
        $this->assertFalse($admin->isStudent());
    }

    public function test_avatar_url_returns_ui_avatars_when_no_avatar(): void
    {
        $user = User::factory()->make(['avatar' => null, 'first_name' => 'Test', 'last_name' => 'User', 'name' => 'Test User']);
        $this->assertStringContainsString('ui-avatars.com', $user->avatar_url);
    }
}
