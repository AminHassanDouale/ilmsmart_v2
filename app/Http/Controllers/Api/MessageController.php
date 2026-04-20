<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function conversations(Request $request)
    {
        $user = $request->user();
        $conversations = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with(['participants.user', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->paginate(20);

        return response()->json($conversations);
    }

    public function messages(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);
        $messages = $conversation->messages()->with('user')->latest()->paginate(30);
        return response()->json($messages);
    }

    public function send(Request $request, $id)
    {
        $request->validate(['body' => 'required|string']);
        $conversation = Conversation::findOrFail($id);

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body'    => $request->body,
        ]);

        return response()->json($message->load('user'));
    }
}
