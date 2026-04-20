<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        return response()->json(Assignment::with('course')->paginate(15));
    }

    public function show(Assignment $assignment)
    {
        return response()->json($assignment->load('course'));
    }

    public function submit(Request $request, Assignment $assignment)
    {
        $student = $request->user()->student;
        if (!$student) return response()->json(['message' => 'Not a student'], 403);

        $request->validate(['content' => 'required|string']);

        $submission = AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'student_id' => $student->id],
            ['content' => $request->content, 'submitted_at' => now(), 'status' => 'submitted']
        );

        return response()->json($submission);
    }
}
