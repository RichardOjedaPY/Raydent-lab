@extends('layouts.admin')

@section('title', 'Nueva clínica')
@section('content_header', 'Nueva clínica')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.clinicas.store') }}" method="POST">
                @include('admin.clinicas._form', ['clinica' => $clinica])
            </form>
        </div>
    </div>
@endsection
