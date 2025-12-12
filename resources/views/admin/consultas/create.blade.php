@extends('layouts.admin')

@section('title', 'Nueva consulta')
@section('content_header', 'Nueva consulta')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.consultas.store') }}" method="POST">
                @include('admin.consultas._form', ['consulta' => $consulta])
            </form>
        </div>
    </div>
@endsection
