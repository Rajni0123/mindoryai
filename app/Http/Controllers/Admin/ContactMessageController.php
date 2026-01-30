<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $messages = $query->paginate(20);
        $unreadCount = ContactMessage::where('status', 'new')->count();

        return view('admin.contact-messages.index', compact('messages', 'unreadCount'));
    }

    public function show($id)
    {
        $message = ContactMessage::findOrFail($id);

        // Mark as read if new
        if ($message->status === 'new') {
            $message->update(['status' => 'read']);
        }

        return view('admin.contact-messages.show', compact('message'));
    }

    public function reply(Request $request, $id)
    {
        $message = ContactMessage::findOrFail($id);

        $validated = $request->validate([
            'admin_reply' => 'required|string|max:10000',
        ]);

        $message->update([
            'admin_reply' => $validated['admin_reply'],
            'status' => 'replied',
            'replied_at' => now(),
            'replied_by' => auth()->id(),
        ]);

        // Send reply email
        try {
            Mail::raw($validated['admin_reply'], function ($mail) use ($message) {
                $mail->to($message->email, $message->name)
                     ->subject('Re: ' . $message->subject . ' - Mindory Support')
                     ->from('support@mindory.in', 'Mindory Support');
            });
        } catch (\Exception $e) {
            // Email send failed but reply is saved
        }

        return redirect()
            ->route('admin.contact-messages.show', $id)
            ->with('success', 'Reply sent successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $message = ContactMessage::findOrFail($id);
        $status = $request->input('status', 'closed');

        if (!in_array($status, ['new', 'read', 'replied', 'closed'])) {
            return back()->with('error', 'Invalid status.');
        }

        $message->update(['status' => $status]);

        return redirect()
            ->route('admin.contact-messages.index')
            ->with('success', 'Status updated.');
    }

    public function destroy($id)
    {
        ContactMessage::findOrFail($id)->delete();

        return redirect()
            ->route('admin.contact-messages.index')
            ->with('success', 'Message deleted.');
    }
}
