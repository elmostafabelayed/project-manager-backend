<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Conversation;
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
            'project_id' => $proposal->project_id
        ]);

        return $proposal;
    }
    public function store(Request $request)
    {
        return Proposal::create([
            'project_id' => $request->project_id,
            'freelancer_id' => Auth::id(),
            'price' => $request->price,
            'message' => $request->message,
        ]);
    }
}   