<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|min:3|max:255',
            'message' => 'required|string|min:10|max:10000',
        ]);
        DB::table('contact_messages')->insert($data + ['created_at' => now(), 'updated_at' => now()]);

        return response()->json(['message' => 'Your message has been received.'], 201);
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        DB::table('newsletter_subscribers')->insertOrIgnore([
            'email' => strtolower($data['email']), 'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Your subscription has been recorded.']);
    }

    public function messages(Request $request)
    {
        $request->validate(['page' => 'sometimes|integer|min:1']);

        return DB::table('contact_messages')->orderByDesc('id')->paginate(12);
    }
}
