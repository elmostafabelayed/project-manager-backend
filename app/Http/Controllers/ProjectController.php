<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return \App\Models\Project::with('client')->get();
    }
    public function store(Request $request)
    {
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
