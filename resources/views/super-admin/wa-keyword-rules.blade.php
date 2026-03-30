@extends('layouts.admin')

@section('title', 'Keyword WA')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tambah Rule Keyword</h3>
                    </div>
                    <form action="{{ route('super-admin.wa-keyword-rules.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="keywords_text">Keyword</label>
                                <textarea id="keywords_text" name="keywords_text" rows="3" class="form-control" placeholder="Pisahkan dengan koma atau baris baru">{{ old('keywords_text') }}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="reply_text">Balasan Otomatis</label>
                                <textarea id="reply_text" name="reply_text" rows="5" class="form-control">{{ old('reply_text') }}</textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="priority">Prioritas</label>
                                    <input id="priority" type="number" name="priority" class="form-control" value="{{ old('priority', 0) }}">
                                </div>
                                <div class="form-group col-md-6 d-flex align-items-center">
                                    <div class="custom-control custom-switch mt-4">
                                        <input id="is_active" type="checkbox" name="is_active" value="1" class="custom-control-input" checked>
                                        <label class="custom-control-label" for="is_active">Aktif</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Simpan Rule</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daftar Rule Keyword</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Keyword</th>
                                        <th>Balasan</th>
                                        <th>Prioritas</th>
                                        <th>Status</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rules as $rule)
                                        <tr>
                                            <td style="min-width: 220px;">
                                                <form action="{{ route('super-admin.wa-keyword-rules.update', $rule) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <textarea name="keywords_text" rows="2" class="form-control form-control-sm mb-2">{{ implode(', ', (array) $rule->keywords) }}</textarea>
                                            </td>
                                            <td style="min-width: 260px;">
                                                    <textarea name="reply_text" rows="3" class="form-control form-control-sm">{{ $rule->reply_text }}</textarea>
                                            </td>
                                            <td style="width: 110px;">
                                                    <input type="number" name="priority" class="form-control form-control-sm mb-2" value="{{ $rule->priority }}">
                                            </td>
                                            <td style="width: 120px;">
                                                    <div class="custom-control custom-switch">
                                                        <input id="rule-active-{{ $rule->id }}" type="checkbox" name="is_active" value="1" class="custom-control-input" {{ $rule->is_active ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="rule-active-{{ $rule->id }}">{{ $rule->is_active ? 'Aktif' : 'Nonaktif' }}</label>
                                                    </div>
                                            </td>
                                            <td class="text-right" style="min-width: 150px;">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm">Update</button>
                                                </form>
                                                <form action="{{ route('super-admin.wa-keyword-rules.destroy', $rule) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada rule keyword.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
