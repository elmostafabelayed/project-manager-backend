<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Conversation;
use App\Models\Notification;
use App\Notifications\NewProposalNotification;
use App\Notifications\ProposalAcceptedNotification;
use Illuminate\Support\Facades\Auth;
use App\Models\Proposal;
use Illuminate\Http\Request;

class ProposalController extends Controller
{

    public function index($projectId)
    {
        return Proposal::with('freelancer')
            ->where('project_id', $projectId)
            ->get();
    }

    public function myProposals()
    {
        return Proposal::with('project.client')
            ->where('freelancer_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function accept($id)
    {
        $proposal = Proposal::findOrFail($id);

        $proposal->update([
            'status' => 'accepted'
        ]);

        Contract::create([
            'project_id'    => $proposal->project_id,
            'client_id'     => Auth::id(),
            'freelancer_id' => $proposal->freelancer_id,
            'status'        => 'active'
        ]);

        Conversation::create([
            'project_id' => $proposal->project_id,
            'client_id' => Auth::id(),
            'freelancer_id' => $proposal->freelancer_id
        ]);

        // Notify the freelancer about proposal acceptance
        $proposal->freelancer->notifications()->create([
            'type' => 'proposal_accepted',
            'data' => [
                'project_id' => $proposal->project_id,
                'proposal_id' => $proposal->id,
                'client_name' => Auth::user()->name,
            ],
        ]);

        // Send email notification
        $proposal->freelancer->notify(new ProposalAcceptedNotification($proposal));

        return $proposal;
    }

    public function reject($id)
    {
        $proposal = Proposal::findOrFail($id);

        $proposal->update([
            'status' => 'rejected'
        ]);

        // Notify the freelancer about proposal rejection
        $proposal->freelancer->notifications()->create([
            'type' => 'proposal_rejected',
            'data' => [
                'project_id' => $proposal->project_id,
                'proposal_id' => $proposal->id,
                'client_name' => Auth::user()->name,
            ],
        ]);

        return $proposal;
    }
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'price' => 'required|numeric|min:1',
            'duration' => 'required|numeric|min:1',
            'message' => 'required|string|min:10',
        ]);

        $proposal = Proposal::create([
            'project_id' => $request->project_id,
            'freelancer_id' => Auth::id(),
            'price' => $request->price,
            'duration' => $request->duration,
            'message' => $request->message,
            'source' => 'freelancer'
        ]);

        // Notify the client about new proposal
        $proposal->project->client->notifications()->create([
            'type' => 'proposal_new',
            'data' => [
                'project_id' => $proposal->project_id,
                'proposal_id' => $proposal->id,
                'freelancer_name' => $proposal->freelancer->name,
            ],
        ]);

        // Send email notification
        $proposal->project->client->notify(new NewProposalNotification($proposal));

        return $proposal;
    }

    public function invite(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'freelancer_id' => 'required|exists:users,id',
            'message' => 'required|string|min:10',
        ]);

        // Check if project belongs to the authenticated client
        $project = \App\Models\Project::where('id', $request->project_id)
            ->where('client_id', Auth::id())
            ->firstOrFail();

        $proposal = Proposal::create([
            'project_id' => $request->project_id,
            'freelancer_id' => $request->freelancer_id,
            'price' => 0,
            'duration' => 0,
            'message' => $request->message,
            'status' => 'invited',
            'source' => 'client'
        ]);

        // Notify the freelancer about the invitation
        $proposal->freelancer->notifications()->create([
            'type' => 'invitation_new',
            'data' => [
                'project_id' => $proposal->project_id,
                'proposal_id' => $proposal->id,
                'client_name' => Auth::user()->name,
                'project_title' => $project->title,
                'message_content' => $request->message,
            ],
        ]);

        return $proposal;
    }

    public function respondInvitation(Request $request, $id)
    {
        $proposal = Proposal::where('id', $id)
            ->where('freelancer_id', Auth::id())
            ->where('status', 'invited')
            ->firstOrFail();

        $request->validate([
            'price' => 'required|numeric|min:1',
            'duration' => 'required|numeric|min:1',
            'message' => 'required|string|min:20',
        ]);

        $proposal->update([
            'price' => $request->price,
            'duration' => $request->duration,
            'response_message' => $request->message,
            'status' => 'pending'
        ]);

        // Notify the client about the response
        $proposal->project->client->notifications()->create([
            'type' => 'invitation_response',
            'data' => [
                'project_id' => $proposal->project_id,
                'proposal_id' => $proposal->id,
                'freelancer_name' => Auth::user()->name,
                'project_title' => $proposal->project->title,
            ],
        ]);

        return $proposal;
    }
}   
