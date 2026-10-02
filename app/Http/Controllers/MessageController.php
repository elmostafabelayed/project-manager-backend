<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Authorize user
        if ($conversation->client_id != Auth::id() && $conversation->freelancer_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate(['before_id' => 'sometimes|integer|min:1', 'after_id' => 'sometimes|integer|min:0']);
        $query = Message::with('sender:id,name')->where('conversation_id', $conversationId);
        if ($request->has('after_id')) {
            return $query->where('id', '>', $request->integer('after_id'))->orderBy('id')->limit(100)->get();
        }

        return $query->when($request->filled('before_id'), fn ($q) => $q->where('id', '<', $request->integer('before_id')))
            ->orderByDesc('id')->limit(50)->get()->reverse()->values();
    }

    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string|min:1|max:10000',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);

        // Authorize user
        if ($conversation->client_id != Auth::id() && $conversation->freelancer_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'sender_id' => Auth::id(),
            'content' => $request->content,
        ]);

        // Notify the other participant
        $recipientId = ($conversation->client_id == Auth::id()) ? $conversation->freelancer_id : $conversation->client_id;
        $recipient = User::find($recipientId);
        $recipient->notifications()->create([
            'type' => 'message_new',
            'data' => [
                'conversation_id' => $conversation->id,
                'project_id' => $conversation->project_id,
                'sender_name' => Auth::user()->name,
                'message_content' => substr($message->content, 0, 50), // First 50 chars
            ],
        ]);

        // Send email notification
        $recipient->notify(new NewMessageNotification($message));

        return $message;
    }
}
