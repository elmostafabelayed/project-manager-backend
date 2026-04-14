<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;

class ProfileController extends Controller
{
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
            'title' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'hourly_rate' => 'nullable|numeric|min:0',
            'profile_picture' => 'nullable|image|max:2048',
        ]);

        $user = $request->user();
        
        if ($request->hasFile('profile_picture')) {
            $validatedData['profile_picture'] = $request->file('profile_picture')->store('profiles', 'public');
        }

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $validatedData
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile
        ]);
    }
}
