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
        $conversations = Conversation::with(['project', 'client.profile', 'freelancer.profile'])
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
                    'profile' => $otherParticipant->profile,
                ] : null,
                'last_message' => $conversation->messages()->latest()->first(),
                'updated_at' => $conversation->updated_at,
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Get or create a conversation between the authenticated user and another user.
     */
    public function showOrCreate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $authId = Auth::id();
        $otherId = $request->user_id;
        $projectId = $request->project_id;

        // Try to find an existing conversation
        $query = Conversation::where(function($q) use ($authId, $otherId) {
            $q->where('client_id', $authId)->where('freelancer_id', $otherId);
        })->orWhere(function($q) use ($authId, $otherId) {
            $q->where('client_id', $otherId)->where('freelancer_id', $authId);
        });

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $conversation = $query->first();

        if (!$conversation) {
            // If no project_id is provided, try to find the most recent project between them
            if (!$projectId) {
                $project = \App\Models\Project::where('client_id', $authId)
                    ->orWhere('client_id', $otherId)
                    ->latest()
                    ->first();
                
                if ($project) {
                    $projectId = $project->id;
                } else {
                    return response()->json(['message' => 'No project found to associate with this conversation.'], 422);
                }
            }

            // Determine roles
            $authUser = Auth::user();
            $otherUser = \App\Models\User::find($otherId);

            $clientId = ($authUser->role_id == 1) ? $authId : $otherId;
            $freelancerId = ($authUser->role_id == 2) ? $authId : $otherId;

            $conversation = Conversation::create([
                'project_id' => $projectId,
                'client_id' => $clientId,
                'freelancer_id' => $freelancerId,
            ]);
        }

        return response()->json([
            'id' => $conversation->id,
            'project_id' => $conversation->project_id,
        ]);
    }
}
