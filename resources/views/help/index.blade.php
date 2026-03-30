@extends('layouts.admin')

@section('title', 'Pusat Bantuan')

@section('content')
    <div class="container">
        <div class="card card-outline card-primary mb-3">
            <div class="card-body">
                <h1 class="h3 mb-1">Pusat Bantuan</h1>
                <p class="text-muted mb-0">
                    Ringkasan panduan operasional untuk instalasi self-hosted single-tenant.
                </p>
            </div>
        </div>

        <div class="row">
            @foreach($topics as $slug => $topic)
                <div class="col-md-6 col-xl-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 mb-2">{{ $topic['title'] }}</h2>
                            <p class="text-muted small mb-3">{{ $topic['summary'] }}</p>
                            <a href="{{ route('super-admin.help.topic', $slug) }}" class="btn btn-outline-primary btn-sm mt-auto">
                                Buka Panduan
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
