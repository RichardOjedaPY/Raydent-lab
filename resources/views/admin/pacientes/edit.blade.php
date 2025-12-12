@extends('layouts.admin')

@section('title', 'Editar paciente')
@section('content_header', 'Editar paciente')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.pacientes.update', $paciente) }}" method="POST">
                @method('PUT')
                @include('admin.pacientes._form', ['paciente' => $paciente])
            </form>
        </div>
    </div>
@endsection
