{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')

@section('title', 'Dashboard')
@section('content_header', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    Bienvenido al panel del laboratorio Raydent.
                    <br>
                    (Aquí luego pondremos tarjetas con estadísticas de pacientes, pedidos, etc.)
                </div>
            </div>
        </div>
    </div>
@endsection
