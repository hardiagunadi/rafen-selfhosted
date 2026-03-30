<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if(! in_array($ticket->status, ['resolved', 'closed'], true))
        <meta http-equiv="refresh" content="120">
    @endif
    <title>Progres Tiket #{{ $ticket->id }} - {{ $businessName }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            background: #0f172a;
            color: #e2e8f0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            padding: 24px 16px 40px;
        }
        .container { max-width: 720px; margin: 0 auto; }
        .header, .card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
        }
        .header { padding: 24px; margin-bottom: 16px; text-align: center; }
        .card { padding: 24px; margin-bottom: 16px; }
        .status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            background: #2563eb;
            color: #fff;
            margin-bottom: 12px;
        }
        .status-resolved { background: #16a34a; }
        .status-closed { background: #475569; }
        .status-in_progress { background: #d97706; }
        .muted { color: #94a3b8; }
        .info-row { display: flex; gap: 12px; margin-bottom: 10px; }
        .info-label { width: 150px; flex-shrink: 0; color: #94a3b8; }
        .timeline { border-left: 2px solid rgba(255, 255, 255, 0.12); padding-left: 18px; }
        .timeline-item { margin-bottom: 18px; }
        .timeline-type { font-size: 12px; font-weight: 700; color: #93c5fd; text-transform: uppercase; }
        .timeline-time { font-size: 12px; color: #64748b; margin-bottom: 4px; }
        .timeline-body { white-space: pre-wrap; word-break: break-word; }
    </style>
</head>
<body>
    <div class="container">
        @php
            $statusLabels = [
                'open' => 'Tiket Diterima',
                'in_progress' => 'Sedang Ditangani',
                'resolved' => 'Selesai',
                'closed' => 'Ditutup',
            ];
            $typeLabels = [
                'complaint' => 'Komplain',
                'troubleshoot' => 'Troubleshoot',
                'installation' => 'Instalasi',
                'other' => 'Lainnya',
            ];
        @endphp

        <div class="header">
            <div class="muted" style="margin-bottom: 8px;">{{ $businessName }}</div>
            <div class="status status-{{ $ticket->status }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</div>
            <h1 style="margin: 0 0 8px 0;">{{ $ticket->title }}</h1>
            <div class="muted">Tiket #{{ $ticket->id }}</div>
        </div>

        <div class="card">
            <h2 style="margin-top: 0;">Informasi Tiket</h2>
            <div class="info-row">
                <div class="info-label">Pelanggan</div>
                <div>{{ $ticket->customer_name ?: '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Nomor Kontak</div>
                <div>{{ $ticket->customer_phone ?: '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Jenis</div>
                <div>{{ $typeLabels[$ticket->type] ?? $ticket->type }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status</div>
                <div>{{ $statusLabels[$ticket->status] ?? $ticket->status }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Teknisi</div>
                <div>{{ $ticket->assignedTo?->name ?: 'Belum di-assign' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Dibuat</div>
                <div>{{ $ticket->created_at?->format('d/m/Y H:i') }}</div>
            </div>
            @if($ticket->description)
                <div class="info-row" style="align-items: flex-start;">
                    <div class="info-label">Deskripsi</div>
                    <div>{{ $ticket->description }}</div>
                </div>
            @endif
        </div>

        <div class="card">
            <h2 style="margin-top: 0;">Timeline Pengerjaan</h2>
            @if($ticket->notes->isEmpty())
                <div class="muted">Belum ada aktivitas.</div>
            @else
                <div class="timeline">
                    @foreach($ticket->notes as $note)
                        <div class="timeline-item">
                            <div class="timeline-time">{{ $note->created_at?->format('d/m/Y H:i') }}</div>
                            <div class="timeline-type">{{ str_replace('_', ' ', $note->type) }}</div>
                            @if($note->meta)
                                <div class="timeline-body">{{ $note->meta }}</div>
                            @endif
                            @if($note->note)
                                <div class="timeline-body">{{ $note->note }}</div>
                            @endif
                            @if($note->user)
                                <div class="muted" style="margin-top: 4px;">Oleh {{ $note->user->name }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</body>
</html>
