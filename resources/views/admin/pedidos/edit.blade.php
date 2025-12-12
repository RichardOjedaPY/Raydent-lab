@extends('layouts.admin')

@section('title', 'Editar pedido '.$pedido->codigo)
@section('content_header', 'Editar pedido')

@section('content')
    <form action="{{ route('admin.pedidos.update', $pedido) }}" method="POST">
        @csrf
        @method('PUT')

        @include('admin.pedidos.partials.form', [
            'modo' => 'edit',
        ])
    </form>

    @include('admin.pedidos.partials.odontograma-modal')
@endsection
