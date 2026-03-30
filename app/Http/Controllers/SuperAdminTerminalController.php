<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunTerminalCommandRequest;
use App\Models\ActivityLog;
use App\Services\SystemCommandRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class SuperAdminTerminalController extends Controller
{
    public function __construct(private readonly SystemCommandRunner $runner) {}

    public function index(): View
    {
        return view('super-admin.terminal', [
            'presets' => $this->runner->presets(),
            'timeoutSeconds' => $this->runner->timeoutSeconds(),
        ]);
    }

    public function run(RunTerminalCommandRequest $request): JsonResponse
    {
        try {
            $result = $this->runner->run($request->validated('command'));
        } catch (InvalidArgumentException $exception) {
            $this->logCommand(
                request: $request,
                action: 'super_admin_terminal_rejected',
                command: (string) $request->validated('command'),
                properties: ['message' => $exception->getMessage()],
            );

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $this->logCommand(
            request: $request,
            action: $result['success'] ? 'super_admin_terminal_run' : 'super_admin_terminal_failed',
            command: $result['command'],
            properties: [
                'exit_code' => $result['exit_code'],
                'duration_ms' => $result['duration_ms'],
                'success' => $result['success'],
            ],
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function logCommand(RunTerminalCommandRequest $request, string $action, string $command, array $properties): void
    {
        ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => 'SystemCommand',
            'subject_id' => null,
            'subject_label' => $command,
            'properties' => $properties,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
