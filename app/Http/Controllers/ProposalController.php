<?php

namespace App\Http\Controllers;

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
    public function accept($id)
    {
        $proposal = Proposal::findOrFail($id);

        $proposal->update([
            'status' => 'accepted'
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
