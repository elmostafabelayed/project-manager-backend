<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * Display a listing of conversations for the authenticated user.
     */
    public function index(Request $request)
    {
        $request->validate([
            'before_id' => 'nullable|integer|min:1',
            'conversation_id' => 'nullable|integer|min:1',
        ]);
        $userId = Auth::id();

        // Get conversations where the user is either the client or the freelancer
        $conversations = Conversation::with(['project', 'client.profile', 'freelancer.profile', 'latestMessage'])
            ->where(fn ($query) => $query->where('client_id', $userId)->orWhere('freelancer_id', $userId))
            ->when($request->filled('before_id'), fn ($query) => $query->where('id', '<', $request->integer('before_id')))
            ->when($request->filled('conversation_id'), fn ($query) => $query->whereKey($request->integer('conversation_id')))
            ->orderByDesc('id')->limit(50)->get();

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
                'last_message' => $conversation->latestMessage,
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

        $authUser = $request->user();
        $otherUser = User::findOrFail($request->user_id);
        abort_unless(
            ((int) $authUser->role_id === 1 && (int) $otherUser->role_id === 2) ||
            ((int) $authUser->role_id === 2 && (int) $otherUser->role_id === 1), 403
        );
        $clientId = (int) $authUser->role_id === 1 ? $authUser->id : $otherUser->id;
        $freelancerId = (int) $authUser->role_id === 2 ? $authUser->id : $otherUser->id;
        $project = Project::where('client_id', $clientId)
            ->when($request->filled('project_id'), fn ($q) => $q->whereKey($request->project_id))
            ->latest()->firstOrFail();
        $conversation = DB::transaction(function () use ($project, $clientId, $freelancerId) {
            Project::whereKey($project->id)->lockForUpdate()->firstOrFail();

            return Conversation::firstOrCreate([
                'project_id' => $project->id,
                'client_id' => $clientId,
                'freelancer_id' => $freelancerId,
            ]);
        });

        return response()->json([
            'id' => $conversation->id,
            'project_id' => $conversation->project_id,
        ]);
    }
}
