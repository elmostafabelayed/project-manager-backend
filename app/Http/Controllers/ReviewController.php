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
    try {
        // 1. ما تعطيش review لنفسك
        if (Auth::id() == $request->reviewed_id) {
            return response()->json(['error' => 'You cannot review yourself'], 400);
        }

        // 2. ما تعطيش review مرتين
        $exists = Review::where('reviewer_id', Auth::id())
                        ->where('project_id', $request->project_id)
                        ->exists();

        if ($exists) {
            return response()->json(['error' => 'Already reviewed'], 400);
        }

        // 3. خلق review + إنهاء project
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
