<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::with('teacher.user')
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->paginate(15);

        return response()->json($courses);
    }

    public function show(Course $course)
    {
        return response()->json($course->load('teacher.user', 'modules.lessons'));
    }

    public function lessons(Course $course)
    {
        return response()->json($course->modules()->with('lessons')->get());
    }

    public function progress(Request $request, Course $course)
    {
        $student = $request->user()->student;
        if (!$student) return response()->json(['progress' => 0]);

        $total     = $course->lessons()->count();
        $completed = $student->lessonProgress()->whereHas('lesson', fn($q) => $q->where('course_id', $course->id))->where('completed', true)->count();

        return response()->json([
            'total'     => $total,
            'completed' => $completed,
            'percent'   => $total > 0 ? round($completed / $total * 100) : 0,
        ]);
    }

    public function enroll(Request $request, Course $course)
    {
        $student = $request->user()->student;
        if (!$student) return response()->json(['message' => 'Not a student'], 403);

        Enrollment::firstOrCreate([
            'student_id' => $student->id,
            'course_id'  => $course->id,
        ], ['enrolled_at' => now(), 'status' => 'active']);

        return response()->json(['message' => 'Enrolled successfully']);
    }
}
