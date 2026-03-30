@extends('layouts.admin')

@section('title', 'Tambah Pelanggan PPP')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Tambah Pelanggan PPP</h4>
            <a href="{{ route('super-admin.settings.ppp-users.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke List</a>
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 pl-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('super-admin.settings.ppp-users.store') }}" method="POST">
                @csrf
                @include('ppp_users._form')
                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('[data-generate-customer-id]')?.addEventListener('click', async function () {
        const response = await fetch(@json(route('super-admin.settings.ppp-users.customer-id')));
        if (!response.ok) {
            return;
        }
        const payload = await response.json();
        document.getElementById('customer_id').value = payload.customer_id || '';
    });
});
</script>
@endpush
