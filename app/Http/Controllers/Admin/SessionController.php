<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    /**
     * List active sessions for current user.
     */
    public function index(): Response
    {
        $sessions = DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => date('Y-m-d H:i:s', $session->last_activity),
                    'is_current' => $session->id === session()->getId(),
                ];
            });

        return Inertia::render('Admin/Sessions/Index', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Terminate a specific session.
     */
    public function destroy(string $sessionId): RedirectResponse
    {
        if ($sessionId === session()->getId()) {
            return redirect()->back()->with('error', 'Cannot terminate current session.');
        }

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', auth()->id())
            ->delete();

        return redirect()->back()->with('success', 'Session terminated successfully.');
    }

    /**
     * Terminate all other sessions.
     */
    public function destroyAll(): RedirectResponse
    {
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        return redirect()->back()->with('success', 'All other sessions terminated.');
    }
}
