<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Display settings by group.
     */
    public function index(Request $request): Response
    {
        $group = $request->input('group', 'general');
        $settings = Setting::where('group', $group)->get();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'groups' => Setting::select('group')->distinct()->pluck('group'),
            'currentGroup' => $group,
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
