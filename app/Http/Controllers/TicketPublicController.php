<?php

namespace App\Http\Controllers;

use App\Models\WaTicket;
use Illuminate\View\View;

class TicketPublicController extends Controller
{
    public function show(string $token): View
    {
        $ticket = WaTicket::query()
            ->where('public_token', $token)
            ->with([
                'assignedTo:id,name',
                'notes' => fn ($query) => $query
                    ->whereIn('type', ['created', 'status_change', 'assigned', 'note'])
                    ->orderBy('created_at'),
                'notes.user:id,name',
            ])
            ->firstOrFail();

        return view('tickets.public-progress', [
            'ticket' => $ticket,
            'businessName' => config('app.name', 'Rafen Self-Hosted'),
        ]);
    }
}
