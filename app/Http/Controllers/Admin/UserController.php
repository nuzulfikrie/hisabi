<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\SearchQuery;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use SearchQuery;

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $query = User::query();

        $query = $this->searchAny($query, $request->input('name'), 'name');
        $query = $this->searchAny($query, $request->input('email'), 'email');
        $query = $this->searchEqual($query, $request->input('status'), 'status');
        $query = $this->searchEqual($query, $request->input('role'), 'role');

        $users = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['name', 'email', 'status', 'role']),
            'statuses' => UserStatus::options(),
            'roles' => UserRole::options(),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => UserRole::options(),
            'statuses' => UserStatus::options(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'role' => $request->validated('role'),
            'status' => $request->validated('status'),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'telegramTransactions' => $user->telegramTransactions()->latest()->limit(10)->get(),
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => UserRole::options(),
            'statuses' => UserStatus::options(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'status' => $request->validated('status'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot change your own status.');
        }

        $newStatus = $user->status === UserStatus::ACTIVE
            ? UserStatus::INACTIVE
            : UserStatus::ACTIVE;

        $user->update(['status' => $newStatus]);

        return redirect()->back()->with('success', 'User status updated successfully.');
    }

    /**
     * Disconnect Telegram account.
     */
    public function disconnectTelegram(User $user): RedirectResponse
    {
        $user->update([
            'telegram_chat_id' => null,
            'telegram_username' => null,
            'telegram_verified_at' => null,
            'telegram_verification_code' => null,
        ]);

        return redirect()->back()->with('success', 'Telegram account disconnected successfully.');
    }
}
