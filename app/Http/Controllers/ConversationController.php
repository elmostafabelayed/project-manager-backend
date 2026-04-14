<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    /**
     * Display a listing of conversations for the authenticated user.
     */
    public function index()
    {
        $userId = Auth::id();

        // Get conversations where the user is either the client or the freelancer
        $conversations = Conversation::with(['project', 'client', 'freelancer'])
            ->where('client_id', $userId)
            ->orWhere('freelancer_id', $userId)
            ->get();

        // Format for easier frontend consumption
        $formatted = $conversations->map(function ($conversation) use ($userId) {
            // Determine the "other participant"
            $otherParticipant = ($conversation->client_id == $userId) 
                ? $conversation->freelancer 
                : $conversation->client;

            return [
                'id' => $conversation->id,
                'project' => [
                    'id' => $conversation->project->id,
                    'title' => $conversation->project->title,
                ],
                'other_participant' => $otherParticipant ? [
                    'id' => $otherParticipant->id,
                    'name' => $otherParticipant->name,
                ] : null,
                'last_message' => $conversation->messages()->latest()->first(),
                'updated_at' => $conversation->updated_at,
            ];
        });

        return response()->json($formatted);
    }
}
