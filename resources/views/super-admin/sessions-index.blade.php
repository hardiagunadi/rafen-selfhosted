@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
    <div class="container-fluid">
        <div class="mb-3">
            <h1 class="h3 mb-1">{{ $pageTitle }}</h1>
            <p class="text-muted mb-0">{{ $pageDescription }}</p>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="small-box {{ $isActive ? 'bg-success' : 'bg-secondary' }}">
                    <div class="inner">
                        <h3 id="session-total">{{ $total }}</h3>
                        <p>{{ $isActive ? 'Sesi Aktif' : 'Sesi Tidak Aktif' }}</p>
                    </div>
                    <div class="icon">
                        <i class="fas {{ $service === 'pppoe' ? 'fa-network-wired' : 'fa-wifi' }}"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $routers->count() }}</h3>
                        <p>Router Aktif</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-server"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Data Session</h3>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route($service === 'pppoe' ? 'super-admin.sessions.pppoe' : 'super-admin.sessions.hotspot') }}" class="btn btn-outline-success {{ $isActive ? 'active' : '' }}">Aktif</a>
                        <a href="{{ route($service === 'pppoe' ? 'super-admin.sessions.pppoe-inactive' : 'super-admin.sessions.hotspot-inactive') }}" class="btn btn-outline-danger {{ $isActive ? '' : 'active' }}">Tidak Aktif</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="form-row mb-3">
                    <div class="form-group col-md-3 mb-0">
                        <label for="session-router-filter">Filter Router</label>
                        <select id="session-router-filter" class="form-control">
                            <option value="">Semua Router</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}">{{ $router->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4 mb-0">
                        <label for="session-search">Cari</label>
                        <input type="text" id="session-search" class="form-control" placeholder="Username, IP, MAC, profile, server">
                    </div>
                    <div class="form-group col-md-2 mb-0 d-flex align-items-end">
                        <button type="button" id="session-refresh" class="btn btn-outline-primary btn-block">Refresh</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>IP Address</th>
                                <th>Caller / MAC</th>
                                <th>Uptime</th>
                                <th>Upload</th>
                                <th>Download</th>
                                <th>{{ $service === 'pppoe' ? 'Profile' : 'Server' }}</th>
                                <th>Router</th>
                                <th>Diperbarui</th>
                            </tr>
                        </thead>
                        <tbody id="session-table-body">
                            <tr>
                                <td colspan="9" class="text-center text-muted">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const datatableUrl = @json(route($routePrefix . '.datatable'));
            const $routerFilter = $('#session-router-filter');
            const $search = $('#session-search');
            const $tableBody = $('#session-table-body');
            const $total = $('#session-total');

            function renderRows(rows) {
                if (!rows.length) {
                    $tableBody.html('<tr><td colspan="9" class="text-center text-muted">Tidak ada session yang cocok.</td></tr>');
                    return;
                }

                const html = rows.map(function (row) {
                    const serviceValue = @json($service) === 'pppoe' ? row.profile : row.server_name;

                    return `<tr>
                        <td><strong>${row.username}</strong></td>
                        <td><code>${row.ipv4}</code></td>
                        <td><code>${row.caller_id}</code></td>
                        <td>${row.uptime}</td>
                        <td>${row.bytes_in}</td>
                        <td>${row.bytes_out}</td>
                        <td>${serviceValue}</td>
                        <td>${row.router}</td>
                        <td><small class="text-muted">${row.updated_at}</small></td>
                    </tr>`;
                }).join('');

                $tableBody.html(html);
            }

            function loadSessions() {
                $tableBody.html('<tr><td colspan="9" class="text-center text-muted">Memuat data...</td></tr>');

                $.get(datatableUrl, {
                    router_id: $routerFilter.val(),
                    search: $search.val(),
                    length: 100,
                }).done(function (response) {
                    $total.text(response.recordsFiltered ?? 0);
                    renderRows(response.data ?? []);
                }).fail(function () {
                    $tableBody.html('<tr><td colspan="9" class="text-center text-danger">Gagal memuat data session.</td></tr>');
                });
            }

            $routerFilter.on('change', loadSessions);
            $search.on('input', loadSessions);
            $('#session-refresh').on('click', loadSessions);

            loadSessions();
        });
    </script>
@endpush
