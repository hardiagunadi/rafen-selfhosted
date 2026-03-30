<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\View\View;

class LogController extends Controller
{
    public function activityIndex(): View
    {
        return view('super-admin.logs-activity', [
            'logs' => ActivityLog::query()
                ->with('user')
                ->latest('created_at')
                ->get(),
        ]);
    }
}
