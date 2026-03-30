@extends('layouts.admin')

@section('title', 'Tiket WhatsApp')

@section('content')
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">Tiket WhatsApp</h1>
                <p class="text-muted mb-0">Kelola tiket pelanggan internal dan bagikan progres publik lewat token.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-2">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['open'] }}</h3>
                        <p>Open</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['in_progress'] }}</h3>
                        <p>In Progress</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['resolved'] }}</h3>
                        <p>Resolved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $stats['closed'] }}</h3>
                        <p>Closed</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <form method="GET" action="{{ route('super-admin.wa-tickets.index') }}">
                            <div class="form-row">
                                <div class="form-group col-md-4 mb-0">
                                    <label for="search" class="small">Cari</label>
                                    <input id="search" type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Judul / pelanggan / nomor">
                                </div>
                                <div class="form-group col-md-3 mb-0">
                                    <label for="status" class="small">Status</label>
                                    <select id="status" name="status" class="form-control form-control-sm">
                                        <option value="">Semua</option>
                                        <option value="open" @selected(request('status') === 'open')>Open</option>
                                        <option value="in_progress" @selected(request('status') === 'in_progress')>In Progress</option>
                                        <option value="resolved" @selected(request('status') === 'resolved')>Resolved</option>
                                        <option value="closed" @selected(request('status') === 'closed')>Closed</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3 mb-0">
                                    <label for="type" class="small">Tipe</label>
                                    <select id="type" name="type" class="form-control form-control-sm">
                                        <option value="">Semua</option>
                                        <option value="complaint" @selected(request('type') === 'complaint')>Komplain</option>
                                        <option value="troubleshoot" @selected(request('type') === 'troubleshoot')>Troubleshoot</option>
                                        <option value="installation" @selected(request('type') === 'installation')>Instalasi</option>
                                        <option value="other" @selected(request('type') === 'other')>Lainnya</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2 mb-0 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-sm btn-block">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Judul</th>
                                        <th>Pelanggan</th>
                                        <th>Status</th>
                                        <th>Prioritas</th>
                                        <th>Petugas</th>
                                        <th>Catatan</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tickets as $ticket)
                                        <tr>
                                            <td>#{{ $ticket->id }}</td>
                                            <td>
                                                <div>{{ $ticket->title }}</div>
                                                <div class="text-muted small">{{ $ticket->type }}</div>
                                            </td>
                                            <td>
                                                <div>{{ $ticket->customer_display_name }}</div>
                                                <div class="text-muted small">{{ $ticket->customer_phone ?: '-' }}</div>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $ticket->status === 'open' ? 'success' : ($ticket->status === 'in_progress' ? 'warning' : ($ticket->status === 'resolved' ? 'info' : 'secondary')) }}">
                                                    {{ str_replace('_', ' ', $ticket->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $ticket->priority }}</td>
                                            <td>{{ $ticket->assignedTo?->name ?: '-' }}</td>
                                            <td>{{ $ticket->notes_count }}</td>
                                            <td class="text-right">
                                                <a href="{{ route('super-admin.wa-tickets.show', $ticket) }}" class="btn btn-xs btn-primary">Detail</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">Belum ada tiket.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if ($tickets->hasPages())
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Halaman {{ $tickets->currentPage() }} / {{ $tickets->lastPage() }}</span>
                            <div class="d-flex">
                                @if ($tickets->previousPageUrl())
                                    <a href="{{ $tickets->previousPageUrl() }}" class="btn btn-outline-secondary btn-xs mr-2">Sebelumnya</a>
                                @endif
                                @if ($tickets->nextPageUrl())
                                    <a href="{{ $tickets->nextPageUrl() }}" class="btn btn-outline-secondary btn-xs">Berikutnya</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Buat Tiket Baru</h3>
                    </div>
                    <form action="{{ route('super-admin.wa-tickets.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="ppp_user_id">Pelanggan PPP</label>
                                <select id="ppp_user_id" name="ppp_user_id" class="form-control">
                                    <option value="">Kontak manual</option>
                                    @foreach ($pppUsers as $pppUser)
                                        <option value="{{ $pppUser->id }}" @selected(old('ppp_user_id') == $pppUser->id)>
                                            {{ $pppUser->customer_name }} - {{ $pppUser->customer_id }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('ppp_user_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="customer_name">Nama Kontak Manual</label>
                                <input id="customer_name" type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}">
                                @error('customer_name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="customer_phone">Nomor Kontak Manual</label>
                                <input id="customer_phone" type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}">
                                @error('customer_phone')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="title">Judul Tiket</label>
                                <input id="title" type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                                @error('title')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="type">Tipe</label>
                                <select id="type" name="type" class="form-control">
                                    <option value="complaint" @selected(old('type') === 'complaint')>Komplain</option>
                                    <option value="troubleshoot" @selected(old('type') === 'troubleshoot')>Troubleshoot</option>
                                    <option value="installation" @selected(old('type') === 'installation')>Instalasi</option>
                                    <option value="other" @selected(old('type') === 'other')>Lainnya</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="priority">Prioritas</label>
                                <select id="priority" name="priority" class="form-control">
                                    <option value="low" @selected(old('priority') === 'low')>Low</option>
                                    <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normal</option>
                                    <option value="high" @selected(old('priority') === 'high')>High</option>
                                </select>
                            </div>

                            <div class="form-group mb-0">
                                <label for="description">Deskripsi</label>
                                <textarea id="description" name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-block">Simpan Tiket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
