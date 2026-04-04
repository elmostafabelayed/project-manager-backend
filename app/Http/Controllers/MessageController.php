<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;

class MessageController extends Controller
{

    public function index($conversationId)
    {
        return Message::with('sender')
            ->where('conversation_id', $conversationId)
            ->get();
    }
    public function store(Request $request)
    {
        return Message::create([
            'conversation_id' => $request->conversation_id,
            'sender_id' => Auth::id(),
            'content' => $request->content
        ]);
    }
}
