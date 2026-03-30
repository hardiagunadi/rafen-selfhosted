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
        return view('super-admin.odps', [
            'odps' => Odp::query()
                ->withCount('pppUsers')
                ->orderBy('code')
                ->get(),
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
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('super-admin.odps.index')
                ->with('error', $message);
        }

        $odp->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Data ODP berhasil dihapus.',
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
}
