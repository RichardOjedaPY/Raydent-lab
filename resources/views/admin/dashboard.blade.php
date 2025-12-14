 {{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')

@section('title', 'Dashboard')
@section('content_header', 'Dashboard')

@section('content')
    {{-- KPIs --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format($totalPedidos, 0, ',', '.') }}</h3>
                    <p>Pedidos (total)</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($pedidosHoy, 0, ',', '.') }}</h3>
                    <p>Pedidos hoy</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($pedidosSemana, 0, ',', '.') }}</h3>
                    <p>Últimos 7 días</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-week"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format($pedidosMes, 0, ',', '.') }}</h3>
                    <p>Últimos 30 días</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ number_format($totalPacientes, 0, ',', '.') }}</h3>
                    <p>Pacientes</p>
                </div>
                <div class="icon"><i class="fas fa-user-injured"></i></div>
            </div>
        </div>

        @if(!is_null($totalClinicas))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-dark">
                    <div class="inner">
                        <h3>{{ number_format($totalClinicas, 0, ',', '.') }}</h3>
                        <p>Clínicas</p>
                    </div>
                    <div class="icon"><i class="fas fa-hospital"></i></div>
                </div>
            </div>
        @endif

        @if(!is_null($totalUsuarios))
            <div class="col-lg-3 col-6">
                <div class="small-box bg-teal">
                    <div class="inner">
                        <h3>{{ number_format($totalUsuarios, 0, ',', '.') }}</h3>
                        <p>Usuarios</p>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </div>
            </div>
        @endif
    </div>

    {{-- Gráficos --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Pedidos (últimos 14 días)</h3>
                </div>
                <div class="card-body">
                    <canvas id="chartPedidosPorDia" height="90"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Distribución por estado</h3>
                </div>
                <div class="card-body">
                    <canvas id="chartEstados" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Últimos pedidos + Actividad --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Últimos pedidos</h3>
                </div>

                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Código</th>
                                <th>Clínica</th>
                                <th>Paciente</th>
                                <th>Estado</th>
                                <th class="text-nowrap">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ultimosPedidos as $p)
                                <tr>
                                    <td class="text-muted">{{ $p->id }}</td>
                                    <td class="text-nowrap">
                                        {{ $p->codigo_pedido ?? $p->codigo ?? ('PED-' . $p->id) }}
                                    </td>
                                    <td>{{ optional($p->clinica)->nombre ?? '-' }}</td>
                                    <td>{{ optional($p->paciente)->nombre ?? '-' }}</td>
                                    <td>
                                        @php
                                            $estado = $p->estado ?? 'sin_estado';
                                            $badge = match ($estado) {
                                                'pendiente'   => 'badge-warning',
                                                'en_proceso'  => 'badge-info',
                                                'finalizado', 'terminado' => 'badge-success',
                                                'cancelado'   => 'badge-danger',
                                                default       => 'badge-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badge }}">{{ \Illuminate\Support\Str::headline($estado) }}</span>
                                    </td>
                                    <td class="text-nowrap">{{ optional($p->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">Sin pedidos aún.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-dark">
                <div class="card-header">
                    <h3 class="card-title">Actividad reciente</h3>
                </div>
                <div class="card-body">
                    @forelse($actividad as $a)
                        <div class="d-flex mb-3">
                            <div class="mr-2 text-muted">
                                <i class="fas fa-history"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small text-muted">
                                    {{ optional($a->created_at)->format('d/m/Y H:i') }}
                                </div>
                                <div>
                                    <strong>{{ $a->log_name ?? 'sistema' }}</strong> —
                                    {{ $a->description ?? 'evento' }}
                                </div>
                                <div class="small text-muted">
                                    Actor: {{ optional($a->causer)->name ?? 'Sistema' }}
                                </div>
                            </div>
                        </div>
                        <hr class="my-2">
                    @empty
                        <div class="text-muted">Sin actividad registrada.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    {{-- Chart.js (ruta típica de AdminLTE) --}}
    <script src="{{ asset('vendor/adminlte/plugins/chart.js/Chart.min.js') }}"></script>

    <script>
        (function () {
            // Pedidos por día (línea)
            const labelsDia = @json($pedidosPorDiaLabels);
            const dataDia   = @json($pedidosPorDiaData);

            const ctx1 = document.getElementById('chartPedidosPorDia');
            if (ctx1) {
                new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: labelsDia,
                        datasets: [{
                            label: 'Pedidos',
                            data: dataDia,
                            tension: 0.35
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true } },
                        scales: {
                            y: { beginAtZero: true, ticks: { precision: 0 } }
                        }
                    }
                });
            }

            // Estados (dona)
            const labelsEstado = @json($estadoLabels);
            const dataEstado   = @json($estadoData);

            const ctx2 = document.getElementById('chartEstados');
            if (ctx2) {
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: labelsEstado,
                        datasets: [{
                            data: dataEstado
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }
        })();
    </script>
@endpush
