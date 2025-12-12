@php
    $fotosSeleccionadas         = $fotosSeleccionadas ?? [];
    $cefalometriasSeleccionadas = $cefalometriasSeleccionadas ?? [];
    $piezasSeleccionadas        = $piezasSeleccionadas ?? [];
@endphp

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            {{-- Clínica --}}
            <div class="col-md-4">
                <div class="form-group">
                    <label for="clinica_id">Clínica</label>
                    <select name="clinica_id" id="clinica_id" class="form-control @error('clinica_id') is-invalid @enderror" required>
                        <option value="">Seleccione...</option>
                        @foreach ($clinicas as $clinica)
                            <option value="{{ $clinica->id }}"
                                @selected(old('clinica_id', $pedido->clinica_id) == $clinica->id)>
                                {{ $clinica->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('clinica_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Paciente --}}
            <div class="col-md-4">
                <div class="form-group">
                    <label for="paciente_id">Paciente</label>
                    <select name="paciente_id" id="paciente_id" class="form-control @error('paciente_id') is-invalid @enderror" required>
                        <option value="">Seleccione...</option>
                        @foreach ($pacientes as $pac)
                            <option value="{{ $pac->id }}"
                                @selected(old('paciente_id', $pedido->paciente_id) == $pac->id)>
                                {{ $pac->apellido }} {{ $pac->nombre }} ({{ $pac->clinica->nombre ?? 'Sin clínica' }})
                            </option>
                        @endforeach
                    </select>
                    @error('paciente_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Prioridad --}}
            <div class="col-md-2">
                <div class="form-group">
                    <label for="prioridad">Prioridad</label>
                    <select name="prioridad" id="prioridad" class="form-control">
                        @foreach (['normal' => 'Normal', 'urgente' => 'Urgente'] as $val => $label)
                            <option value="{{ $val }}"
                                @selected(old('prioridad', $pedido->prioridad ?? 'normal') === $val)>
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
                    @error('doctor_nombre') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Teléfono --}}
            <div class="col-md-4">
                <div class="form-group">
                    <label for="doctor_telefono">Teléfono</label>
                    <input type="text" name="doctor_telefono" id="doctor_telefono"
                           class="form-control @error('doctor_telefono') is-invalid @enderror"
                           value="{{ old('doctor_telefono', $pedido->doctor_telefono) }}">
                    @error('doctor_telefono') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Email --}}
            <div class="col-md-4">
                <div class="form-group">
                    <label for="doctor_email">E-mail</label>
                    <input type="email" name="doctor_email" id="doctor_email"
                           class="form-control @error('doctor_email') is-invalid @enderror"
                           value="{{ old('doctor_email', $pedido->doctor_email) }}">
                    @error('doctor_email') <span class="invalid-feedback">{{ $message }}</span> @enderror
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
                    @error('direccion') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- CI del paciente (copia) --}}
            <div class="col-md-3">
                <div class="form-group">
                    <label for="paciente_documento">CI paciente</label>
                    <input type="text" name="paciente_documento" id="paciente_documento"
                           class="form-control @error('paciente_documento') is-invalid @enderror"
                           value="{{ old('paciente_documento', $pedido->paciente_documento) }}">
                    @error('paciente_documento') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Fecha/Hora agendada --}}
            <div class="col-md-3">
                <div class="form-group">
                    <label>Agendado para</label>
                    <div class="d-flex">
                        <input type="date" name="fecha_agendada"
                               class="form-control form-control-sm mr-1"
                               value="{{ old('fecha_agendada', optional($pedido->fecha_agendada)->format('Y-m-d')) }}">
                        <input type="time" name="hora_agendada"
                               class="form-control form-control-sm"
                               value="{{ old('hora_agendada', $pedido->hora_agendada ? $pedido->hora_agendada->format('H:i') : '') }}">
                    </div>
                </div>
            </div>
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
                    @foreach (['frente','perfil_derecho','perfil_izquierdo','sonriendo','frontal_oclusion'] as $foto)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fotos[]" id="foto_{{ $foto }}"
                                   value="{{ $foto }}"
                                   @checked(in_array($foto, old('fotos', $fotosSeleccionadas)))>
                            <label class="form-check-label" for="foto_{{ $foto }}">
                                {{ $fotosTipos[$foto] }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-6">
                    @foreach (['lateral_derecha','lateral_izquierda','oclusal_superior','oclusal_inferior'] as $foto)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="fotos[]" id="foto_{{ $foto }}"
                                   value="{{ $foto }}"
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
                    @foreach (['usp','unicamp','usp_unicamp','tweed','steiner','homem_neto'] as $c)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="cefalometrias[]" id="cefa_{{ $c }}"
                                   value="{{ $c }}"
                                   @checked(in_array($c, old('cefalometrias', $cefalometriasSeleccionadas)))>
                            <label class="form-check-label" for="cefa_{{ $c }}">
                                {{ $cefalometriasTipos[$c] }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-4">
                    @foreach (['downs','mcnamara','bimler','jarabak','profis'] as $c)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="cefalometrias[]" id="cefa_{{ $c }}"
                                   value="{{ $c }}"
                                   @checked(in_array($c, old('cefalometrias', $cefalometriasSeleccionadas)))>
                            <label class="form-check-label" for="cefa_{{ $c }}">
                                {{ $cefalometriasTipos[$c] }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-4">
                    @foreach (['ricketts','ricketts_frontal','petrovic','sassouni','schwarz','trevisi','valieri','rocabado','adenoides'] as $c)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="cefalometrias[]" id="cefa_{{ $c }}"
                                   value="{{ $c }}"
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
                        <input class="form-check-input" type="checkbox" name="intraoral_maxilar_superior" id="intraoral_maxilar_superior"
                               @checked(old('intraoral_maxilar_superior', $pedido->intraoral_maxilar_superior))>
                        <label class="form-check-label" for="intraoral_maxilar_superior">
                            Maxilar superior
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="intraoral_mandibula" id="intraoral_mandibula"
                               @checked(old('intraoral_mandibula', $pedido->intraoral_mandibula))>
                        <label class="form-check-label" for="intraoral_mandibula">
                            Mandíbula
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="intraoral_maxilar_mandibula_completa" id="intraoral_maxilar_mandibula_completa"
                               @checked(old('intraoral_maxilar_mandibula_completa', $pedido->intraoral_maxilar_mandibula_completa))>
                        <label class="form-check-label" for="intraoral_maxilar_mandibula_completa">
                            Maxilar y mandíbula completa
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="intraoral_modelo_con_base" id="intraoral_modelo_con_base"
                               @checked(old('intraoral_modelo_con_base', $pedido->intraoral_modelo_con_base))>
                        <label class="form-check-label" for="intraoral_modelo_con_base">
                            Modelo con base (Estudio)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="intraoral_modelo_sin_base" id="intraoral_modelo_sin_base"
                               @checked(old('intraoral_modelo_sin_base', $pedido->intraoral_modelo_sin_base))>
                        <label class="form-check-label" for="intraoral_modelo_sin_base">
                            Modelo sin base (Trabajo)
                        </label>
                    </div>
                </div>
            </div>
        </details>
    </div>
</div>

{{-- ===== RX + TOMOGRAFÍA + PIEZAS ===== --}}

<div class="card mb-3">
    <div class="card-body">
        <details open class="mb-3">
            <summary class="h6 mb-2">Exámenes radiográficos</summary>

            <div class="row">
                {{-- Panorámica --}}
                <div class="col-md-4">
                    <div class="font-weight-bold mb-2">Panorámica</div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_panoramica_convencional" id="rx_panoramica_convencional"
                               @checked(old('rx_panoramica_convencional', $pedido->rx_panoramica_convencional))>
                        <label class="form-check-label" for="rx_panoramica_convencional">Convencional</label>
                    </div>

                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="rx_panoramica_trazado_implante" id="rx_panoramica_trazado_implante"
                               @checked(old('rx_panoramica_trazado_implante', $pedido->rx_panoramica_trazado_implante))>
                        <label class="form-check-label" for="rx_panoramica_trazado_implante">
                            Con trazado p/ implante de región:
                        </label>
                    </div>
                    <input type="text" name="rx_panoramica_trazado_region"
                           class="form-control form-control-sm mb-1"
                           placeholder="Región"
                           value="{{ old('rx_panoramica_trazado_region', $pedido->rx_panoramica_trazado_region) }}">

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_panoramica_atm_boca_abierta_cerrada" id="rx_panoramica_atm_boca_abierta_cerrada"
                               @checked(old('rx_panoramica_atm_boca_abierta_cerrada', $pedido->rx_panoramica_atm_boca_abierta_cerrada))>
                        <label class="form-check-label" for="rx_panoramica_atm_boca_abierta_cerrada">
                            ATM (boca abierta y cerrada)
                        </label>
                    </div>
                </div>

                {{-- Teleradiografía --}}
                <div class="col-md-4">
                    <div class="font-weight-bold mb-2">Teleradiografía</div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_teleradiografia_lateral" id="rx_teleradiografia_lateral"
                               @checked(old('rx_teleradiografia_lateral', $pedido->rx_teleradiografia_lateral))>
                        <label class="form-check-label" for="rx_teleradiografia_lateral">Lateral</label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_teleradiografia_frontal_pa" id="rx_teleradiografia_frontal_pa"
                               @checked(old('rx_teleradiografia_frontal_pa', $pedido->rx_teleradiografia_frontal_pa))>
                        <label class="form-check-label" for="rx_teleradiografia_frontal_pa">Frontal (PA)</label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_teleradiografia_waters" id="rx_teleradiografia_waters"
                               @checked(old('rx_teleradiografia_waters', $pedido->rx_teleradiografia_waters))>
                        <label class="form-check-label" for="rx_teleradiografia_waters">Waters (senos de faces)</label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_teleradiografia_indice_carpal_edad_osea" id="rx_teleradiografia_indice_carpal_edad_osea"
                               @checked(old('rx_teleradiografia_indice_carpal_edad_osea', $pedido->rx_teleradiografia_indice_carpal_edad_osea))>
                        <label class="form-check-label" for="rx_teleradiografia_indice_carpal_edad_osea">
                            Índice carpal y edad ósea
                        </label>
                    </div>
                </div>

                {{-- Interproximal + Periapical --}}
                <div class="col-md-4">
                    <div class="font-weight-bold mb-2">Interproximal</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_interproximal_premolares_derecho" id="rx_interproximal_premolares_derecho"
                               @checked(old('rx_interproximal_premolares_derecho', $pedido->rx_interproximal_premolares_derecho))>
                        <label class="form-check-label" for="rx_interproximal_premolares_derecho">Pre-molares derecho</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_interproximal_premolares_izquierdo" id="rx_interproximal_premolares_izquierdo"
                               @checked(old('rx_interproximal_premolares_izquierdo', $pedido->rx_interproximal_premolares_izquierdo))>
                        <label class="form-check-label" for="rx_interproximal_premolares_izquierdo">Pre-molares izquierdo</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_interproximal_molares_derecho" id="rx_interproximal_molares_derecho"
                               @checked(old('rx_interproximal_molares_derecho', $pedido->rx_interproximal_molares_derecho))>
                        <label class="form-check-label" for="rx_interproximal_molares_derecho">Molares derecho</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="rx_interproximal_molares_izquierdo" id="rx_interproximal_molares_izquierdo"
                               @checked(old('rx_interproximal_molares_izquierdo', $pedido->rx_interproximal_molares_izquierdo))>
                        <label class="form-check-label" for="rx_interproximal_molares_izquierdo">Molares izquierdo</label>
                    </div>

                    <div class="font-weight-bold mb-1 mt-2">Periapical</div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_periapical_dientes_senalados" id="rx_periapical_dientes_senalados"
                               @checked(old('rx_periapical_dientes_senalados', $pedido->rx_periapical_dientes_senalados))>
                        <label class="form-check-label" for="rx_periapical_dientes_senalados">Dientes señalados</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="rx_periapical_status_radiografico" id="rx_periapical_status_radiografico"
                               @checked(old('rx_periapical_status_radiografico', $pedido->rx_periapical_status_radiografico))>
                        <label class="form-check-label" for="rx_periapical_status_radiografico">Status radiográfico (todos)</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="rx_periapical_tecnica_clark" id="rx_periapical_tecnica_clark"
                               @checked(old('rx_periapical_tecnica_clark', $pedido->rx_periapical_tecnica_clark))>
                        <label class="form-check-label" for="rx_periapical_tecnica_clark">Técnica de Clark</label>
                    </div>
                    <input type="text" name="rx_periapical_region"
                           class="form-control form-control-sm mb-1"
                           placeholder="Región"
                           value="{{ old('rx_periapical_region', $pedido->rx_periapical_region) }}">

                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rx_con_informe" id="rx_con_informe_1" value="1"
                                   @checked(old('rx_con_informe', $pedido->rx_con_informe ?? true))>
                            <label class="form-check-label" for="rx_con_informe_1">Con informe</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rx_con_informe" id="rx_con_informe_0" value="0"
                                   @checked(! old('rx_con_informe', $pedido->rx_con_informe ?? true))>
                            <label class="form-check-label" for="rx_con_informe_0">Sin informe</label>
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
                        <input class="form-check-input" type="checkbox" name="ct_maxilar_completa" id="ct_maxilar_completa"
                               @checked(old('ct_maxilar_completa', $pedido->ct_maxilar_completa))>
                        <label class="form-check-label" for="ct_maxilar_completa">Tomografía maxilar completa</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ct_mandibula_completa" id="ct_mandibula_completa"
                               @checked(old('ct_mandibula_completa', $pedido->ct_mandibula_completa))>
                        <label class="form-check-label" for="ct_mandibula_completa">Tomografía mandíbula completa</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ct_maxilar_arco_cigomatico" id="ct_maxilar_arco_cigomatico"
                               @checked(old('ct_maxilar_arco_cigomatico', $pedido->ct_maxilar_arco_cigomatico))>
                        <label class="form-check-label" for="ct_maxilar_arco_cigomatico">Maxilar completa arco cigomático</label>
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
                    <input type="text" name="ct_parcial_zona"
                           class="form-control form-control-sm mb-1"
                           placeholder="Zona"
                           value="{{ old('ct_parcial_zona', $pedido->ct_parcial_zona) }}">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ct_region_senalada_abajo" id="ct_region_senalada_abajo"
                               @checked(old('ct_region_senalada_abajo', $pedido->ct_region_senalada_abajo))>
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
                        <input class="form-check-input" type="checkbox" name="entrega_papel_fotografico" id="entrega_papel_fotografico"
                               @checked(old('entrega_papel_fotografico', $pedido->entrega_papel_fotografico))>
                        <label class="form-check-label" for="entrega_papel_fotografico">Papel fotográfico</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="entrega_dicom" id="entrega_dicom"
                               @checked(old('entrega_dicom', $pedido->entrega_dicom))>
                        <label class="form-check-label" for="entrega_dicom">Dicom</label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="entrega_software_visualizacion" id="entrega_software_visualizacion"
                               @checked(old('entrega_software_visualizacion', $pedido->entrega_software_visualizacion))>
                        <label class="form-check-label" for="entrega_software_visualizacion">
                            Software para visualización:
                        </label>
                    </div>
                    <input type="text" name="entrega_software_detalle"
                           class="form-control form-control-sm"
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

            <div class="d-flex align-items-center mb-2">
                <button type="button" class="btn btn-outline-primary btn-sm mr-2" id="btn-open-odontograma">
                    Abrir odontograma
                </button>
                <span class="small">
                    Piezas actuales:
                    <span id="piezas_codigos_resumen">
                        {{ $piezasSeleccionadas ? implode(', ', $piezasSeleccionadas) : 'Ninguna' }}
                    </span>
                </span>
            </div>

            <input type="hidden" name="piezas_codigos" id="piezas_codigos"
                   value="{{ old('piezas_codigos', $piezasSeleccionadas ? implode(',', $piezasSeleccionadas) : '') }}">
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
                    <input class="form-check-input" type="radio" name="documentacion_tipo" id="doc_{{ $val }}"
                           value="{{ $val }}"
                           @checked(old('documentacion_tipo', $pedido->documentacion_tipo) === $val)>
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
                        'finalidad_implantes'            => 'Implantes',
                        'finalidad_dientes_incluidos'    => 'Dientes incluidos',
                        'finalidad_terceros_molares'     => '3º molares',
                        'finalidad_supernumerarios'      => 'Localización de supernumerarios',
                    ] as $field => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="{{ $field }}" id="{{ $field }}"
                                   @checked(old($field, $pedido->{$field}))>
                            <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-6">
                    @foreach ([
                        'finalidad_perforacion_radicular' => 'Perforación radicular',
                        'finalidad_sospecha_fractura'     => 'Sospecha de fractura',
                        'finalidad_patologia'             => 'Patología',
                    ] as $field => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="{{ $field }}" id="{{ $field }}"
                                   @checked(old($field, $pedido->{$field}))>
                            <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </details>

        <div class="form-group">
            <label for="descripcion_caso">Describir el caso</label>
            <textarea name="descripcion_caso" id="descripcion_caso" rows="4"
                      class="form-control @error('descripcion_caso') is-invalid @enderror">{{ old('descripcion_caso', $pedido->descripcion_caso) }}</textarea>
            @error('descripcion_caso') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>
    </div>
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
