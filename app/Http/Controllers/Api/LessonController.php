<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function show(Lesson $lesson)
    {
        return response()->json($lesson->load('module.course', 'documents'));
    }

    public function markComplete(Request $request, Lesson $lesson)
    {
        $student = $request->user()->student;
        if (!$student) return response()->json(['message' => 'Not a student'], 403);

        LessonProgress::updateOrCreate(
            ['student_id' => $student->id, 'lesson_id' => $lesson->id],
            ['course_id' => $lesson->module->course_id, 'completed' => true, 'completion_percent' => 100, 'completed_at' => now()]
        );

        return response()->json(['message' => 'Lesson marked complete']);
    }
}
