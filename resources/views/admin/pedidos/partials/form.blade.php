@php
    $fotosSeleccionadas = $fotosSeleccionadas ?? [];
    $cefalometriasSeleccionadas = $cefalometriasSeleccionadas ?? [];
    $piezasPeriapicalSeleccionadas = $piezasPeriapicalSeleccionadas ?? [];
    $piezasTomografiaSeleccionadas = $piezasTomografiaSeleccionadas ?? [];

    //
    $isAdmin = $isAdmin ?? (auth()->check() ? auth()->user()->hasRole('admin') : false);
    $consultas = $consultas ?? collect();

    $clinicaValor = old('clinica_id', $pedido->clinica_id ?? null);
    if (!$isAdmin && empty($clinicaValor)) {
        $clinicaValor = optional($clinicas->first())->id;
    }

    $pacienteValor = old('paciente_id', $pedido->paciente_id ?? null);
    $consultaValor = old('consulta_id', $pedido->consulta_id ?? null);

    // Datos compactos para JS (consultas y pacientes)
    $consultasData = $consultas
        ->map(function ($c) {
            return [
                'id' => $c->id,
                'paciente_id' => $c->paciente_id,
                'clinica_id' => $c->clinica_id,
                'label' =>
                    ($c->fecha_hora?->format('d/m/Y H:i') ?: '—') .
                    ' · ' .
                    \Illuminate\Support\Str::limit((string) $c->motivo_consulta, 70),
            ];
        })
        ->values();

    $pacientesData = collect($pacientes ?? [])
        ->map(function ($p) {
            return [
                'id' => $p->id,
                'clinica_id' => $p->clinica_id,
            ];
        })
        ->values();
@endphp



<style>
    /* CONTENEDOR GENERAL DEL MINI-ODONTOGRAMA (LO CENTRA EN LA COLUMNA) */
    .odontograma-mini-wrapper {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        /* centra horizontalmente todo el bloque */
        justify-content: center;
        text-align: center;
    }

    /* CAJA QUE CONTIENE LAS FILAS DE PIEZAS */
    .odontograma-mini {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        padding: .35rem .75rem;
        border-radius: .5rem;
        background-color: #f8fafc;
        box-shadow: 0 0 0 1px #e5e7eb inset;
    }

    .odontograma-mini-row {
        display: flex;
        align-items: center;
        justify-content: center;
        /* centra cada fila de botones */
        flex-wrap: nowrap;
        margin-bottom: .15rem;
    }

    .odontograma-mini-row .quadrant-label {
        font-weight: 600;
        font-size: .75rem;
        width: 18px;
        /* “D” e “I” alineados */
        text-align: center;
    }

    .odontograma-mini .pieza-btn {
        min-width: 30px;
        height: 26px;
        padding: 0;
        margin: 2px 2px;
        font-size: .7rem;
        line-height: 1;
    }

    .odontograma-mini .pieza-btn.active {
        background-color: #007bff;
        color: #fff;
    }
</style>

