<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveClass;
use App\Models\TutoringSession;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $liveClasses = LiveClass::where('status', 'scheduled')
            ->where('start_time', '>=', now())
            ->with('course')
            ->orderBy('start_time')
            ->limit(20)
            ->get()
            ->map(fn($c) => [
                'type'       => 'live_class',
                'id'         => $c->id,
                'title'      => $c->title,
                'start_time' => $c->start_time,
                'end_time'   => $c->end_time,
            ]);

        return response()->json($liveClasses);
    }
}
