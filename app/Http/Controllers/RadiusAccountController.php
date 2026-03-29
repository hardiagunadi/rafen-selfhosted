<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRadiusAccountRequest;
use App\Http\Requests\UpdateRadiusAccountRequest;
use App\Models\MikrotikConnection;
use App\Models\RadiusAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RadiusAccountController extends Controller
{
    public function index(): View
    {
        return view('super-admin.settings.radius-accounts', [
            'radiusAccounts' => RadiusAccount::query()->with('mikrotikConnection')->latest()->get(),
            'mikrotikConnections' => MikrotikConnection::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreRadiusAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if (($data['service'] ?? null) !== 'pppoe') {
            $data['ipv4_address'] = null;
        }

        RadiusAccount::query()->create($data);

        return redirect()
            ->route('super-admin.settings.radius-accounts.index')
            ->with('success', 'Akun RADIUS berhasil dibuat.');
    }

    public function update(UpdateRadiusAccountRequest $request, RadiusAccount $radiusAccount): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $radiusAccount->is_active);

        if (($data['service'] ?? $radiusAccount->service) !== 'pppoe') {
            $data['ipv4_address'] = null;
        }

        $radiusAccount->update($data);

        return redirect()
            ->route('super-admin.settings.radius-accounts.index')
            ->with('success', 'Akun RADIUS diperbarui.');
    }

    public function destroy(RadiusAccount $radiusAccount): RedirectResponse
    {
        $radiusAccount->delete();

        return redirect()
            ->route('super-admin.settings.radius-accounts.index')
            ->with('success', 'Akun RADIUS dihapus.');
    }
}
