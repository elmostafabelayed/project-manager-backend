<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;

class ProfileController extends Controller
{
    /**
     * Display a listing of freelancers.
     */
    public function index(Request $request)
    {
        $category = $request->query('category');

        $query = \App\Models\User::where('role_id', 2)
            ->with(['profile', 'skills']);

        if ($category) {
            $query->whereHas('skills', function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        $freelancers = $query->get();

        return response()->json($freelancers);
    }

    /**
     * Display the authenticated user's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load(['profile', 'skills', 'role']);
        
        if (!$user->profile) {
            // Create a default profile if it doesn't exist yet to avoid 404
            $user->profile()->create([
                'user_id' => $user->id
            ]);
            $user->load('profile');
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'profile' => $user->profile,
            'skills' => $user->skills
        ]);
    }

    /**
     * Display a specific user's public profile.
     */
    public function publicShow($id)
    {
        $user = \App\Models\User::with(['profile', 'skills', 'role'])->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'profile' => $user->profile,
            'skills' => $user->skills,
            'created_at' => $user->created_at
        ]);
    }

    
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'hourly_rate' => 'nullable|numeric|min:0',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        $user = $request->user();
        
        // Update user name if provided
        if (isset($validatedData['name'])) {
            $user->update(['name' => $validatedData['name']]);
            unset($validatedData['name']);
        }

        if ($request->hasFile('profile_picture')) {
            $validatedData['profile_picture'] = $request->file('profile_picture')->store('profiles', 'public');
        }

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $validatedData
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile,
            'user' => $user->fresh()
        ]);
    }
}
