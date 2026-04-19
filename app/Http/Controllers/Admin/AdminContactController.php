<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminContactController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Messages/Index', [
            'messages' => ContactMessage::latest()
                ->paginate(20)
                ->through(fn (ContactMessage $m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'email' => $m->email,
                    'subject' => $m->subject,
                    'status' => $m->status,
                    'created_at' => $m->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function show(ContactMessage $message): Response
    {
        return Inertia::render('Admin/Messages/Show', [
            'message' => $message,
        ]);
    }

    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,read,replied,archived'],
        ]);
        $message->update($validated);
        return back();
    }
}
