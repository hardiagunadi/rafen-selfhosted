@extends('layouts.admin')

@section('title', 'GenieACS')

@section('content')
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">GenieACS</h3>
                        <div class="d-flex" style="gap: 0.5rem;">
                            @if($uiUrl !== '')
                                <a href="{{ $uiUrl }}" target="_blank" rel="noreferrer" class="btn btn-outline-primary btn-sm">Buka UI</a>
                            @endif
                            <form action="{{ route('super-admin.settings.genieacs.test-connection') }}" method="POST" class="mb-0">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Tes Koneksi NBI</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="small text-muted">NBI URL</div>
                                <div class="font-weight-bold">{{ $nbiUrl !== '' ? $nbiUrl : '-' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted">Status NBI</div>
                                <div class="font-weight-bold {{ $nbiStatus['online'] ? 'text-success' : 'text-danger' }}">
                                    {{ $nbiStatus['online'] ? 'Online' : 'Offline' }}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted">Threshold Online</div>
                                <div class="font-weight-bold">{{ $thresholdMinutes }} menit</div>
                            </div>
                        </div>
                        <div class="mt-3 text-muted">{{ $nbiStatus['message'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $summary['total_devices'] }}</h3>
                        <p>Total Device</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $summary['online_devices'] }}</h3>
                        <p>Device Online</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $summary['pending_tasks'] }}</h3>
                        <p>Pending Task</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $summary['faults'] }}</h3>
                        <p>Faults</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Kontrol Device</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('super-admin.settings.genieacs.devices.connection-request') }}" method="POST">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="device_id">Device ID</label>
                                    <input type="text" id="device_id" name="device_id" class="form-control @error('device_id') is-invalid @enderror" value="{{ old('device_id') }}" placeholder="AA11BB-ONU-12345678">
                                    @error('device_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="profile">Profile</label>
                                    <select id="profile" name="profile" class="form-control @error('profile') is-invalid @enderror">
                                        <option value="igd" @selected(old('profile', 'igd') === 'igd')>TR-098 / IGD</option>
                                        <option value="device" @selected(old('profile') === 'device')>TR-181 / Device</option>
                                    </select>
                                    @error('profile')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-block">Connection Request</button>
                                </div>
                            </div>
                        </form>

                        <form action="{{ route('super-admin.settings.genieacs.devices.clear-tasks') }}" method="POST" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <div class="form-row">
                                <div class="form-group col-md-9">
                                    <label for="clear_task_device_id">Hapus Task per Device</label>
                                    <input type="text" id="clear_task_device_id" name="device_id" class="form-control" value="{{ old('device_id') }}" placeholder="AA11BB-ONU-12345678">
                                </div>
                                <div class="form-group col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-outline-danger btn-block">Bersihkan Task</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Service GenieACS</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($serviceStatus as $serviceKey => $service)
                                        <tr>
                                            <td>{{ $service['label'] }}</td>
                                            <td>
                                                <span class="badge {{ $service['success'] ? 'badge-success' : 'badge-danger' }}">
                                                    {{ $service['success'] ? 'OK' : 'ERR' }}
                                                </span>
                                                <div class="small text-muted mt-1">{{ $service['output'] !== '' ? $service['output'] : '-' }}</div>
                                            </td>
                                            <td class="text-right">
                                                <form action="{{ route('super-admin.settings.genieacs.service', 'restart-'.$serviceKey) }}" method="POST" class="mb-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Restart</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <form action="{{ route('super-admin.settings.genieacs.service', 'restart-all') }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">Restart Semua Service</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Log GenieACS</h3>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-2">
                            Path: {{ $logPath !== '' ? $logPath : '-' }}
                            @if($logPayload['updated_at'])
                                <span class="ml-2">Terakhir diperbarui: {{ $logPayload['updated_at'] }}</span>
                            @endif
                        </div>

                        @if($logPayload['error'])
                            <div class="alert alert-warning mb-0">{{ $logPayload['error'] }}</div>
                        @else
                            <pre class="mb-0 p-3 bg-light rounded" style="max-height: 320px; overflow: auto;">{{ implode(PHP_EOL, $logPayload['lines']) }}</pre>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