<div class="card mb-3">
    {{-- Código de pedido --}}
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="codigo_pedido">Código de pedido</label>
                <input type="text" id="codigo_pedido" class="form-control font-weight-bold text-primary"
                    value="{{ $pedido->exists ? $pedido->codigo_pedido : $codigoPedidoSugerido ?? 'Se generará al guardar' }}"
                    readonly>
                <small class="form-text text-muted">
                    Código generado automáticamente.
                </small>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            {{-- Clínica --}}
            <div class="col-md-4">
                <div class="form-group">
                    <label for="clinica_id">Clínica</label>

                    {{-- Si NO es admin: bloquear visualmente pero enviar el valor --}}
                    @if (!$isAdmin)
                        <input type="hidden" name="clinica_id" value="{{ (int) $clinicaValor }}">
                        <select id="clinica_id" class="form-control" disabled>
                            @foreach ($clinicas as $clinica)
                                <option value="{{ $clinica->id }}" @selected((int) $clinicaValor === (int) $clinica->id)>
                                    {{ $clinica->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Clínica asignada a tu usuario.</small>
                    @else
                        <select name="clinica_id" id="clinica_id"
                            class="form-control @error('clinica_id') is-invalid @enderror" required>
                            <option value="">Seleccione...</option>
                            @foreach ($clinicas as $clinica)
                                <option value="{{ $clinica->id }}" @selected((int) $clinicaValor === (int) $clinica->id)>
                                    {{ $clinica->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('clinica_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    @endif
                </div>
            </div>


            {{-- Paciente --}}
            <div class="col-md-4">
                <div class="form-group">
                    <label for="paciente_id">Paciente</label>
                    <select name="paciente_id" id="paciente_id"
                        class="form-control @error('paciente_id') is-invalid @enderror" required>
                        <option value="">Seleccione...</option>
                        @foreach ($pacientes as $pac)
                            <option value="{{ $pac->id }}" @selected(old('paciente_id', $pedido->paciente_id) == $pac->id)>
                                {{ $pac->apellido }} {{ $pac->nombre }} ({{ $pac->clinica->nombre ?? 'Sin clínica' }})
                            </option>
                        @endforeach
                    </select>
                    @error('paciente_id')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="row">
                {{-- Consulta --}}
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="consulta_id">Consulta (del paciente)</label>
                        <select name="consulta_id" id="consulta_id"
                            class="form-control @error('consulta_id') is-invalid @enderror">
                            <option value="">-- Seleccione paciente primero --</option>
                        </select>
                        @error('consulta_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            La consulta se filtra automáticamente según el paciente seleccionado.
                        </small>
                    </div>
                </div>
            </div>

            {{-- Prioridad --}}
            <div class="col-md-2">
                <div class="form-group">
                    <label for="prioridad">Prioridad</label>
                    <select name="prioridad" id="prioridad" class="form-control">
                        @foreach (['normal' => 'Normal', 'urgente' => 'Urgente'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('prioridad', $pedido->prioridad ?? 'normal') === $val)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Estado solo en edición (opcional, por ahora lo dejamos fuera del form) --}}
        </div>

        <div class="row">
            {{-- Doctor --}}
            <div class="col-md-4">
                <div class="form-group">
                    <label for="doctor_nombre">Dr(a)</label>
                    <input type="text" name="doctor_nombre" id="doctor_nombre"
                        class="form-control @error('doctor_nombre') is-invalid @enderror"
                        value="{{ old('doctor_nombre', $pedido->doctor_nombre) }}">
                    @error('doctor_nombre')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Teléfono --}}
            <div class="col-md-4">
                <div class="form-group">
                    <label for="doctor_telefono">Teléfono</label>
                    <input type="text" name="doctor_telefono" id="doctor_telefono"
                        class="form-control @error('doctor_telefono') is-invalid @enderror"
                        value="{{ old('doctor_telefono', $pedido->doctor_telefono) }}">
                    @error('doctor_telefono')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Email --}}
            <div class="col-md-4">
                <div class="form-group">
                    <label for="doctor_email">E-mail</label>
                    <input type="email" name="doctor_email" id="doctor_email"
                        class="form-control @error('doctor_email') is-invalid @enderror"
                        value="{{ old('doctor_email', $pedido->doctor_email) }}">
                    @error('doctor_email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Dirección --}}
            <div class="col-md-6">
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <input type="text" name="direccion" id="direccion"
                        class="form-control @error('direccion') is-invalid @enderror"
                        value="{{ old('direccion', $pedido->direccion) }}">
                    @error('direccion')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- CI del paciente (copia) --}}
            <div class="col-md-3">
                <div class="form-group">
                    <label for="paciente_documento">CI paciente</label>
                    <input type="text" name="paciente_documento" id="paciente_documento"
                        class="form-control @error('paciente_documento') is-invalid @enderror"
                        value="{{ old('paciente_documento', $pedido->paciente_documento) }}">
                    @error('paciente_documento')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Fecha/Hora agendada --}}
            <div class="col-md-3">
                <div class="form-group">
                    <label>Agendado para</label>
                    <div class="d-flex">
                        <input type="date" name="fecha_agendada" class="form-control form-control-sm mr-1"
                            value="{{ old('fecha_agendada', optional($pedido->fecha_agendada)->format('Y-m-d')) }}">
                        <input type="time" name="hora_agendada" class="form-control form-control-sm"
                            value="{{ old('hora_agendada', $pedido->hora_agendada ? $pedido->hora_agendada->format('H:i') : '') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- ===== RX + TOMOGRAFÍA + PIEZAS ===== --}}

