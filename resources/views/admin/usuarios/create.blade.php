@extends('layouts.admin')

@section('title', 'Nuevo usuario')
@section('content_header', 'Nuevo usuario')

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.usuarios.store') }}" method="POST">
                @include('admin.usuarios._form', ['user' => $user])
            </form>
        </div>
    </div>
@endsection
