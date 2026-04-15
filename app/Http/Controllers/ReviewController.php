<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Review;
use App\Models\Project;

class ReviewController extends Controller
{


   public function store(Request $request)
{
    $request->validate([
        'project_id' => 'required|exists:projects,id',
        'reviewed_id' => 'required|exists:users,id',
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'required|string|min:5|max:1000'
    ]);

    try {
       
        if (Auth::id() == $request->reviewed_id) {
            return response()->json(['error' => 'You cannot review yourself'], 400);
        }

    
        $exists = Review::where('reviewer_id', Auth::id())
                        ->where('project_id', $request->project_id)
                        ->exists();

        if ($exists) {
            return response()->json(['error' => 'Already reviewed'], 400);
        }

       
        $project = Project::findOrFail($request->project_id);
        $project->update(['status' => 'completed']);

        $review = Review::create([
            'reviewer_id' => Auth::id(),
            'reviewed_id' => $request->reviewed_id,
            'project_id'  => $request->project_id,
            'rating'      => $request->rating,
            'comment'     => $request->comment
        ]);

        return response()->json($review, 201);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}
