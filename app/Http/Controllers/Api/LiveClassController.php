<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveClass;
use Illuminate\Http\Request;

class LiveClassController extends Controller
{
    public function index()
    {
        return response()->json(LiveClass::with('teacher.user', 'course')->paginate(15));
    }

    public function upcoming()
    {
        $classes = LiveClass::where('status', 'scheduled')
            ->where('start_time', '>=', now())
            ->with('teacher.user', 'course')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        return response()->json($classes);
    }
}
