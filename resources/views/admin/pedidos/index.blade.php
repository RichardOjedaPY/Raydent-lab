@extends('layouts.admin')

@section('title', 'Pedidos')
@section('content_header', 'Pedidos')

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">

            {{-- Filtros de búsqueda --}}
            <form method="GET" action="{{ route('admin.pedidos.index') }}" class="form-inline">

                <div class="input-group input-group-sm mr-2">
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Buscar por código o paciente..."
                           value="{{ $search }}">
                </div>

                <div class="input-group input-group-sm mr-2">
                    <select name="estado" class="form-control">
                        <option value="">-- Estado --</option>
                        @foreach ([
                            'pendiente'   => 'Pendiente',
                            'en_proceso'  => 'En proceso',
                            'finalizado'  => 'Finalizado',
                            'cancelado'   => 'Cancelado',
                        ] as $val => $label)
                            <option value="{{ $val }}" @selected($estado === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Filtrar
                </button>

                @if($search || $estado)
                    <a href="{{ route('admin.pedidos.index') }}"
                       class="btn btn-link btn-sm ml-1">
                        Limpiar
                    </a>
                @endif
            </form>

            {{-- Botón "Nuevo pedido" --}}
            @can('pedidos.create')
                <a href="{{ route('admin.pedidos.create') }}"
                   class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Nuevo pedido
                </a>
            @endcan
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>Código</th>
                            <th>Clínica</th>
                            <th>Paciente</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha solicitud</th>
                            <th>Agendado para</th>
                            <th class="text-center" style="width: 200px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pedidos as $p)
                            <tr>
                                {{-- numeración corrigiendo por página --}}
                                <td>
                                    {{ ($pedidos->currentPage() - 1) * $pedidos->perPage() + $loop->iteration }}
                                </td>

                                <td>{{ $p->codigo }}</td>

                                <td>{{ $p->clinica->nombre ?? '-' }}</td>

                                <td>
                                    @if ($p->paciente)
                                        {{ $p->paciente->apellido }} {{ $p->paciente->nombre }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    <span class="badge badge-{{ $p->prioridad === 'urgente' ? 'danger' : 'secondary' }}">
                                        {{ ucfirst($p->prioridad ?? 'normal') }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $mapEstado = [
                                            'pendiente'  => 'warning',
                                            'en_proceso' => 'info',
                                            'finalizado' => 'success',
                                            'cancelado'  => 'secondary',
                                        ];
                                        $color = $mapEstado[$p->estado] ?? 'light';
                                    @endphp
                                    <span class="badge badge-{{ $color }}">
                                        {{ ucfirst($p->estado) }}
                                    </span>
                                </td>

                                <td>
                                    @if ($p->fecha_solicitud)
                                        {{ \Carbon\Carbon::parse($p->fecha_solicitud)->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @if ($p->fecha_agendada)
                                        {{ \Carbon\Carbon::parse($p->fecha_agendada)->format('d/m/Y') }}
                                        @if ($p->hora_agendada)
                                            {{ ' ' . \Carbon\Carbon::parse($p->hora_agendada)->format('H:i') }}
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="text-center">

                                    {{-- Ver --}}
                                    @can('pedidos.view')
                                        <a href="{{ route('admin.pedidos.show', $p) }}"
                                           class="btn btn-xs btn-outline-secondary"
                                           title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endcan

                                    {{-- Editar --}}
                                    @can('pedidos.update')
                                        <a href="{{ route('admin.pedidos.edit', $p) }}"
                                           class="btn btn-xs btn-outline-primary"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan

                                    {{-- PDF --}}
                                    @can('pedidos.view')
                                        <a href="{{ route('admin.pedidos.pdf', $p) }}"
                                           class="btn btn-xs btn-outline-info"
                                           title="Descargar PDF"
                                           target="_blank">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    @endcan

                                    {{-- Eliminar: solo rol admin --}}
                                    @role('admin')
                                        @can('pedidos.delete')
                                            <form action="{{ route('admin.pedidos.destroy', $p) }}"
                                                  method="POST"
                                                  class="d-inline-block"
                                                  onsubmit="return confirm('¿Eliminar este pedido?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-xs btn-outline-danger"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endrole
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-3">
                                    No hay pedidos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($pedidos->hasPages())
            <div class="card-footer">
                {{ $pedidos->links() }}
            </div>
        @endif
    </div>
@endsection
