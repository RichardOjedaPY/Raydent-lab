@extends('layouts.admin')

@section('title', 'Nuevo paciente')
@section('content_header', 'Nuevo paciente')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.pacientes.store') }}" method="POST">
                @include('admin.pacientes._form', ['paciente' => $paciente])
            </form>
        </div>
    </div>
@endsection
