<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Voucher {{ $batch }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            color: #111827;
        }

        .header {
            margin-bottom: 16px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
        }

        .card {
            border: 1px dashed #9ca3af;
            border-radius: 8px;
            padding: 12px;
        }

        .code {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .meta {
            font-size: 12px;
            color: #4b5563;
            line-height: 1.5;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1 style="margin: 0;">Cetak Voucher</h1>
        <p style="margin: 4px 0 0;">Batch: <strong>{{ $batch }}</strong></p>
    </div>

    <div class="grid">
        @foreach($vouchers as $voucher)
            <div class="card">
                <div class="code">{{ $voucher->code }}</div>
                <div class="meta">
                    <div>Paket: {{ $voucher->hotspotProfile?->name ?: '-' }}</div>
                    <div>Login: {{ $voucher->username ?: $voucher->code }}</div>
                    <div>Password: {{ $voucher->password ?: $voucher->code }}</div>
                    <div>Status: {{ strtoupper($voucher->status) }}</div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
