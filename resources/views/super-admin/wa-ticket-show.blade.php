@extends('layouts.admin')

@section('title', 'Detail Tiket #' . $waTicket->id)

@section('content')
    <div class="container">
        <div class="mb-3">
            <a href="{{ route('super-admin.wa-tickets.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke Daftar Tiket</a>
            <a href="{{ route('ticket.public-progress', $waTicket->public_token) }}" target="_blank" class="btn btn-outline-primary btn-sm ml-2">Halaman Publik</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Detail Tiket</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <th style="width: 160px;">Judul</th>
                                <td>{{ $waTicket->title }}</td>
                            </tr>
                            <tr>
                                <th>Pelanggan</th>
                                <td>{{ $waTicket->customer_display_name }}</td>
                            </tr>
                            <tr>
                                <th>Nomor Kontak</th>
                                <td>{{ $waTicket->customer_phone ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>{{ str_replace('_', ' ', $waTicket->status) }}</td>
                            </tr>
                            <tr>
                                <th>Prioritas</th>
                                <td>{{ $waTicket->priority }}</td>
                            </tr>
                            <tr>
                                <th>Petugas</th>
                                <td>{{ $waTicket->assignedTo?->name ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Dibuat</th>
                                <td>{{ $waTicket->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                            @if ($waTicket->resolved_at)
                                <tr>
                                    <th>Diselesaikan</th>
                                    <td>{{ $waTicket->resolved_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endif
                        </table>

                        @if ($waTicket->description)
                            <hr>
                            <h4 class="h6">Deskripsi</h4>
                            <p class="mb-0 text-muted">{{ $waTicket->description }}</p>
                        @endif
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Timeline</h3>
                    </div>
                    <div class="card-body">
                        @forelse ($waTicket->notes as $note)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>{{ str_replace('_', ' ', $note->type) }}</strong>
                                    <small class="text-muted">{{ $note->created_at?->format('d/m/Y H:i') }}</small>
                                </div>
                                @if ($note->meta)
                                    <div class="text-muted small mb-2">{{ $note->meta }}</div>
                                @endif
                                @if ($note->note)
                                    <div class="mb-2">{{ $note->note }}</div>
                                @endif
                                @if ($note->image_path)
                                    <div class="mb-2">
                                        <img src="{{ asset('storage/' . $note->image_path) }}" alt="Lampiran catatan" style="max-width: 100%; max-height: 240px; border-radius: 8px;">
                                    </div>
                                @endif
                                <small class="text-muted">Oleh {{ $note->user?->name ?: 'Sistem' }}</small>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Belum ada aktivitas tiket.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Ubah Tiket</h3>
                    </div>
                    <form action="{{ route('super-admin.wa-tickets.update', $waTicket) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="form-group">
                                <label for="title">Judul</label>
                                <input id="title" type="text" name="title" class="form-control" value="{{ old('title', $waTicket->title) }}" required>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="open" @selected(old('status', $waTicket->status) === 'open')>Open</option>
                                    <option value="in_progress" @selected(old('status', $waTicket->status) === 'in_progress')>In Progress</option>
                                    <option value="resolved" @selected(old('status', $waTicket->status) === 'resolved')>Resolved</option>
                                    <option value="closed" @selected(old('status', $waTicket->status) === 'closed')>Closed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="priority">Prioritas</label>
                                <select id="priority" name="priority" class="form-control">
                                    <option value="low" @selected(old('priority', $waTicket->priority) === 'low')>Low</option>
                                    <option value="normal" @selected(old('priority', $waTicket->priority) === 'normal')>Normal</option>
                                    <option value="high" @selected(old('priority', $waTicket->priority) === 'high')>High</option>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label for="description">Deskripsi</label>
                                <textarea id="description" name="description" rows="4" class="form-control">{{ old('description', $waTicket->description) }}</textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Assign Petugas</h3>
                    </div>
                    <form action="{{ route('super-admin.wa-tickets.assign', $waTicket) }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group mb-0">
                                <label for="assigned_to_id">Petugas</label>
                                <select id="assigned_to_id" name="assigned_to_id" class="form-control">
                                    <option value="">Belum di-assign</option>
                                    @foreach ($assignees as $assignee)
                                        <option value="{{ $assignee->id }}" @selected((int) old('assigned_to_id', $waTicket->assigned_to_id) === $assignee->id)>
                                            {{ $assignee->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-outline-primary btn-block">Simpan Penugasan</button>
                        </div>
                    </form>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Tambah Catatan</h3>
                    </div>
                    <form action="{{ route('super-admin.wa-tickets.notes.store', $waTicket) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="note">Catatan</label>
                                <textarea id="note" name="note" rows="4" class="form-control">{{ old('note') }}</textarea>
                            </div>
                            <div class="form-group mb-0">
                                <label for="image">Lampiran Gambar</label>
                                <input id="image" type="file" name="image" class="form-control-file" accept="image/*">
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-outline-success btn-block">Tambah Catatan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
