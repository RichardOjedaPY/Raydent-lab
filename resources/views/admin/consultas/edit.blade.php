@extends('layouts.admin')

@section('title', 'Editar consulta')
@section('content_header', 'Editar consulta')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.consultas.update', $consulta) }}" method="POST">
                @method('PUT')
                @include('admin.consultas._form', ['consulta' => $consulta])
            </form>
        </div>
    </div>
@endsection
