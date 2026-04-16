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
}   
