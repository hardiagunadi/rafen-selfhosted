@extends('layouts.admin')

@section('title', 'Terminal Super Admin')

@section('content')
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
            <div>
                <h1 class="h3 mb-1">Terminal Super Admin</h1>
                <p class="text-muted mb-0">Jalankan command operasional self-hosted yang sudah masuk allowlist.</p>
            </div>
            <a href="{{ route('super-admin.help.index') }}" class="btn btn-outline-info btn-sm mt-2 mt-md-0">Buka Bantuan</a>
        </div>

        <div class="alert alert-warning">
            Hanya command yang ada di daftar cepat atau sesuai allowlist yang dapat dijalankan. Service infrastruktur dieksekusi dengan <code>sudo -n</code>. Timeout per command: <strong>{{ $timeoutSeconds }} detik</strong>.
        </div>

        <div id="terminal-alert" class="alert d-none" role="alert"></div>

        <div class="row">
            <div class="col-lg-5 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Quick Command</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($presets as $preset)
                            <button
                                type="button"
                                class="list-group-item list-group-item-action text-left js-terminal-preset"
                                data-command="{{ $preset['command'] }}"
                            >
                                <strong>{{ $preset['label'] }}</strong>
                                <div class="small text-muted mt-1">{{ $preset['note'] }}</div>
                                <code class="d-block mt-2">{{ $preset['command'] }}</code>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-7 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Eksekusi Command</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="terminal-command">Command</label>
                            <input
                                id="terminal-command"
                                type="text"
                                class="form-control"
                                autocomplete="off"
                                placeholder="Contoh: php artisan license:status --json"
                            >
                        </div>

                        <div class="d-flex gap-2 mb-3">
                            <button type="button" id="btn-terminal-run" class="btn btn-primary">Jalankan</button>
                            <button type="button" id="btn-terminal-clear" class="btn btn-outline-secondary">Bersihkan Output</button>
                        </div>

                        <div>
                            <div class="small text-muted mb-1">Output</div>
                            <pre id="terminal-output" class="mb-0 p-3 rounded border bg-dark text-light" style="min-height: 340px; max-height: 520px; overflow: auto; white-space: pre-wrap;">Belum ada command dijalankan.</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const runUrl = '{{ route('super-admin.terminal.run') }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const commandInput = document.getElementById('terminal-command');
        const outputBox = document.getElementById('terminal-output');
        const runButton = document.getElementById('btn-terminal-run');
        const clearButton = document.getElementById('btn-terminal-clear');
        const alertBox = document.getElementById('terminal-alert');

        function setOutput(content) {
            outputBox.textContent = content;
            outputBox.scrollTop = outputBox.scrollHeight;
        }

        function showAlert(type, message) {
            alertBox.className = 'alert alert-' + type;
            alertBox.textContent = message;
        }

        function setRunningState(isRunning) {
            runButton.disabled = isRunning;
            runButton.textContent = isRunning ? 'Menjalankan...' : 'Jalankan';
        }

        document.querySelectorAll('.js-terminal-preset').forEach(function (button) {
            button.addEventListener('click', function () {
                commandInput.value = button.dataset.command || '';
                commandInput.focus();
            });
        });

        clearButton.addEventListener('click', function () {
            alertBox.className = 'alert d-none';
            alertBox.textContent = '';
            setOutput('Belum ada command dijalankan.');
        });

        async function runCommand() {
            const command = commandInput.value.trim();

            if (! command) {
                showAlert('warning', 'Isi command terlebih dahulu.');
                return;
            }

            setRunningState(true);
            setOutput('$ ' + command + '\n\nMemproses...');

            try {
                const response = await fetch(runUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ command }),
                });

                const data = await response.json();
                const header = [
                    '$ ' + (data.command || command),
                    'exit_code: ' + ((data.exit_code === null || data.exit_code === undefined) ? '-' : data.exit_code),
                    'durasi: ' + (data.duration_ms || '-') + ' ms',
                    '',
                ].join('\n');

                setOutput(header + (data.output || '[tidak ada output]'));
                showAlert(data.success ? 'success' : 'danger', data.message || 'Command selesai diproses.');
            } catch (error) {
                setOutput('$ ' + command + '\n\nGagal menjalankan command.');
                showAlert('danger', 'Gagal menjalankan command.');
            } finally {
                setRunningState(false);
            }
        }

        runButton.addEventListener('click', runCommand);

        commandInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                runCommand();
            }
        });
    </script>
@endpush
