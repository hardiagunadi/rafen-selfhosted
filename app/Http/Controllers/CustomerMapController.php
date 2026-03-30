<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\PppUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerMapController extends Controller
{
    public function index(Request $request): View
    {
        $odpsQuery = Odp::query()
            ->withCount('pppUsers')
            ->orderBy('code');

        $selectedOdpId = $request->integer('odp_id');
        $selectedStatusAkun = $request->filled('status_akun')
            ? (string) $request->string('status_akun')
            : null;

        $customerQuery = PppUser::query()
            ->with(['profile:id,name', 'odp:id,code,name'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($selectedOdpId > 0) {
            $customerQuery->where('odp_id', $selectedOdpId);
        }

        if (in_array($selectedStatusAkun, ['enable', 'disable', 'isolir'], true)) {
            $customerQuery->where('status_akun', $selectedStatusAkun);
        }

        $customers = $customerQuery->get();

        $customerMarkers = $customers->map(function (PppUser $customer): array {
            return [
                'id' => $customer->id,
                'name' => $customer->customer_name,
                'customer_id' => $customer->customer_id,
                'username' => $customer->username,
                'odp_code' => $customer->odp?->code ?? $customer->odp_pop,
                'profile' => $customer->profile?->name,
                'status_akun' => $customer->status_akun,
                'latitude' => (float) $customer->latitude,
                'longitude' => (float) $customer->longitude,
                'accuracy' => $customer->location_accuracy_m !== null ? (float) $customer->location_accuracy_m : null,
            ];
        })->values();

        $odpMarkers = $odpsQuery->get()
            ->filter(fn (Odp $odp): bool => $odp->latitude !== null && $odp->longitude !== null)
            ->map(function (Odp $odp): array {
                return [
                    'id' => $odp->id,
                    'code' => $odp->code,
                    'name' => $odp->name,
                    'area' => $odp->area,
                    'status' => $odp->status,
                    'capacity_ports' => (int) $odp->capacity_ports,
                    'used_ports' => (int) $odp->ppp_users_count,
                    'latitude' => (float) $odp->latitude,
                    'longitude' => (float) $odp->longitude,
                ];
            })->values();

        return view('super-admin.customer-map', [
            'odps' => $odpsQuery->get(),
            'customerMarkers' => $customerMarkers,
            'odpMarkers' => $odpMarkers,
            'selectedOdpId' => $selectedOdpId > 0 ? $selectedOdpId : null,
            'selectedStatusAkun' => $selectedStatusAkun,
            'summary' => [
                'odps_total' => Odp::query()->count(),
                'odps_with_coordinate' => $odpMarkers->count(),
                'customers_total' => PppUser::query()->count(),
                'customers_with_coordinate' => $customerMarkers->count(),
            ],
        ]);
    }
}