<div class="card mb-3">
    <div class="card-body">
        <details open class="mb-3">
            <summary class="h6 mb-2">Exámenes radiográficos</summary>

            {{-- PRIMERA FILA: 3 COLUMNAS (PANORÁMICA / TELERADIOGRAFÍA / INTERPROXIMAL+DATOS PERIAPICAL) --}}
            <div class="row">
                {{-- Panorámica --}}
                <div class="col-md-4">
                    <div class="font-weight-bold mb-2">Panorámica</div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_panoramica_convencional"
                            id="rx_panoramica_convencional" @checked(old('rx_panoramica_convencional', $pedido->rx_panoramica_convencional))>
                        <label class="form-check-label" for="rx_panoramica_convencional">Convencional</label>
                    </div>

                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="rx_panoramica_trazado_implante"
                            id="rx_panoramica_trazado_implante" @checked(old('rx_panoramica_trazado_implante', $pedido->rx_panoramica_trazado_implante))>
                        <label class="form-check-label" for="rx_panoramica_trazado_implante">
                            Con trazado p/ implante de región:
                        </label>
                    </div>
                    <input type="text" name="rx_panoramica_trazado_region"
                        class="form-control form-control-sm mb-1" placeholder="Región"
                        value="{{ old('rx_panoramica_trazado_region', $pedido->rx_panoramica_trazado_region) }}">

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_panoramica_atm_boca_abierta_cerrada"
                            id="rx_panoramica_atm_boca_abierta_cerrada" @checked(old('rx_panoramica_atm_boca_abierta_cerrada', $pedido->rx_panoramica_atm_boca_abierta_cerrada))>
                        <label class="form-check-label" for="rx_panoramica_atm_boca_abierta_cerrada">
                            ATM (boca abierta y cerrada)
                        </label>
                    </div>
                </div>

                {{-- Teleradiografía --}}
                <div class="col-md-4">
                    <div class="font-weight-bold mb-2">Teleradiografía</div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_teleradiografia_lateral"
                            id="rx_teleradiografia_lateral" @checked(old('rx_teleradiografia_lateral', $pedido->rx_teleradiografia_lateral))>
                        <label class="form-check-label" for="rx_teleradiografia_lateral">Lateral</label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_teleradiografia_frontal_pa"
                            id="rx_teleradiografia_frontal_pa" @checked(old('rx_teleradiografia_frontal_pa', $pedido->rx_teleradiografia_frontal_pa))>
                        <label class="form-check-label" for="rx_teleradiografia_frontal_pa">Frontal (PA)</label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_teleradiografia_waters"
                            id="rx_teleradiografia_waters" @checked(old('rx_teleradiografia_waters', $pedido->rx_teleradiografia_waters))>
                        <label class="form-check-label" for="rx_teleradiografia_waters">Waters (senos de
                            faces)</label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                            name="rx_teleradiografia_indice_carpal_edad_osea"
                            id="rx_teleradiografia_indice_carpal_edad_osea" @checked(old('rx_teleradiografia_indice_carpal_edad_osea', $pedido->rx_teleradiografia_indice_carpal_edad_osea))>
                        <label class="form-check-label" for="rx_teleradiografia_indice_carpal_edad_osea">
                            Índice carpal y edad ósea
                        </label>
                    </div>
                </div>

                {{-- Interproximal + datos básicos de Periapical (sin cuadrícula) --}}
                <div class="col-md-4">
                    <div class="font-weight-bold mb-2">Interproximal</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_interproximal_premolares_derecho"
                            id="rx_interproximal_premolares_derecho" @checked(old('rx_interproximal_premolares_derecho', $pedido->rx_interproximal_premolares_derecho))>
                        <label class="form-check-label" for="rx_interproximal_premolares_derecho">Pre-molares
                            derecho</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_interproximal_premolares_izquierdo"
                            id="rx_interproximal_premolares_izquierdo" @checked(old('rx_interproximal_premolares_izquierdo', $pedido->rx_interproximal_premolares_izquierdo))>
                        <label class="form-check-label" for="rx_interproximal_premolares_izquierdo">Pre-molares
                            izquierdo</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_interproximal_molares_derecho"
                            id="rx_interproximal_molares_derecho" @checked(old('rx_interproximal_molares_derecho', $pedido->rx_interproximal_molares_derecho))>
                        <label class="form-check-label" for="rx_interproximal_molares_derecho">Molares derecho</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="rx_interproximal_molares_izquierdo"
                            id="rx_interproximal_molares_izquierdo" @checked(old('rx_interproximal_molares_izquierdo', $pedido->rx_interproximal_molares_izquierdo))>
                        <label class="form-check-label" for="rx_interproximal_molares_izquierdo">Molares
                            izquierdo</label>
                    </div>

                    <div class="font-weight-bold mb-1 mt-2">Periapical</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_periapical_dientes_senalados"
                            id="rx_periapical_dientes_senalados" @checked(old('rx_periapical_dientes_senalados', $pedido->rx_periapical_dientes_senalados))>
                        <label class="form-check-label" for="rx_periapical_dientes_senalados">Dientes
                            señalados</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_periapical_status_radiografico"
                            id="rx_periapical_status_radiografico" @checked(old('rx_periapical_status_radiografico', $pedido->rx_periapical_status_radiografico))>
                        <label class="form-check-label" for="rx_periapical_status_radiografico">Status radiográfico
                            (todos)</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="rx_periapical_tecnica_clark"
                            id="rx_periapical_tecnica_clark" @checked(old('rx_periapical_tecnica_clark', $pedido->rx_periapical_tecnica_clark))>
                        <label class="form-check-label" for="rx_periapical_tecnica_clark">Técnica de Clark</label>
                    </div>
                    <input type="text" name="rx_periapical_region" class="form-control form-control-sm mb-1"
                        placeholder="Región"
                        value="{{ old('rx_periapical_region', $pedido->rx_periapical_region) }}">
                </div>
            </div>

            {{-- SEGUNDA FILA: CUADRÍCULA DE PIEZAS CENTRADA A TODO EL ANCHO --}}
            <div class="row mt-2">
                <div class="col-12">
                    <div class="odontograma-mini-wrapper">
                        <div class="small font-weight-bold mb-1">
                            Piezas periapicales (clic para seleccionar)
                        </div>

                        <div id="grid-piezas-periapical" class="odontograma-mini">

                            {{-- Arcada superior permanente: 18–28 --}}
                            <div class="odontograma-mini-row">
                                <span class="quadrant-label mr-1">D</span>
                                @foreach ([18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28] as $pieza)
                                    @php $piezaStr = (string) $pieza; @endphp
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary pieza-btn {{ in_array($piezaStr, $piezasPeriapicalSeleccionadas) ? 'active' : '' }}"
                                        data-pieza="{{ $piezaStr }}">
                                        {{ $piezaStr }}
                                    </button>
                                @endforeach
                                <span class="quadrant-label ml-1">I</span>
                            </div>

                            {{-- Arcada inferior permanente: 48–38 --}}
                            <div class="odontograma-mini-row">
                                <span class="quadrant-label mr-1">D</span>
                                @foreach ([48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38] as $pieza)
                                    @php $piezaStr = (string) $pieza; @endphp
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary pieza-btn {{ in_array($piezaStr, $piezasPeriapicalSeleccionadas) ? 'active' : '' }}"
                                        data-pieza="{{ $piezaStr }}">
                                        {{ $piezaStr }}
                                    </button>
                                @endforeach
                                <span class="quadrant-label ml-1">I</span>
                            </div>

                            {{-- Arcada superior dentición mixta: 55–65 --}}
                            <div class="odontograma-mini-row">
                                <span class="quadrant-label mr-1">D</span>
                                @foreach ([55, 54, 53, 52, 51, 61, 62, 63, 64, 65] as $pieza)
                                    @php $piezaStr = (string) $pieza; @endphp
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary pieza-btn {{ in_array($piezaStr, $piezasPeriapicalSeleccionadas) ? 'active' : '' }}"
                                        data-pieza="{{ $piezaStr }}">
                                        {{ $piezaStr }}
                                    </button>
                                @endforeach
                                <span class="quadrant-label ml-1">I</span>
                            </div>

                            {{-- Arcada inferior dentición mixta: 85–75 --}}
                            <div class="odontograma-mini-row">
                                <span class="quadrant-label mr-1">D</span>
                                @foreach ([85, 84, 83, 82, 81, 71, 72, 73, 74, 75] as $pieza)
                                    @php $piezaStr = (string) $pieza; @endphp
                                    <button type="button"
                                        class="btn btn-sm btn-outline-secondary pieza-btn {{ in_array($piezaStr, $piezasPeriapicalSeleccionadas) ? 'active' : '' }}"
                                        data-pieza="{{ $piezaStr }}">
                                        {{ $piezaStr }}
                                    </button>
                                @endforeach
                                <span class="quadrant-label ml-1">I</span>
                            </div>
                        </div>

                        {{-- hidden para guardar en BD --}}
                        <input type="hidden" name="piezas_periapical_codigos" id="piezas_periapical_codigos"
                            value="{{ old('piezas_periapical_codigos', $piezasPeriapicalSeleccionadas ? implode(',', $piezasPeriapicalSeleccionadas) : '') }}">

                        {{-- caja de texto visible con las piezas seleccionadas --}}
                        <input type="text" id="piezas_periapical_resumen"
                            class="form-control form-control-sm mt-2 text-center"
                            value="{{ old('piezas_periapical_codigos', $piezasPeriapicalSeleccionadas ? implode(', ', $piezasPeriapicalSeleccionadas) : '') }}"
                            readonly>

                        <small class="form-text text-muted mt-1">
                            Las piezas seleccionadas se guardan como periapicales y se reflejan también en esta caja de
                            texto.
                        </small>

                        <div class="mt-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rx_con_informe"
                                    id="rx_con_informe_1" value="1" @checked(old('rx_con_informe', $pedido->rx_con_informe ?? true))>
                                <label class="form-check-label" for="rx_con_informe_1">Con informe</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rx_con_informe"
                                    id="rx_con_informe_0" value="0" @checked(!old('rx_con_informe', $pedido->rx_con_informe ?? true))>
                                <label class="form-check-label" for="rx_con_informe_0">Sin informe</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </details>


        <details class="mb-3">
            <summary class="h6 mb-2">Tomografía computarizada de alta resolución</summary>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ct_maxilar_completa"
                            id="ct_maxilar_completa" @checked(old('ct_maxilar_completa', $pedido->ct_maxilar_completa))>
                        <label class="form-check-label" for="ct_maxilar_completa">Tomografía maxilar completa</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ct_mandibula_completa"
                            id="ct_mandibula_completa" @checked(old('ct_mandibula_completa', $pedido->ct_mandibula_completa))>
                        <label class="form-check-label" for="ct_mandibula_completa">Tomografía mandíbula
                            completa</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ct_maxilar_arco_cigomatico"
                            id="ct_maxilar_arco_cigomatico" @checked(old('ct_maxilar_arco_cigomatico', $pedido->ct_maxilar_arco_cigomatico))>
                        <label class="form-check-label" for="ct_maxilar_arco_cigomatico">Maxilar completa arco
                            cigomático</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ct_atm" id="ct_atm"
                            @checked(old('ct_atm', $pedido->ct_atm))>
                        <label class="form-check-label" for="ct_atm">Tomografía de ATM</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="ct_parcial" id="ct_parcial"
                            @checked(old('ct_parcial', $pedido->ct_parcial))>
                        <label class="form-check-label" for="ct_parcial">Tomografía parcial (zona):</label>
                    </div>
                    <input type="text" name="ct_parcial_zona" class="form-control form-control-sm mb-1"
                        placeholder="Zona" value="{{ old('ct_parcial_zona', $pedido->ct_parcial_zona) }}">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ct_region_senalada_abajo"
                            id="ct_region_senalada_abajo" @checked(old('ct_region_senalada_abajo', $pedido->ct_region_senalada_abajo))>
                        <label class="form-check-label" for="ct_region_senalada_abajo">
                            Tomografía región señaladas abajo
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="font-weight-bold mb-1">Formas de entrega</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="entrega_pdf" id="entrega_pdf"
                            @checked(old('entrega_pdf', $pedido->entrega_pdf))>
                        <label class="form-check-label" for="entrega_pdf">Digital (PDF)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="entrega_papel_fotografico"
                            id="entrega_papel_fotografico" @checked(old('entrega_papel_fotografico', $pedido->entrega_papel_fotografico))>
                        <label class="form-check-label" for="entrega_papel_fotografico">Papel fotográfico</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="entrega_dicom" id="entrega_dicom"
                            @checked(old('entrega_dicom', $pedido->entrega_dicom))>
                        <label class="form-check-label" for="entrega_dicom">Dicom</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="entrega_software_visualizacion"
                            id="entrega_software_visualizacion" @checked(old('entrega_software_visualizacion', $pedido->entrega_software_visualizacion))>
                        <label class="form-check-label" for="entrega_software_visualizacion">
                            Software para visualización:
                        </label>
                    </div>
                    <input type="text" name="entrega_software_detalle" class="form-control form-control-sm"
                        placeholder="Nombre del software"
                        value="{{ old('entrega_software_detalle', $pedido->entrega_software_detalle) }}">
                </div>
            </div>
        </details>
        {{-- Selector de piezas (odontograma) --}}
        <details class="mb-3">
            <summary class="h6 mb-2">Odontograma – piezas seleccionadas</summary>
            <p class="small text-muted mb-1">
                Seleccione las piezas en el odontograma 3D.
            </p>

            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center mb-2">
                <button type="button" class="btn btn-outline-primary btn-sm mr-sm-2 mb-2 mb-sm-0"
                    id="btn-open-odontograma">
                    Abrir odontograma
                </button>

                <div class="flex-grow-1">
                    <label for="piezas_tomografia_resumen" class="small mb-1 d-block">
                        Piezas actuales
                    </label>
                    <input type="text" id="piezas_tomografia_resumen" class="form-control form-control-sm"
                        value="{{ $piezasTomografiaSeleccionadas ? implode(', ', $piezasTomografiaSeleccionadas) : 'Ninguna' }}"
                        readonly>
                </div>
            </div>

            {{-- Este hidden es el que se envía al controlador --}}
            <input type="hidden" name="piezas_tomografia_codigos" id="piezas_tomografia_codigos"
                value="{{ old('piezas_tomografia_codigos', $piezasTomografiaSeleccionadas ? implode(',', $piezasTomografiaSeleccionadas) : '') }}">
        </details>



        <div class="form-group">
            <label for="descripcion_caso">Describir el caso</label>
            <textarea name="descripcion_caso" id="descripcion_caso" rows="4"
                class="form-control @error('descripcion_caso') is-invalid @enderror">{{ old('descripcion_caso', $pedido->descripcion_caso) }}</textarea>
            @error('descripcion_caso')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

