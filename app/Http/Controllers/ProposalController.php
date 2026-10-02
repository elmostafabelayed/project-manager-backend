<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Conversation;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use App\Notifications\NewProposalNotification;
use App\Notifications\ProposalAcceptedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProposalController extends Controller
{
    public function index(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);
        abort_unless((int) $project->client_id === (int) Auth::id(), 403);

        $request->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100']);

        return Proposal::with(['freelancer:id,name,role_id', 'freelancer.profile'])
            ->where('project_id', $projectId)
            ->orderByDesc('id')->paginate($request->integer('per_page', 12));
    }

    public function myProposals(Request $request)
    {
        $request->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100']);
        $query = Proposal::where('freelancer_id', Auth::id());
        $summary = [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'accepted' => (clone $query)->where('status', 'accepted')->count(),
            'accepted_value' => (clone $query)->where('status', 'accepted')->sum('price'),
        ];
        $result = $query->with('project.client:id,name', 'project.client.profile')->orderByDesc('id')->paginate($request->integer('per_page', 12));

        return response()->json(array_merge($result->toArray(), ['summary' => $summary]));
    }

    public function accept($id)
    {
        $proposal = Proposal::findOrFail($id);

        if ($proposal->project->client_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return DB::transaction(function () use ($proposal) {
            $project = Project::whereKey($proposal->project_id)->lockForUpdate()->firstOrFail();
            $proposal->refresh();
            abort_unless($proposal->status === 'pending' && $project->status === 'open' && ! $project->contract()->exists(), 409, 'Proposal cannot be accepted.');
            $proposal->update([
                'status' => 'accepted',
            ]);

            Contract::create([
                'project_id' => $proposal->project_id,
                'client_id' => Auth::id(),
                'freelancer_id' => $proposal->freelancer_id,
                'status' => 'active',
            ]);

            Conversation::firstOrCreate([
                'project_id' => $proposal->project_id,
                'client_id' => Auth::id(),
                'freelancer_id' => $proposal->freelancer_id,
            ]);

            $project->status = 'active';
            $project->save();
            $project->proposals()->where('id', '!=', $proposal->id)->whereIn('status', ['pending', 'invited'])->update(['status' => 'rejected']);

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
        });
    }

    public function reject($id)
    {
        return DB::transaction(function () use ($id) {
            $proposal = Proposal::findOrFail($id);

            if ($proposal->project->client_id != Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            abort_unless($proposal->status === 'pending' || $proposal->status === 'invited', 409);

            Project::whereKey($proposal->project_id)->lockForUpdate()->firstOrFail();
            $proposal->refresh();
            abort_unless(in_array($proposal->status, ['pending', 'invited'], true), 409);
            $proposal->update([
                'status' => 'rejected',
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
        });
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            abort_unless((int) $request->user()->role_id === 2, 403);
            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'price' => 'required|numeric|min:1|max:99999999.99',
                'duration' => 'required|integer|min:1|max:3650',
                'message' => 'required|string|min:10|max:10000',
            ]);

            $project = Project::whereKey($request->project_id)->lockForUpdate()->firstOrFail();
            abort_unless($project->status === 'open', 409, 'Project is not open.');
            abort_if(Proposal::where('project_id', $project->id)->where('freelancer_id', Auth::id())->exists(), 409, 'Proposal already exists.');

            $proposal = Proposal::create([
                'project_id' => $request->project_id,
                'freelancer_id' => Auth::id(),
                'price' => $request->price,
                'duration' => $request->duration,
                'message' => $request->message,
                'source' => 'freelancer',
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
        });
    }

    public function invite(Request $request)
    {
        return DB::transaction(function () use ($request) {
            abort_unless((int) $request->user()->role_id === 1, 403);
            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'freelancer_id' => 'required|exists:users,id',
                'message' => 'required|string|min:10|max:10000',
            ]);

            // Check if project belongs to the authenticated client
            $project = Project::where('id', $request->project_id)
                ->where('client_id', Auth::id())
                ->lockForUpdate()->firstOrFail();

            abort_unless($project->status === 'open', 409);
            abort_unless(User::whereKey($request->freelancer_id)->where('role_id', 2)->exists(), 422);
            abort_if(Proposal::where('project_id', $project->id)->where('freelancer_id', $request->freelancer_id)->exists(), 409);

            $proposal = Proposal::create([
                'project_id' => $request->project_id,
                'freelancer_id' => $request->freelancer_id,
                'price' => 0,
                'duration' => 0,
                'message' => $request->message,
                'status' => 'invited',
                'source' => 'client',
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
        });
    }

    public function respondInvitation(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $proposal = Proposal::where('id', $id)
                ->where('freelancer_id', Auth::id())
                ->where('status', 'invited')
                ->firstOrFail();

            $request->validate([
                'price' => 'required|numeric|min:1|max:99999999.99',
                'duration' => 'required|integer|min:1|max:3650',
                'message' => 'required|string|min:20|max:10000',
            ]);

            Project::whereKey($proposal->project_id)->lockForUpdate()->firstOrFail();
            $proposal->refresh();
            abort_unless($proposal->status === 'invited', 409);
            $proposal->update([
                'price' => $request->price,
                'duration' => $request->duration,
                'response_message' => $request->message,
                'status' => 'pending',
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
        });
    }
}
