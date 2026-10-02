<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'reviewed_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5|max:1000',
        ]);

        return DB::transaction(function () use ($request, $data) {
            $project = Project::whereKey($data['project_id'])->lockForUpdate()->firstOrFail();
            $contract = $project->contract;
            abort_unless($contract, 403, 'A contract is required to review this project.');
            $userId = (int) $request->user()->id;
            $clientId = (int) $contract->client_id;
            $freelancerId = (int) $contract->freelancer_id;
            abort_unless(
                ($userId === $clientId && (int) $data['reviewed_id'] === $freelancerId) ||
                ($userId === $freelancerId && (int) $data['reviewed_id'] === $clientId),
                403, 'Only contract participants may review each other.'
            );
            abort_if(Review::where('reviewer_id', $userId)->where('project_id', $project->id)->exists(), 409, 'Already reviewed.');
            abort_unless(in_array($project->status, ['active', 'completed'], true), 409);
            abort_unless($userId === $clientId || $project->status === 'completed', 409, 'The client must complete the project first.');

            if ($userId === $clientId) {
                $project->status = 'completed';
                $project->save();
                $contract->update(['status' => 'completed']);
            }

            return response()->json(Review::create($data + ['reviewer_id' => $userId]), 201);
        });
    }
}
