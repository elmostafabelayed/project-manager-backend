<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['page' => 'sometimes|integer|min:1']);
        $notifications = Auth::user()->notifications()->orderByDesc('id')->paginate(20);

        return response()->json(array_merge($notifications->toArray(), [
            'unread_count' => Auth::user()->notifications()->whereNull('read_at')->count(),
            'unread_messages' => Auth::user()->notifications()->whereNull('read_at')->where('type', 'message_new')->count(),
        ]));
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead()
    {
        Auth::user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function markConversationNotificationsRead($conversationId)
    {
        Auth::user()->notifications()
            ->where('type', 'message_new')
            ->where('data->conversation_id', (int) $conversationId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Conversation notifications marked as read']);
    }

    public function markAllMessageNotificationsRead()
    {
        Auth::user()->notifications()
            ->where('type', 'message_new')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All message notifications marked as read']);
    }
}
