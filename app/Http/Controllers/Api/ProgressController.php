<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;
        if (!$student) return response()->json([]);

        $enrollments = $student->enrollments()->with('course')->get();

        return response()->json($enrollments->map(function ($enrollment) use ($student) {
            $course    = $enrollment->course;
            $total     = $course->lessons()->count();
            $completed = $student->lessonProgress()->where('course_id', $course->id)->where('completed', true)->count();
            return [
                'course'    => $course->only('id', 'title'),
                'total'     => $total,
                'completed' => $completed,
                'percent'   => $total > 0 ? round($completed / $total * 100) : 0,
            ];
        }));
    }

    public function show(Request $request, Course $course)
    {
        $student = $request->user()->student;
        if (!$student) return response()->json(['progress' => 0]);

        $total     = $course->lessons()->count();
        $completed = $student->lessonProgress()->where('course_id', $course->id)->where('completed', true)->count();

        return response()->json([
            'course'    => $course->only('id', 'title'),
            'total'     => $total,
            'completed' => $completed,
            'percent'   => $total > 0 ? round($completed / $total * 100) : 0,
        ]);
    }
}
