<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Proposal;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100', 'search' => 'nullable|string|max:200', 'category' => 'nullable|string|max:255']);

        return Project::with('client:id,name,role_id', 'client.profile')
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category', $request->category);
            })
            ->where('status', 'open')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->search.'%')->orWhere('description', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->boolean('available') && $request->user(), function ($query) use ($request) {
                $query->whereDoesntHave('proposals', fn ($q) => $q->where('freelancer_id', $request->user()->id));
            })
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 12));
    }

    public function myProjects(Request $request)
    {
        $request->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100', 'status' => 'nullable|in:open,active,completed']);
        $summary = ['total_budget' => Project::where('client_id', auth()->id())->sum('budget'), 'active_proposals' => Proposal::whereHas('project', fn ($q) => $q->where('client_id', auth()->id()))->where('status', 'pending')->count()];
        $result = Project::where('client_id', auth()->id())->with('contract.freelancer:id,name,role_id')->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->orderByDesc('id')->paginate($request->integer('per_page', 12));

        return response()->json(array_merge($result->toArray(), ['summary' => $summary]));
    }

    public function show($id)
    {
        return Project::with('client:id,name,role_id', 'client.profile')->findOrFail($id);
    }

    public function store(Request $request)
    {
        abort_unless((int) $request->user()->role_id === 1, 403);
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:20|max:10000',
            'budget' => 'required|numeric|min:1|max:99999999.99',
            'category' => 'nullable|string|max:255',
        ]);

        return Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'budget' => $request->budget,
            'category' => $request->category,
            'client_id' => auth()->id(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        if ($project->client_id != auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|min:20|max:10000',
            'budget' => 'sometimes|required|numeric|min:1|max:99999999.99',
            'category' => 'sometimes|nullable|string|max:255',
        ]);

        $project->update($request->only(['title', 'description', 'budget', 'category']));

        return $project;
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        if ($project->client_id != auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $project->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function createFromProposal(Request $request)
    {
        abort_unless((int) $request->user()->role_id === 1, 403);
        $request->validate([
            'proposal_id' => 'required|exists:proposals,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|min:20|max:10000',
            'budget' => 'sometimes|numeric|min:1|max:99999999.99',
            'category' => 'sometimes|nullable|string|max:255',
        ]);

        $proposal = Proposal::with('project')->findOrFail($request->proposal_id);
        abort_unless((int) $proposal->project->client_id === (int) auth()->id(), 403);

        $project = Project::create([
            'title' => $request->title ?? $proposal->project->title ?? 'Project from Proposal',
            'description' => $request->description ?? $proposal->project->description ?? '',
            'budget' => $request->budget ?? $proposal->price,
            'category' => $request->category ?? $proposal->project->category ?? null,
            'client_id' => auth()->id(),
        ]);

        return $project;
    }
}
