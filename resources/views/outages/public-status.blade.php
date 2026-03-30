<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="120">
    <title>Status Gangguan</title>
    <style>
        body {
            background: #0f172a;
            color: #e2e8f0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            padding: 24px 16px 40px;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
        }

        .card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
        }

        .status-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 999px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .status-open { background: #dc2626; color: #fff; }
        .status-in_progress { background: #d97706; color: #fff; }
        .status-resolved { background: #16a34a; color: #fff; }

        .area-badge {
            display: inline-block;
            background: rgba(99, 102, 241, .3);
            color: #c7d2fe;
            border-radius: 8px;
            padding: 4px 10px;
            margin: 0 6px 6px 0;
            font-size: .85rem;
        }

        .update-item {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 12px;
            margin-top: 12px;
        }

        .muted {
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        @php
            $statusClass = [
                'open' => 'status-open',
                'in_progress' => 'status-in_progress',
                'resolved' => 'status-resolved',
            ];
        @endphp

        <div class="card" style="text-align: center;">
            <div class="status-badge {{ $statusClass[$outage->status] ?? 'status-open' }}">
                {{ strtoupper(str_replace('_', ' ', $outage->status)) }}
            </div>
            <h1 style="margin: 0 0 8px;">{{ $outage->title }}</h1>
            <div class="muted">Status publik gangguan jaringan</div>
        </div>

        <div class="card">
            <h2 style="margin: 0 0 12px; font-size: 1.1rem;">Informasi Gangguan</h2>
            <p><strong>Mulai:</strong> {{ $outage->started_at->format('d M Y H:i') }}</p>
            @if($outage->estimated_resolved_at)
                <p><strong>Estimasi Selesai:</strong> {{ $outage->estimated_resolved_at->format('d M Y H:i') }}</p>
            @endif
            @if($outage->resolved_at)
                <p><strong>Selesai:</strong> {{ $outage->resolved_at->format('d M Y H:i') }}</p>
            @endif
            @if($outage->description)
                <p><strong>Keterangan:</strong> {{ $outage->description }}</p>
            @endif
            <div>
                <strong>Area Terdampak:</strong><br>
                @forelse($outage->affectedAreas as $area)
                    <span class="area-badge">{{ $area->display_label }}</span>
                @empty
                    <span class="muted">Belum ada area spesifik.</span>
                @endforelse
            </div>
        </div>

        <div class="card">
            <h2 style="margin: 0 0 12px; font-size: 1.1rem;">Riwayat Update</h2>
            @forelse($outage->updates as $update)
                <div class="{{ $loop->first ? '' : 'update-item' }}">
                    <div class="muted" style="font-size: .85rem;">{{ $update->created_at->format('d M Y H:i') }}</div>
                    @if($update->meta)
                        <div class="muted" style="margin-top: 4px;">{{ $update->meta }}</div>
                    @endif
                    <div style="margin-top: 6px;">{{ $update->body ?: '-' }}</div>
                </div>
            @empty
                <div class="muted">Belum ada update publik.</div>
            @endforelse
        </div>
    </div>
</body>
</html>
