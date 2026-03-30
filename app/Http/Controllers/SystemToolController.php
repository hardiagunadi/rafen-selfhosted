<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportTransactionsRequest;
use App\Http\Requests\RestoreBackupRequest;
use App\Models\ActivityLog;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemToolController extends Controller
{
    public function backupIndex(): View
    {
        $files = collect(Storage::disk('local')->files('backups'))
            ->filter(fn (string $file): bool => str_ends_with($file, '.json.gz'))
            ->map(fn (string $file): array => [
                'name' => basename($file),
                'path' => $file,
                'size' => $this->formatBytes(Storage::disk('local')->size($file)),
                'modified' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file))->format('Y-m-d H:i:s'),
            ])
            ->sortByDesc('modified')
            ->values();

        return view('super-admin.system-backup', compact('files'));
    }

    public function backupCreate(Request $request): JsonResponse
    {
        $tables = $this->listBackupTables();
        $payload = [
            'created_at' => now()->toIso8601String(),
            'driver' => DB::connection()->getDriverName(),
            'tables' => [],
        ];

        foreach ($tables as $table) {
            $payload['tables'][$table] = DB::table($table)->get()->map(function (object $row): array {
                return (array) $row;
            })->all();
        }

        $filename = 'backup_'.now()->format('Ymd_His').'.json.gz';
        Storage::disk('local')->put('backups/'.$filename, gzencode(json_encode($payload, JSON_PRETTY_PRINT) ?: '{}', 9));

        $this->recordActivity($request, 'backup_created', 'Database', 0, $filename, [
            'tables' => count($tables),
        ]);

        return response()->json([
            'status' => 'Backup berhasil dibuat.',
            'file' => $filename,
        ]);
    }

    public function backupDownload(Request $request): StreamedResponse
    {
        $filename = basename((string) $request->input('file'));
        $path = 'backups/'.$filename;

        abort_unless(Storage::disk('local')->exists($path), 404, 'File backup tidak ditemukan.');

        return Storage::disk('local')->download($path);
    }

    public function backupRestore(RestoreBackupRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $contents = file_get_contents($file->getRealPath());
        $decoded = is_string($contents) ? json_decode((string) gzdecode($contents), true) : null;

        if (! is_array($decoded) || ! is_array($decoded['tables'] ?? null)) {
            return response()->json(['error' => 'Format backup tidak valid.'], 422);
        }

        DB::transaction(function () use ($decoded): void {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            $tables = array_keys($decoded['tables']);

            foreach (array_reverse($tables) as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            foreach ($decoded['tables'] as $table => $rows) {
                if (! Schema::hasTable($table) || ! is_array($rows)) {
                    continue;
                }

                foreach ($rows as $row) {
                    if (is_array($row)) {
                        DB::table($table)->insert($row);
                    }
                }
            }

            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        });

        $this->recordActivity($request, 'backup_restored', 'Database', 0, $file->getClientOriginalName());

        return response()->json([
            'status' => 'Database berhasil direstore.',
        ]);
    }

    public function backupDelete(Request $request): JsonResponse
    {
        $filename = basename((string) $request->input('file'));
        $path = 'backups/'.$filename;

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
            $this->recordActivity($request, 'backup_deleted', 'Database', 0, $filename);
        }

        return response()->json([
            'status' => 'File backup dihapus.',
        ]);
    }

    public function exportTransactionsIndex(): View
    {
        return view('super-admin.system-export-transactions');
    }

    public function exportTransactionsDownload(ExportTransactionsRequest $request): Response
    {
        $validated = $request->validated();
        $dateFrom = isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->startOfDay() : null;
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->endOfDay() : null;
        $status = $validated['status'] ?? null;

        $rows = Invoice::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($dateFrom, fn ($query) => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->where('created_at', '<=', $dateTo))
            ->latest()
            ->get();

        $headers = ['invoice_number', 'customer_id', 'customer_name', 'tipe_service', 'paket_langganan', 'harga_dasar', 'ppn_percent', 'ppn_amount', 'total', 'status', 'due_date', 'paid_at', 'payment_method', 'created_at'];

        $content = fopen('php://temp', 'r+');
        fputcsv($content, $headers);

        foreach ($rows as $invoice) {
            fputcsv($content, [
                $invoice->invoice_number,
                $invoice->customer_id,
                $invoice->customer_name,
                $invoice->tipe_service,
                $invoice->paket_langganan,
                $invoice->harga_dasar,
                $invoice->ppn_percent,
                $invoice->ppn_amount,
                $invoice->total,
                $invoice->status,
                $invoice->due_date?->format('Y-m-d'),
                $invoice->paid_at?->format('Y-m-d H:i:s'),
                $invoice->payment_method,
                $invoice->created_at?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($content);
        $csv = stream_get_contents($content) ?: '';
        fclose($content);

        $filename = 'export_transaksi_'.now()->format('Ymd_His').'.csv';
        $this->recordActivity($request, 'transactions_exported', 'Invoice', 0, $filename, [
            'rows' => $rows->count(),
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function listBackupTables(): array
    {
        $driver = DB::connection()->getDriverName();

        $tables = match ($driver) {
            'sqlite' => collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
                ->map(fn (object $row): string => (string) $row->name)
                ->all(),
            default => collect(DB::select('SHOW TABLES'))
                ->map(fn (object $row): string => (string) array_values((array) $row)[0])
                ->all(),
        };

        return array_values(array_filter($tables, fn (string $table): bool => ! in_array($table, ['migrations'], true)));
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function recordActivity(Request $request, string $action, string $subjectType, int $subjectId, string $subjectLabel, array $properties = []): void
    {
        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'properties' => $properties,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
