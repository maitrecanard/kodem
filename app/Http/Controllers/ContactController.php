<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // Honeypot — si rempli, on silent-discard.
        if (filled($request->input('website'))) {
            return back()->with('success', 'Merci.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:180'],
            'company' => ['nullable', 'string', 'max:120'],
            'subject' => ['required', 'string', 'min:3', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        ContactMessage::create([
            ...$validated,
            'ip_hash' => hash('sha256', (string) $request->ip().config('app.key')),
            'status' => 'new',
        ]);

        return back()->with('success', 'Message envoyé.');
    }
}
