<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();

        if (! in_array($role, [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF, User::ROLE_USER], true)) {
            $role = '';
        }

        if (! in_array($status, [User::STATUS_ACTIVE, User::STATUS_INACTIVE], true)) {
            $status = '';
        }

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', fn ($query) => $query->where('role', $role))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('users.create', ['user' => new User()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,manager,staff,user'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => User::STATUS_ACTIVE,
            'password' => Hash::make($data['password']),
        ]);

        ActivityLog::record(
            ActivityLog::MODULE_USER,
            ActivityLog::ACTION_CREATED,
            "{$user->name} was added as {$user->role}.",
            $user,
        );

        return redirect()->route('users.index')
            ->with('success', "{$data['name']} was added as {$data['role']}.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:admin,manager,staff,user'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        ActivityLog::record(
            ActivityLog::MODULE_USER,
            ActivityLog::ACTION_UPDATED,
            "{$user->name} was updated to {$user->role} access.",
            $user,
        );

        return redirect()->route('users.index')
            ->with('success', "{$user->name} was updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->deactivate();

        ActivityLog::record(
            ActivityLog::MODULE_USER,
            ActivityLog::ACTION_DEACTIVATED,
            "{$user->name}'s account was deactivated.",
            $user,
        );

        return redirect()->route('users.index')
            ->with('success', "{$user->name} was deactivated.");
    }

    public function restore(User $user): RedirectResponse
    {
        $user->activate();

        ActivityLog::record(
            ActivityLog::MODULE_USER,
            ActivityLog::ACTION_RESTORED,
            "{$user->name}'s account was restored.",
            $user,
        );

        return redirect()->route('users.index')
            ->with('success', "{$user->name} was restored.");
    }
}
