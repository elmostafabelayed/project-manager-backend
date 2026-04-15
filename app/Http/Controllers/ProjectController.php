<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return \App\Models\Project::with('client')->get();
    }

    public function myProjects()
    {
        return \App\Models\Project::where('client_id', auth()->id())->get();
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|min:20',
            'budget' => 'required|numeric|min:1'
        ]);

        return \App\Models\Project::create([
            'title' => $request->title,
            'description' => $request->description,
            'budget' => $request->budget,
            'client_id' => auth()->id()
        ]);
    }
    public function update(Request $request, $id)
    {
        $project = \App\Models\Project::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|min:20',
            'budget' => 'sometimes|required|numeric|min:1'
        ]);

        $project->update($request->all());

        return $project;
    }
    public function destroy($id)
    {
        $project = \App\Models\Project::findOrFail($id);

        $project->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
