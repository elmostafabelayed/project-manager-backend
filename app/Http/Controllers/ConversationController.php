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

        // Get conversations where the user is either the client or the accepted freelancer
        $conversations = Conversation::with(['project.client', 'project.proposals' => function($query) {
                $query->where('status', 'accepted')->with('freelancer');
            }])
            ->whereHas('project', function ($query) use ($userId) {
                $query->where('client_id', $userId)
                    ->orWhereHas('proposals', function ($q) use ($userId) {
                        $q->where('freelancer_id', $userId)->where('status', 'accepted');
                    });
            })
            ->get();

        // Format for easier frontend consumption
        $formatted = $conversations->map(function ($conversation) use ($userId) {
            $project = $conversation->project;
            $acceptedProposal = $project->proposals->first();
            
            // Determine the "other participant"
            $otherParticipant = null;
            if ($project->client_id == $userId) {
                $otherParticipant = $acceptedProposal ? $acceptedProposal->freelancer : null;
            } else {
                $otherParticipant = $project->client;
            }

            return [
                'id' => $conversation->id,
                'project' => [
                    'id' => $project->id,
                    'title' => $project->title,
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
