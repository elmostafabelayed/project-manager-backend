<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillController extends Controller
{
    /**
     * Display a listing of all available skills.
     */
    public function index()
    {
        return response()->json(Skill::all());
    }

    /**
     * Sync/Attach skills to the authenticated user.
     */
    public function sync(Request $request)
    {
        $request->validate([
            'skills' => 'required|array',
            'skills.*' => 'exists:skills,id'
        ]);

        $user = Auth::user();
        $user->skills()->sync($request->skills);

        return response()->json([
            'message' => 'Skills updated successfully',
            'skills' => $user->skills
        ]);
    }
}
