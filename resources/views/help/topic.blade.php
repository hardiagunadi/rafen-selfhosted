@extends('layouts.admin')

@section('title', $topic['title'])

@section('content')
    <div class="container">
        <div class="mb-3">
            <a href="{{ route('super-admin.help.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke Pusat Bantuan</a>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-body">
                <h1 class="h3 mb-2">{{ $topic['title'] }}</h1>
                <p class="text-muted">{{ $topic['summary'] }}</p>

                <div class="mt-4">
                    <h2 class="h5">Poin Penting</h2>
                    <ul class="mb-0 pl-3">
                        @foreach($topic['highlights'] as $highlight)
                            <li class="mb-2">{{ $highlight }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
