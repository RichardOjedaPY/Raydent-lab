@extends('layouts.admin')

@section('title', 'Dashboard (Técnico)')
@section('content_header', 'Dashboard (Técnico)')

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $pendientes }}</h3>
                <p>Pendientes</p>
            </div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            <a href="{{ route('admin.tecnico.pedidos.index', ['estado' => 'pendiente']) }}" class="small-box-footer">
                Ver <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $enProceso }}</h3>
                <p>En proceso</p>
            </div>
            <div class="icon"><i class="fas fa-cogs"></i></div>
            <a href="{{ route('admin.tecnico.pedidos.index', ['estado' => 'en_proceso']) }}" class="small-box-footer">
                Ver <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $realizados }}</h3>
                <p>Realizados</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <a href="{{ route('admin.tecnico.pedidos.index', ['estado' => 'realizado']) }}" class="small-box-footer">
                Ver <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $total }}</h3>
                <p>Total</p>
            </div>
            <div class="icon"><i class="fas fa-layer-group"></i></div>
            <a href="{{ route('admin.tecnico.pedidos.index') }}" class="small-box-footer">
                Ver <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="fas fa-chart-bar mr-1"></i>
            Ranking: Clínicas con más pedidos
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Clínica</th>
                        <th class="text-right">Pedidos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topClinicas as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row['clinica'] }}</td>
                            <td class="text-right">
                                <span class="badge badge-dark">{{ $row['total'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Sin datos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
