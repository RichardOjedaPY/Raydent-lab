@extends('layouts.admin')

@section('title', 'Editar usuario')
@section('content_header', 'Editar usuario')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.usuarios.update', $user) }}" method="POST">
                @method('PUT')
                @include('admin.usuarios._form', ['user' => $user])
            </form>
        </div>
    </div>
@endsection
