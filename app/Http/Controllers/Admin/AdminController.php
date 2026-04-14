<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Project;
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
    public function users()
    {
        return response()->json(User::with('role')->get());
    }

    /**
     * List all projects.
     */
    public function projects()
    {
        return response()->json(Project::with('client')->get());
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
