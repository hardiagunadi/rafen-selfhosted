<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWaKeywordRuleRequest;
use App\Http\Requests\UpdateWaKeywordRuleRequest;
use App\Models\WaKeywordRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WaKeywordRuleController extends Controller
{
    public function index(): View
    {
        return view('super-admin.wa-keyword-rules', [
            'rules' => WaKeywordRule::query()
                ->orderBy('priority')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(StoreWaKeywordRuleRequest $request): RedirectResponse
    {
        WaKeywordRule::query()->create([
            'keywords' => $this->normalizeKeywords($request->validated('keywords_text')),
            'reply_text' => $request->validated('reply_text'),
            'priority' => (int) ($request->validated('priority') ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('super-admin.wa-keyword-rules.index')
            ->with('success', 'Rule keyword WhatsApp berhasil ditambahkan.');
    }

    public function update(UpdateWaKeywordRuleRequest $request, WaKeywordRule $waKeywordRule): RedirectResponse
    {
        $waKeywordRule->update([
            'keywords' => $this->normalizeKeywords($request->validated('keywords_text')),
            'reply_text' => $request->validated('reply_text'),
            'priority' => (int) ($request->validated('priority') ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('super-admin.wa-keyword-rules.index')
            ->with('success', 'Rule keyword WhatsApp berhasil diperbarui.');
    }

    public function destroy(WaKeywordRule $waKeywordRule): RedirectResponse
    {
        $waKeywordRule->delete();

        return redirect()
            ->route('super-admin.wa-keyword-rules.index')
            ->with('success', 'Rule keyword WhatsApp berhasil dihapus.');
    }

    /**
     * @return list<string>
     */
    private function normalizeKeywords(string $keywordsText): array
    {
        return collect(preg_split('/[\n,]+/', $keywordsText) ?: [])
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter(fn (string $keyword): bool => $keyword !== '')
            ->unique()
            ->values()
            ->all();
    }
}
