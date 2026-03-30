<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignWaTicketRequest;
use App\Http\Requests\StoreWaTicketNoteRequest;
use App\Http\Requests\StoreWaTicketRequest;
use App\Http\Requests\UpdateWaTicketRequest;
use App\Models\ActivityLog;
use App\Models\PppUser;
use App\Models\User;
use App\Models\WaTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaTicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = WaTicket::query()
            ->with(['assignedTo:id,name'])
            ->withCount('notes')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', (string) $request->string('type'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        return view('super-admin.wa-tickets', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'pppUsers' => PppUser::query()
                ->orderBy('customer_name')
                ->limit(200)
                ->get(['id', 'customer_name', 'customer_id', 'nomor_hp']),
            'stats' => [
                'open' => WaTicket::query()->where('status', 'open')->count(),
                'in_progress' => WaTicket::query()->where('status', 'in_progress')->count(),
                'resolved' => WaTicket::query()->where('status', 'resolved')->count(),
                'closed' => WaTicket::query()->where('status', 'closed')->count(),
            ],
        ]);
    }

    public function store(StoreWaTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $pppUser = isset($validated['ppp_user_id'])
            ? PppUser::query()->findOrFail($validated['ppp_user_id'])
            : null;

        $ticket = WaTicket::query()->create([
            'customer_name' => $pppUser?->customer_name ?: $validated['customer_name'],
            'customer_phone' => $pppUser?->nomor_hp ?: $validated['customer_phone'],
            'customer_type' => $pppUser ? 'ppp' : null,
            'customer_id' => $pppUser?->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'status' => 'open',
            'priority' => $validated['priority'] ?? 'normal',
        ]);

        $ticket->notes()->create([
            'user_id' => auth()->id(),
            'type' => 'created',
            'meta' => 'Tiket dibuat oleh '.auth()->user()?->name,
        ]);

        $this->recordActivity($request, 'wa_ticket_created', $ticket);

        return redirect()
            ->route('super-admin.wa-tickets.show', $ticket)
            ->with('success', 'Tiket berhasil dibuat.');
    }

    public function show(WaTicket $waTicket): View
    {
        $waTicket->load([
            'assignedTo:id,name',
            'assignedBy:id,name',
            'notes.user:id,name',
        ]);

        return view('super-admin.wa-ticket-show', [
            'waTicket' => $waTicket,
            'assignees' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateWaTicketRequest $request, WaTicket $waTicket): RedirectResponse
    {
        $validated = $request->validated();
        $oldStatus = $waTicket->status;
        $oldPriority = $waTicket->priority;

        $waTicket->fill([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ])->save();

        if ($validated['status'] !== $oldStatus) {
            $waTicket->notes()->create([
                'user_id' => auth()->id(),
                'type' => 'status_change',
                'meta' => $oldStatus.' → '.$validated['status'],
            ]);
        }

        if ($validated['priority'] !== $oldPriority) {
            $waTicket->notes()->create([
                'user_id' => auth()->id(),
                'type' => 'note',
                'note' => 'Prioritas diperbarui dari '.$oldPriority.' ke '.$validated['priority'].'.',
            ]);
        }

        $this->recordActivity($request, 'wa_ticket_updated', $waTicket);

        return redirect()
            ->route('super-admin.wa-tickets.show', $waTicket)
            ->with('success', 'Tiket berhasil diperbarui.');
    }

    public function assign(AssignWaTicketRequest $request, WaTicket $waTicket): RedirectResponse
    {
        $validated = $request->validated();
        $assignee = isset($validated['assigned_to_id'])
            ? User::query()->findOrFail($validated['assigned_to_id'])
            : null;

        $waTicket->update([
            'assigned_to_id' => $assignee?->id,
            'assigned_by_id' => auth()->id(),
        ]);

        $waTicket->notes()->create([
            'user_id' => auth()->id(),
            'type' => 'assigned',
            'meta' => $assignee
                ? 'Tiket ditugaskan ke '.$assignee->name.'.'
                : 'Penugasan tiket dilepas.',
        ]);

        $this->recordActivity($request, 'wa_ticket_assigned', $waTicket);

        return redirect()
            ->route('super-admin.wa-tickets.show', $waTicket)
            ->with('success', 'Penugasan tiket diperbarui.');
    }

    public function addNote(StoreWaTicketNoteRequest $request, WaTicket $waTicket): RedirectResponse
    {
        if (! $request->filled('note') && ! $request->hasFile('image')) {
            return redirect()
                ->route('super-admin.wa-tickets.show', $waTicket)
                ->withErrors(['note' => 'Isi catatan atau unggah gambar terlebih dahulu.'])
                ->withInput();
        }

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('ticket-notes', 'public')
            : null;

        $waTicket->notes()->create([
            'user_id' => auth()->id(),
            'type' => 'note',
            'note' => $request->input('note'),
            'image_path' => $imagePath,
        ]);

        $this->recordActivity($request, 'wa_ticket_noted', $waTicket);

        return redirect()
            ->route('super-admin.wa-tickets.show', $waTicket)
            ->with('success', 'Catatan tiket berhasil ditambahkan.');
    }

    private function recordActivity(Request $request, string $action, WaTicket $ticket): void
    {
        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => 'WaTicket',
            'subject_id' => $ticket->id,
            'subject_label' => $ticket->title,
            'properties' => [
                'status' => $ticket->status,
                'priority' => $ticket->priority,
            ],
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
