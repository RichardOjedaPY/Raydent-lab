{{-- resources/views/admin/pedidos/create.blade.php --}}
@extends('layouts.admin')

@section('title', 'Nuevo pedido')
@section('content_header', 'Nuevo pedido')

@section('content')
    <form action="{{ route('admin.pedidos.store') }}" method="POST">
        @csrf

        @include('admin.pedidos.partials.form', [
            'modo' => 'create',
        ])
    </form>
    @include('admin.pedidos.partials.odontograma-modal')
@endsection
