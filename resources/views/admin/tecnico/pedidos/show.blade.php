@extends('layouts.admin')

@section('title', 'Trabajar Pedido')
@section('content_header', 'Trabajar Pedido')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="h5 mb-0">
                Pedido: <strong>{{ $pedido->codigo_pedido ?? ($pedido->codigo ?? '#' . $pedido->id) }}</strong>
            </div>
            <div class="text-muted small">
                Clínica: {{ $pedido->clinica->nombre ?? '-' }} · Paciente:
                {{ ($pedido->paciente->apellido ?? '') . ' ' . ($pedido->paciente->nombre ?? '') }}
            </div>
        </div>
        <div>
            <a href="{{ route('admin.tecnico.pedidos.index') }}" class="btn btn-sm btn-secondary">Volver</a>
            <a href="{{ route('admin.pedidos.show', $pedido) }}" class="btn btn-sm btn-outline-primary">Ver como clínica</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Estado</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.tecnico.pedidos.estado', $pedido) }}">
                        @csrf
                        <select name="estado" class="form-control">
                            @foreach (['pendiente', 'en_proceso', 'realizado', 'entregado', 'cancelado'] as $e)
                                <option value="{{ $e }}" @selected($pedido->estado === $e)>{{ $e }}
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary btn-sm mt-2">Guardar estado</button>
                    </form>

                    <hr>
                    <div class="small text-muted">Inicio:</div>
                    <div>{{ $pedido->fecha_inicio_trabajo ?? '-' }}</div>
                    <div class="small text-muted mt-2">Fin:</div>
                    <div>{{ $pedido->fecha_fin_trabajo ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Subir archivos (zip/pdf/dicom/raw/etc)</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.tecnico.pedidos.archivos', $pedido) }}"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="form-row">
                            <div class="col-md-4">
                                <select name="grupo" class="form-control form-control-sm">
                                    @foreach (['resultado', 'dicom', 'raw', 'pdf', 'imagen', 'otro'] as $g)
                                        <option value="{{ $g }}">{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <input type="file" name="archivos[]" class="form-control form-control-sm" multiple
                                    required>
                            </div>
                        </div>
                        <button class="btn btn-success btn-sm mt-2">Subir</button>
                    </form>

                    <hr>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Archivo</th>
                                    <th>Grupo</th>
                                    <th>Tamaño</th>
                                    <th class="text-right">Descargar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pedido->archivos as $a)
                                    <tr>
                                        <td>{{ $a->original_name }}</td>
                                        <td><span class="badge badge-info">{{ $a->grupo }}</span></td>
                                        <td>{{ number_format(($a->size ?? 0) / 1024 / 1024, 2) }} MB</td>
                                        <td class="text-right">
                                            <a class="btn btn-sm btn-primary"
                                                href="{{ route('admin.resultados.archivo.download', $a) }}">Descargar</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-2">Sin archivos cargados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Fotos realizadas (8)</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.tecnico.pedidos.fotos', $pedido) }}"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            @foreach ($slots as $slot => $label)
                                <div class="col-md-6 mb-2">
                                    <label class="small mb-1">{{ $label }}</label>
                                    <input type="file" name="fotos[{{ $slot }}]"
                                        class="form-control form-control-sm" accept="image/*">
                                </div>
                            @endforeach
                        </div>

                        <button class="btn btn-success btn-sm">Guardar fotos</button>
                    </form>

                    <hr>

                    @php
                        $fotosBySlot = $pedido->fotosRealizadas->keyBy('slot');
                    @endphp

                    <div class="row">
                        @foreach ($slots as $slot => $label)
                            @php $f = $fotosBySlot->get($slot); @endphp

                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-1">
                                    <div class="small text-muted">{{ $label }}</div>

                                    @if ($f && \Illuminate\Support\Facades\Route::has('admin.resultados.foto.ver'))
                                        <a href="{{ route('admin.resultados.foto.ver', $f) }}" target="_blank">
                                            <img class="img-fluid"
                                                src="{{ route('admin.resultados.foto.ver', $f) }}?v={{ optional($f->updated_at)->timestamp ?? $f->id }}"
                                                alt="{{ $slot }}" loading="lazy">
                                        </a>
                                    @else
                                        <div class="text-center text-muted small py-4">Sin foto</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>


                    <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.pedidos.fotos_pdf', $pedido) }}"
                        target="_blank">PDF Fotos</a>
                </div>
            </div>
        </div>
    </div>
@endsection
