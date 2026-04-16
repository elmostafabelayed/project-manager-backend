<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        return Auth::user()->notifications()->orderBy('created_at', 'desc')->get();
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
            ->whereRaw("JSON_EXTRACT(data, '$.conversation_id') = ?", [$conversationId])
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
