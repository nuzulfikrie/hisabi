<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display user settings.
     */
    public function index(): View
    {
        return view('user.settings.index', [
            'user' => auth()->user(),
            'settings' => auth()->user()->settings()->get(),
        ]);
    }

    /**
     * Update user settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        // Update profile settings
        if ($request->has('profile')) {
            $user->update($request->validated('profile'));
        }

        // Update preferences
        if ($request->has('preferences')) {
            foreach ($request->input('preferences', []) as $key => $value) {
                $type = is_bool($value) ? 'boolean' : 'string';
                $user->setSetting($key, $value, $type);
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully');
    }
}
