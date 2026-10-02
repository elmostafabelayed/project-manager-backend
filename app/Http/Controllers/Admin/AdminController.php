<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get platform statistics.
     */
    public function stats()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_projects' => Project::count(),
            'active_clients' => User::where('role_id', '1')->count(),
            'active_freelancers' => User::where('role_id', '2')->count(),
        ]);
    }

    /**
     * List all users.
     */
    public function users(Request $request)
    {
        $request->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100']);

        return User::with('role')->orderByDesc('id')->paginate($request->integer('per_page', 12));
    }

    /**
     * List all projects.
     */
    public function projects(Request $request)
    {
        $request->validate(['page' => 'sometimes|integer|min:1', 'per_page' => 'sometimes|integer|min:1|max:100']);

        return Project::with('client:id,name')->orderByDesc('id')->paginate($request->integer('per_page', 12));
    }

    /**
     * Delete a user.
     */
    public function deleteUser($id)
    {
        if (auth()->id() == $id) {
            return response()->json(['error' => 'You cannot delete your own account'], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Delete a project.
     */
    public function deleteProject($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }
}
