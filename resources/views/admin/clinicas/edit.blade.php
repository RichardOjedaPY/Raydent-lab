@extends('layouts.admin')

@section('title', 'Editar clínica')
@section('content_header', 'Editar clínica')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.clinicas.update', $clinica) }}" method="POST">
                @method('PUT')
                @include('admin.clinicas._form', ['clinica' => $clinica])
            </form>
        </div>
    </div>
@endsection
