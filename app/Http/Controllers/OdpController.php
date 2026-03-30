<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateOdpCodeRequest;
use App\Http\Requests\StoreOdpRequest;
use App\Http\Requests\UpdateOdpRequest;
use App\Models\Odp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OdpController extends Controller
{
    public function index(): View
    {
        return view('odps.index', [
            'stats' => $this->stats(),
        ]);
    }

    public function create(): View
    {
        return view('odps.create');
    }

    public function show(Odp $odp): RedirectResponse
    {
        return redirect()->route('super-admin.odps.edit', $odp);
    }

    public function edit(Odp $odp): View
    {
        return view('odps.edit', [
            'odp' => $odp->loadCount('pppUsers'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 20);
        $search = trim((string) $request->input('search.value', ''));

        $query = Odp::query()
            ->withCount('pppUsers')
            ->orderBy('code');

        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count();

        $rows = $query
            ->skip($start)
            ->take($length > 0 ? $length : 20)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map(function (Odp $odp): array {
                $usedPorts = (int) $odp->ppp_users_count;
                $capacity = max(0, (int) $odp->capacity_ports);
                $remaining = max(0, $capacity - $usedPorts);
                $coordinates = $odp->latitude !== null && $odp->longitude !== null
                    ? ((string) $odp->latitude).', '.((string) $odp->longitude)
                    : '-';

                return [
                    'code' => '<a href="'.route('super-admin.odps.edit', $odp).'" class="font-weight-bold text-dark">'.e($odp->code).'</a>',
                    'name' => e($odp->name),
                    'area' => e($odp->area ?: '-'),
                    'coordinates' => e($coordinates),
                    'ports' => e($usedPorts.' / '.$capacity.' / '.$remaining),
                    'status' => strtoupper((string) $odp->status),
                    'aksi' => '<div class="btn-group btn-group-sm">'
                        .'<a href="'.route('super-admin.odps.edit', $odp).'" class="btn btn-warning text-white" title="Edit"><i class="fas fa-pen"></i></a>'
                        .'<button type="button" class="btn btn-danger" data-ajax-delete="'.route('super-admin.odps.destroy', $odp).'" title="Hapus"'.($usedPorts > 0 ? ' disabled' : '').'><i class="fas fa-trash"></i></button>'
                        .'</div>',
                ];
            }),
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->input('q', $request->input('search', '')));

        $query = Odp::query()->orderBy('code');

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword): void {
                $builder->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('area', 'like', "%{$keyword}%");
            });
        }

        return response()->json([
            'data' => $query->limit(20)->get(['id', 'code', 'name', 'area']),
        ]);
    }

    public function generateCode(GenerateOdpCodeRequest $request): JsonResponse
    {
        $prefix = $this->normalizeCodeSegment((string) $request->validated('area_name', 'ODP'));
        $sequence = $this->nextSequence($prefix);

        return response()->json([
            'code' => sprintf('%s-%03d', $prefix, $sequence),
            'prefix' => $prefix,
            'sequence' => $sequence,
        ]);
    }

    public function store(StoreOdpRequest $request): RedirectResponse
    {
        Odp::query()->create($request->validated());

        return redirect()
            ->route('super-admin.odps.index')
            ->with('success', 'Data ODP berhasil ditambahkan.');
    }

    public function update(UpdateOdpRequest $request, Odp $odp): RedirectResponse
    {
        $odp->update($request->validated());

        return redirect()
            ->route('super-admin.odps.index')
            ->with('success', 'Data ODP berhasil diperbarui.');
    }

    public function destroy(Request $request, Odp $odp): RedirectResponse|JsonResponse
    {
        if ($odp->pppUsers()->exists()) {
            $message = 'ODP tidak bisa dihapus karena sudah terhubung ke pelanggan PPP.';

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => $message,
                ], 422);
            }

            return redirect()
                ->route('super-admin.odps.index')
                ->with('error', $message);
        }

        $odp->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'Data ODP berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('super-admin.odps.index')
            ->with('success', 'Data ODP berhasil dihapus.');
    }

    private function normalizeCodeSegment(string $value): string
    {
        $normalized = Str::of($value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->toString();

        if ($normalized === '') {
            return 'ODP';
        }

        return (string) Str::of($normalized)->substr(0, 24);
    }

    private function nextSequence(string $prefix): int
    {
        $maxSequence = 0;
        $pattern = '/^'.preg_quote($prefix, '/').'\-(\d+)$/';

        Odp::query()
            ->where('code', 'like', $prefix.'-%')
            ->pluck('code')
            ->each(function (string $code) use (&$maxSequence, $pattern): void {
                if (preg_match($pattern, $code, $matches) === 1) {
                    $maxSequence = max($maxSequence, (int) $matches[1]);
                }
            });

        return $maxSequence + 1;
    }

    private function stats(): array
    {
        $odps = Odp::query()->withCount('pppUsers')->get();

        return [
            'total_odp' => $odps->count(),
            'active_odp' => $odps->where('status', 'active')->count(),
            'maintenance_odp' => $odps->where('status', 'maintenance')->count(),
            'used_ports' => (int) $odps->sum('ppp_users_count'),
            'capacity_ports' => (int) $odps->sum(fn (Odp $odp): int => max(0, (int) $odp->capacity_ports)),
        ];
    }
}