{{-- ===== INFORMACIÓN ADICIONAL (FOTOS / CEFALO / EXAMENES COMPLEMENTARIOS) ===== --}}

<div class="card mb-3">
    <div class="card-body">
        <details open class="mb-3">
            <summary class="h6 mb-2">Información adicional – Fotos</summary>
            <div class="row">
                <div class="col-md-6">
                    @foreach (['frente', 'perfil_derecho', 'perfil_izquierdo', 'sonriendo', 'frontal_oclusion'] as $foto)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fotos[]"
                                id="foto_{{ $foto }}" value="{{ $foto }}"
                                @checked(in_array($foto, old('fotos', $fotosSeleccionadas)))>
                            <label class="form-check-label" for="foto_{{ $foto }}">
                                {{ $fotosTipos[$foto] }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-6">
                    @foreach (['lateral_derecha', 'lateral_izquierda', 'oclusal_superior', 'oclusal_inferior'] as $foto)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fotos[]"
                                id="foto_{{ $foto }}" value="{{ $foto }}"
                                @checked(in_array($foto, old('fotos', $fotosSeleccionadas)))>
                            <label class="form-check-label" for="foto_{{ $foto }}">
                                {{ $fotosTipos[$foto] }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </details>

        <details class="mb-3">
            <summary class="h6 mb-2">Estudios cefalométricos</summary>
            <div class="row">
                <div class="col-md-4">
                    @foreach (['usp', 'unicamp', 'usp_unicamp', 'tweed', 'steiner', 'homem_neto'] as $c)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="cefalometrias[]"
                                id="cefa_{{ $c }}" value="{{ $c }}"
                                @checked(in_array($c, old('cefalometrias', $cefalometriasSeleccionadas)))>
                            <label class="form-check-label" for="cefa_{{ $c }}">
                                {{ $cefalometriasTipos[$c] }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-4">
                    @foreach (['downs', 'mcnamara', 'bimler', 'jarabak', 'profis'] as $c)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="cefalometrias[]"
                                id="cefa_{{ $c }}" value="{{ $c }}"
                                @checked(in_array($c, old('cefalometrias', $cefalometriasSeleccionadas)))>
                            <label class="form-check-label" for="cefa_{{ $c }}">
                                {{ $cefalometriasTipos[$c] }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-4">
                    @foreach (['ricketts', 'ricketts_frontal', 'petrovic', 'sassouni', 'schwarz', 'trevisi', 'valieri', 'rocabado', 'adenoides'] as $c)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="cefalometrias[]"
                                id="cefa_{{ $c }}" value="{{ $c }}"
                                @checked(in_array($c, old('cefalometrias', $cefalometriasSeleccionadas)))>
                            <label class="form-check-label" for="cefa_{{ $c }}">
                                {{ $cefalometriasTipos[$c] }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </details>

        <details class="mb-3">
            <summary class="h6 mb-2">Exámenes complementarios – Escaneamiento intraoral</summary>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="intraoral_maxilar_superior"
                            id="intraoral_maxilar_superior" @checked(old('intraoral_maxilar_superior', $pedido->intraoral_maxilar_superior))>
                        <label class="form-check-label" for="intraoral_maxilar_superior">
                            Maxilar superior
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="intraoral_mandibula"
                            id="intraoral_mandibula" @checked(old('intraoral_mandibula', $pedido->intraoral_mandibula))>
                        <label class="form-check-label" for="intraoral_mandibula">
                            Mandíbula
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="intraoral_maxilar_mandibula_completa"
                            id="intraoral_maxilar_mandibula_completa" @checked(old('intraoral_maxilar_mandibula_completa', $pedido->intraoral_maxilar_mandibula_completa))>
                        <label class="form-check-label" for="intraoral_maxilar_mandibula_completa">
                            Maxilar y mandíbula completa
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="intraoral_modelo_con_base"
                            id="intraoral_modelo_con_base" @checked(old('intraoral_modelo_con_base', $pedido->intraoral_modelo_con_base))>
                        <label class="form-check-label" for="intraoral_modelo_con_base">
                            Modelo con base (Estudio)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="intraoral_modelo_sin_base"
                            id="intraoral_modelo_sin_base" @checked(old('intraoral_modelo_sin_base', $pedido->intraoral_modelo_sin_base))>
                        <label class="form-check-label" for="intraoral_modelo_sin_base">
                            Modelo sin base (Trabajo)
                        </label>
                    </div>
                </div>
            </div>
        </details>
    </div>
</div>


{{-- ===== DOCUMENTACIÓN + FINALIDAD + DESCRIPCIÓN ===== --}}

<div class="card mb-3">
    <div class="card-body">
        <details open class="mb-3">
            <summary class="h6 mb-2">Documentación</summary>
            @foreach ($documentaciones as $val => $label)
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="documentacion_tipo"
                        id="doc_{{ $val }}" value="{{ $val }}" @checked(old('documentacion_tipo', $pedido->documentacion_tipo) === $val)>
                    <label class="form-check-label" for="doc_{{ $val }}">
                        {{ $label }}
                    </label>
                </div>
            @endforeach
        </details>

        <details open class="mb-3">
            <summary class="h6 mb-2">Finalidad del examen</summary>
            <div class="row">
                <div class="col-md-6">
                    @foreach ([
        'finalidad_implantes' => 'Implantes',
        'finalidad_dientes_incluidos' => 'Dientes incluidos',
        'finalidad_terceros_molares' => '3º molares',
        'finalidad_supernumerarios' => 'Localización de supernumerarios',
    ] as $field => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="{{ $field }}"
                                id="{{ $field }}" @checked(old($field, $pedido->{$field}))>
                            <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-6">
                    @foreach ([
        'finalidad_perforacion_radicular' => 'Perforación radicular',
        'finalidad_sospecha_fractura' => 'Sospecha de fractura',
        'finalidad_patologia' => 'Patología',
    ] as $field => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="{{ $field }}"
                                id="{{ $field }}" @checked(old($field, $pedido->{$field}))>
                            <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </details>


    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ───────────────────────────────
            // PERIAPICAL (cuadrícula)
            // ───────────────────────────────
            const grid = document.getElementById('grid-piezas-periapical');
            const inputPeriapical = document.getElementById('piezas_periapical_codigos');
            const resumenPeriapical = document.getElementById('piezas_periapical_resumen');

            if (grid && inputPeriapical) {
                const piezasSet = new Set(
                    (inputPeriapical.value || '')
                    .split(',')
                    .map(p => p.trim())
                    .filter(p => p !== '')
                );

                function actualizarPeriapical() {
                    const arr = Array.from(piezasSet).sort((a, b) => a.localeCompare(b));
                    inputPeriapical.value = arr.join(',');

                    if (resumenPeriapical) {
                        resumenPeriapical.value = arr.join(', ');
                    }
                }

                grid.querySelectorAll('.pieza-btn').forEach(btn => {
                    const code = String(btn.dataset.pieza || '').trim();
                    if (!code) return;

                    if (piezasSet.has(code)) {
                        btn.classList.add('active');
                    }

                    btn.addEventListener('click', function() {
                        const pieza = String(this.dataset.pieza || '').trim();
                        if (!pieza) return;

                        if (piezasSet.has(pieza)) {
                            piezasSet.delete(pieza);
                            this.classList.remove('active');
                        } else {
                            piezasSet.add(pieza);
                            this.classList.add('active');
                        }

                        actualizarPeriapical();
                    });
                });

                actualizarPeriapical();
            }

            // ───────────────────────────────
            // TOMOGRAFÍA – abrir modal
            // ───────────────────────────────
            const btnOdonto = document.getElementById('btn-open-odontograma');
            if (btnOdonto && window.jQuery) {
                btnOdonto.addEventListener('click', function() {
                    if ($('#odontogramaModal').length) {
                        $('#odontogramaModal').modal('show');
                    }
                });
            }

            // ───────────────────────────────
            // TOMOGRAFÍA – función global para que el modal envíe las piezas
            // ───────────────────────────────
            window.syncPiezasTomografiaDesdeModal = function(codigos) {
                const inputTom = document.getElementById('piezas_tomografia_codigos');
                const resumenTom = document.getElementById('piezas_tomografia_resumen');

                const arr = (codigos || [])
                    .map(c => String(c).trim())
                    .filter(c => c !== '')
                    .filter((val, idx, self) => self.indexOf(val) === idx)
                    .sort((a, b) => a.localeCompare(b));

                if (inputTom) {
                    inputTom.value = arr.join(',');
                }

                if (resumenTom) {
                    resumenTom.textContent = arr.length ? arr.join(', ') : 'Ninguna';
                }
            };
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // El modal llamará a esta función pasándole un array de códigos (['15', '12', ...])
            window.syncPiezasTomografiaDesdeModal = function(codigos) {
                const inputTom = document.getElementById('piezas_tomografia_codigos');
                const resumenTxt = document.getElementById('piezas_tomografia_resumen');

                const arr = (codigos || [])
                    .map(c => String(c).trim())
                    .filter(c => c !== '')
                    .filter((val, idx, self) => self.indexOf(val) === idx)
                    .sort((a, b) => a.localeCompare(b));

                const csv = arr.join(',');

                if (inputTom) {
                    inputTom.value = csv; // lo que se guarda en BD
                }

                if (resumenTxt) {
                    // caja de texto visible
                    resumenTxt.value = arr.length ? arr.join(', ') : 'Ninguna';
                }
            };
        });
    </script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const isAdmin = @json((bool) $isAdmin);
        const consultas = @json($consultasData);
        const pacientes = @json($pacientesData);
    
        const selClinica  = document.getElementById('clinica_id');
        const selPaciente = document.getElementById('paciente_id');
        const selConsulta = document.getElementById('consulta_id');
    
        const consultaInicial = @json($consultaValor ? (int)$consultaValor : null);
    
        function rebuildConsultas(pacienteId, selectedId = null) {
            if (!selConsulta) return;
    
            selConsulta.innerHTML = '';
            const opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = pacienteId ? '-- Seleccione consulta --' : '-- Seleccione paciente primero --';
            selConsulta.appendChild(opt0);
    
            if (!pacienteId) return;
    
            const list = consultas.filter(c => String(c.paciente_id) === String(pacienteId));
    
            if (!list.length) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Sin consultas para este paciente';
                selConsulta.appendChild(opt);
                return;
            }
    
            list.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.label;
                if (selectedId && String(selectedId) === String(c.id)) {
                    opt.selected = true;
                }
                selConsulta.appendChild(opt);
            });
        }
    
        function filterPacientesByClinica() {
            // Solo útil si admin puede cambiar clínica
            if (!isAdmin || !selClinica || !selPaciente) return;
    
            const clinicaId = selClinica.value ? String(selClinica.value) : '';
            const currentPaciente = selPaciente.value ? String(selPaciente.value) : '';
    
            // Mostrar/ocultar options de pacientes
            [...selPaciente.options].forEach(opt => {
                if (!opt.value) return; // placeholder
                const p = pacientes.find(x => String(x.id) === String(opt.value));
                if (!p) return;
    
                const visible = !clinicaId || String(p.clinica_id) === clinicaId;
                opt.hidden = !visible;
            });
    
            // Si el paciente actual ya no pertenece a la clínica seleccionada, limpiamos
            if (currentPaciente) {
                const p = pacientes.find(x => String(x.id) === currentPaciente);
                if (p && clinicaId && String(p.clinica_id) !== clinicaId) {
                    selPaciente.value = '';
                    rebuildConsultas(null, null);
                }
            }
        }
    
        // Eventos
        if (selPaciente) {
            selPaciente.addEventListener('change', function () {
                // al cambiar paciente: limpiar consulta y recargar
                rebuildConsultas(this.value || null, null);
            });
        }
    
        if (selClinica) {
            selClinica.addEventListener('change', function () {
                filterPacientesByClinica();
                // clínica cambió: también resetea consultas
                rebuildConsultas(selPaciente?.value || null, null);
            });
        }
    
        // Inicial (edit / old)
        filterPacientesByClinica();
        rebuildConsultas(selPaciente?.value || null, consultaInicial);
    });
    </script>
    

</div>

<div class="d-flex justify-content-between mb-5">
    <a href="{{ route('admin.pedidos.index') }}" class="btn btn-outline-secondary">
        Cancelar
    </a>
    <button type="submit" class="btn btn-primary">
        @if ($modo === 'edit')
            Guardar cambios
        @else
            Guardar pedido
        @endif
    </button>
</div>
