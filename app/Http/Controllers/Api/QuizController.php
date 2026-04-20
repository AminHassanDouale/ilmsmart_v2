<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index()
    {
        return response()->json(Quiz::with('course')->paginate(15));
    }

    public function show(Quiz $quiz)
    {
        return response()->json($quiz->load('questions.answers'));
    }

    public function start(Request $request, Quiz $quiz)
    {
        $student = $request->user()->student;
        if (!$student) return response()->json(['message' => 'Not a student'], 403);

        $attempt = QuizAttempt::create([
            'quiz_id'    => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now(),
        ]);

        return response()->json($attempt);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $student = $request->user()->student;
        if (!$student) return response()->json(['message' => 'Not a student'], 403);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->whereNull('submitted_at')
            ->latest()
            ->firstOrFail();

        $answers  = $request->input('answers', []);
        $score    = 0;
        $total    = $quiz->questions()->count();

        foreach ($answers as $questionId => $answerId) {
            $correct = $quiz->questions()->find($questionId)?->answers()->where('id', $answerId)->where('is_correct', true)->exists();
            if ($correct) $score++;
            $attempt->answers()->create([
                'quiz_question_id' => $questionId,
                'quiz_answer_id'   => $answerId,
                'is_correct'       => $correct,
            ]);
        }

        $attempt->update([
            'submitted_at' => now(),
            'score'        => $total > 0 ? round($score / $total * 100) : 0,
        ]);

        return response()->json(['score' => $attempt->score, 'passed' => $attempt->score >= $quiz->passing_score]);
    }
}
