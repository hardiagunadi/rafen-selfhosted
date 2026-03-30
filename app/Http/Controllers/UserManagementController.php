<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $search = request()->string('q')->toString();

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('nickname', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return view('super-admin.users.index', [
            'users' => $users,
            'search' => $search,
            'stats' => [
                'total' => User::query()->count(),
                'super_admins' => User::query()->where('is_super_admin', true)->count(),
                'operators' => User::query()->where('is_super_admin', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('super-admin.users.create', [
            'roles' => $this->roles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create($request->validated());

        return redirect()
            ->route('super-admin.users.index')
            ->with('success', 'Pengguna internal berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('super-admin.users.edit', [
            'user' => $user,
            'roles' => $this->roles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        if (($validated['password'] ?? null) === null || $validated['password'] === '') {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('super-admin.users.index')
            ->with('success', 'Pengguna internal berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $authUser = request()->user();

        if ($authUser?->is($user)) {
            return redirect()
                ->route('super-admin.users.index')
                ->with('error', 'Akun yang sedang login tidak dapat dihapus.');
        }

        if ($user->isSuperAdmin() && User::query()->where('is_super_admin', true)->count() <= 1) {
            return redirect()
                ->route('super-admin.users.index')
                ->with('error', 'Minimal harus ada satu super admin aktif.');
        }

        $user->delete();

        return redirect()
            ->route('super-admin.users.index')
            ->with('success', 'Pengguna internal berhasil dihapus.');
    }

    /**
     * @return array<string, string>
     */
    private function roles(): array
    {
        return [
            User::ROLE_ADMINISTRATOR => 'Administrator',
            User::ROLE_IT_SUPPORT => 'IT Support',
            User::ROLE_NOC => 'NOC',
            User::ROLE_KEUANGAN => 'Keuangan',
            User::ROLE_TEKNISI => 'Teknisi',
            User::ROLE_CS => 'Customer Services',
        ];
    }
}
