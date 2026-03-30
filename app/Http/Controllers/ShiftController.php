<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewShiftSwapRequest;
use App\Http\Requests\StoreShiftDefinitionRequest;
use App\Http\Requests\StoreShiftScheduleRequest;
use App\Http\Requests\StoreShiftSwapRequest;
use App\Http\Requests\UpdateShiftDefinitionRequest;
use App\Models\ShiftDefinition;
use App\Models\ShiftSchedule;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ShiftController extends Controller
{
    /**
     * @var list<string>
     */
    private const ADMIN_ROLES = [
        User::ROLE_ADMINISTRATOR,
    ];

    /**
     * @var list<string>
     */
    private const SHIFT_ROLES = [
        User::ROLE_ADMINISTRATOR,
        User::ROLE_IT_SUPPORT,
        User::ROLE_NOC,
        User::ROLE_KEUANGAN,
        User::ROLE_TEKNISI,
        User::ROLE_CS,
    ];

    public function index(): View
    {
        $user = request()->user();
        $this->requireShiftAccess($user, true);

        return view('shifts.index', [
            'definitions' => ShiftDefinition::query()->orderBy('start_time')->get(),
            'schedules' => ShiftSchedule::query()
                ->with(['user', 'shiftDefinition'])
                ->whereBetween('schedule_date', [now()->startOfWeek()->toDateString(), now()->addDays(13)->toDateString()])
                ->orderBy('schedule_date')
                ->get(),
            'swapRequests' => ShiftSwapRequest::query()
                ->with(['requester', 'target', 'fromSchedule.shiftDefinition', 'toSchedule.shiftDefinition', 'reviewedBy'])
                ->latest()
                ->get(),
            'staffUsers' => User::query()
                ->where(function ($query): void {
                    $query->whereIn('role', self::SHIFT_ROLES)
                        ->orWhere('is_super_admin', true);
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function mySchedule(): View
    {
        $user = request()->user();
        $this->requireShiftAccess($user, false);

        return view('shifts.my-schedule', [
            'userSchedules' => ShiftSchedule::query()
                ->with('shiftDefinition')
                ->where('user_id', $user->id)
                ->whereBetween('schedule_date', [now()->toDateString(), now()->addDays(13)->toDateString()])
                ->orderBy('schedule_date')
                ->get(),
            'swapRequests' => ShiftSwapRequest::query()
                ->with(['target', 'fromSchedule.shiftDefinition', 'toSchedule.shiftDefinition', 'reviewedBy'])
                ->where('requester_id', $user->id)
                ->latest()
                ->get(),
            'candidateSchedules' => ShiftSchedule::query()
                ->with(['user', 'shiftDefinition'])
                ->whereBetween('schedule_date', [now()->toDateString(), now()->addDays(13)->toDateString()])
                ->orderBy('schedule_date')
                ->get(),
            'staffUsers' => User::query()
                ->whereIn('role', self::SHIFT_ROLES)
                ->whereKeyNot($user->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeDefinition(StoreShiftDefinitionRequest $request): RedirectResponse
    {
        $this->requireShiftAccess($request->user(), true);

        ShiftDefinition::query()->create([
            ...$request->validated(),
            'color' => $request->validated('color') ?: '#3b82f6',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('shifts.index')
            ->with('success', 'Definisi shift berhasil dibuat.');
    }

    public function updateDefinition(UpdateShiftDefinitionRequest $request, ShiftDefinition $shiftDefinition): RedirectResponse
    {
        $this->requireShiftAccess($request->user(), true);

        $shiftDefinition->update($request->validated());

        return redirect()
            ->route('shifts.index')
            ->with('success', 'Definisi shift berhasil diperbarui.');
    }

    public function destroyDefinition(ShiftDefinition $shiftDefinition): RedirectResponse
    {
        $this->requireShiftAccess(request()->user(), true);

        $shiftDefinition->delete();

        return redirect()
            ->route('shifts.index')
            ->with('success', 'Definisi shift berhasil dihapus.');
    }

    public function storeSchedule(StoreShiftScheduleRequest $request): RedirectResponse
    {
        $this->requireShiftAccess($request->user(), true);

        ShiftSchedule::query()->updateOrCreate(
            [
                'user_id' => $request->validated('user_id'),
                'shift_definition_id' => $request->validated('shift_definition_id'),
                'schedule_date' => $request->validated('schedule_date'),
            ],
            [
                'status' => $request->validated('status') ?: 'scheduled',
                'notes' => $request->validated('notes'),
            ],
        );

        return redirect()
            ->route('shifts.index')
            ->with('success', 'Jadwal shift berhasil disimpan.');
    }

    public function destroySchedule(ShiftSchedule $shiftSchedule): RedirectResponse
    {
        $this->requireShiftAccess(request()->user(), true);

        $shiftSchedule->delete();

        return redirect()
            ->route('shifts.index')
            ->with('success', 'Jadwal shift berhasil dihapus.');
    }

    public function requestSwap(StoreShiftSwapRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->requireShiftAccess($user, false);

        $fromSchedule = ShiftSchedule::query()->findOrFail($request->validated('from_schedule_id'));

        if (! $user->isSuperAdmin() && $fromSchedule->user_id !== $user->id) {
            abort(403);
        }

        ShiftSwapRequest::query()->create([
            'requester_id' => $user->id,
            'target_id' => $request->validated('target_id'),
            'from_schedule_id' => $fromSchedule->id,
            'to_schedule_id' => $request->validated('to_schedule_id'),
            'reason' => $request->validated('reason'),
            'status' => 'pending',
        ]);

        return redirect()
            ->route('shifts.my')
            ->with('success', 'Permintaan tukar shift berhasil dikirim.');
    }

    public function reviewSwap(ReviewShiftSwapRequest $request, ShiftSwapRequest $shiftSwapRequest): RedirectResponse
    {
        $user = $request->user();
        $this->requireShiftAccess($user, true);

        $isApprove = $request->validated('action') === 'approve';

        $shiftSwapRequest->update([
            'status' => $isApprove ? 'approved' : 'rejected',
            'reviewed_by_id' => $user->id,
            'reviewed_at' => now(),
        ]);

        if ($isApprove && $shiftSwapRequest->to_schedule_id) {
            $from = $shiftSwapRequest->fromSchedule;
            $to = $shiftSwapRequest->toSchedule;

            if ($from && $to) {
                [$from->user_id, $to->user_id] = [$to->user_id, $from->user_id];
                $from->status = 'swapped';
                $to->status = 'swapped';
                $from->save();
                $to->save();
            }
        }

        return redirect()
            ->route('shifts.index')
            ->with('success', 'Permintaan tukar shift berhasil diproses.');
    }

    private function requireShiftAccess(?User $user, bool $adminOnly): void
    {
        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($adminOnly && ! in_array($user->role, self::ADMIN_ROLES, true)) {
            abort(403);
        }

        if (! $adminOnly && ! in_array($user->role, self::SHIFT_ROLES, true)) {
            abort(403);
        }
    }
}
