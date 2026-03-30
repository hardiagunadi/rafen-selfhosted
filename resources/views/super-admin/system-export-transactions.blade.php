@extends('layouts.admin')

@section('title', 'Export Transaksi')

@section('content')
    <div class="container">
        <div class="mb-3">
            <h1 class="h3 mb-1">Export Transaksi</h1>
            <p class="text-muted mb-0">Unduh transaksi invoice dalam format CSV.</p>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('super-admin.tools.export-transactions.download') }}" method="GET">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="date_from">Dari</label>
                            <input type="date" id="date_from" name="date_from" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="date_to">Sampai</label>
                            <input type="date" id="date_to" name="date_to" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">Semua</option>
                                <option value="paid">Paid</option>
                                <option value="unpaid">Unpaid</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Download CSV</button>
                </form>
            </div>
        </div>
    </div>
@endsection
