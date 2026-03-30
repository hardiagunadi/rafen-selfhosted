<?php

namespace App\Http\Controllers;

use App\Models\Outage;
use Illuminate\View\View;

class OutageStatusController extends Controller
{
    public function show(string $token): View
    {
        $outage = Outage::query()
            ->where('public_token', $token)
            ->with([
                'affectedAreas',
                'updates' => fn ($query) => $query->where('is_public', true)->orderBy('created_at'),
                'updates.user',
            ])
            ->firstOrFail();

        return view('outages.public-status', [
            'outage' => $outage,
        ]);
    }
}
