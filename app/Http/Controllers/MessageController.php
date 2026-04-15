<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;
use App\Models\Conversation;

class MessageController extends Controller
{

    public function index($conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        
        // Authorize user
        if ($conversation->client_id != Auth::id() && $conversation->freelancer_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return Message::with('sender')
            ->where('conversation_id', $conversationId)
            ->get();
    }
    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string|min:1'
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        // Authorize user
        if ($conversation->client_id != Auth::id() && $conversation->freelancer_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return Message::create([
            'conversation_id' => $request->conversation_id,
            'sender_id' => Auth::id(),
            'content' => $request->content
        ]);
    }
}
