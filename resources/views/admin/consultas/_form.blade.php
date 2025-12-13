@csrf

@php
    $u = auth()->user();
    $isAdmin   = $u && $u->hasRole('admin');
    $esClinica = $u && $u->hasRole('clinica');

    // Clínica fija para rol clínica
    $clinicaFijaId = $esClinica ? (int) ($u->clinica_id ?? 0) : 0;
    $clinicaFija   = null;

    if ($esClinica && $clinicaFijaId) {
        $clinicaFija = ($clinicas ?? collect())->firstWhere('id', $clinicaFijaId);
    }

    $fechaValor = old('fecha_hora', optional($consulta->fecha_hora ?? now())->format('Y-m-d\TH:i'));
    $pacienteSeleccionado = (int) old('paciente_id', $consulta->paciente_id ?? ($pacienteId ?? 0));

    // Si viene paciente preseleccionado, intentamos inferir su clínica (para admin)
    $pacienteObj = ($pacientes ?? collect())->firstWhere('id', $pacienteSeleccionado);
    $pacienteClinicaId = (int) optional($pacienteObj)->clinica_id;

    // Clínica seleccionada (admin): old > consulta > clínica del paciente preseleccionado
    $clinicaSeleccionada = (int) old('clinica_id', $consulta->clinica_id ?? $pacienteClinicaId);
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="clinica_id">Clínica</label>

            @if($esClinica)
                {{-- Rol clínica: clínica fija --}}
                <input type="hidden" name="clinica_id" value="{{ $clinicaFijaId }}">

                <input type="text" class="form-control"
                       value="{{ $clinicaFija->nombre ?? 'Tu clínica (no asignada)' }}"
                       readonly>

                @error('clinica_id')
                    <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
            @else
                {{-- Admin/otros: puede elegir --}}
                <select name="clinica_id" id="clinica_id"
                        class="form-control @error('clinica_id') is-invalid @enderror" required>
                    <option value="">-- Seleccione clínica --</option>
                    @foreach(($clinicas ?? collect()) as $clinica)
                        <option value="{{ $clinica->id }}"
                            {{ $clinicaSeleccionada === (int) $clinica->id ? 'selected' : '' }}>
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

    <div class="col-md-4">
        <div class="form-group">
            <label for="paciente_id">Paciente</label>
            <select name="paciente_id" id="paciente_id"
                    class="form-control @error('paciente_id') is-invalid @enderror" required>
                <option value="">-- Seleccione paciente --</option>

                @foreach(($pacientes ?? collect()) as $p)
                    @php
                        $pid = (int) $p->id;
                        $cid = (int) ($p->clinica_id ?? 0);
                        $selected = ($pacienteSeleccionado === $pid);
                    @endphp

                    <option value="{{ $pid }}"
                            data-clinica="{{ $cid }}"
                            {{ $selected ? 'selected' : '' }}>
                        {{ $p->nombre }} {{ $p->apellido }}
                        @if(!$esClinica && $p->clinica)
                            ({{ $p->clinica->nombre }})
                        @endif
                    </option>
                @endforeach
            </select>

            @error('paciente_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror

            @if(!$esClinica)
                <small class="form-text text-muted">
                    El listado se filtra por clínica seleccionada.
                </small>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="fecha_hora">Fecha y hora</label>
            <input type="datetime-local" name="fecha_hora" id="fecha_hora"
                   class="form-control @error('fecha_hora') is-invalid @enderror"
                   value="{{ $fechaValor }}" required>
            @error('fecha_hora')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="motivo_consulta">Motivo de la consulta</label>
    <input type="text" name="motivo_consulta" id="motivo_consulta"
           class="form-control @error('motivo_consulta') is-invalid @enderror"
           value="{{ old('motivo_consulta', $consulta->motivo_consulta ?? '') }}" required>
    @error('motivo_consulta')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label for="descripcion_problema">Descripción del problema</label>
    <textarea name="descripcion_problema" id="descripcion_problema" rows="3"
              class="form-control @error('descripcion_problema') is-invalid @enderror">{{ old('descripcion_problema', $consulta->descripcion_problema ?? '') }}</textarea>
    @error('descripcion_problema')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="antecedentes_medicos">Antecedentes médicos</label>
            <textarea name="antecedentes_medicos" id="antecedentes_medicos" rows="3"
                      class="form-control @error('antecedentes_medicos') is-invalid @enderror">{{ old('antecedentes_medicos', $consulta->antecedentes_medicos ?? '') }}</textarea>
            @error('antecedentes_medicos')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="antecedentes_odontologicos">Antecedentes odontológicos</label>
            <textarea name="antecedentes_odontologicos" id="antecedentes_odontologicos" rows="3"
                      class="form-control @error('antecedentes_odontologicos') is-invalid @enderror">{{ old('antecedentes_odontologicos', $consulta->antecedentes_odontologicos ?? '') }}</textarea>
            @error('antecedentes_odontologicos')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="medicamentos_actuales">Medicamentos actuales</label>
            <textarea name="medicamentos_actuales" id="medicamentos_actuales" rows="3"
                      class="form-control @error('medicamentos_actuales') is-invalid @enderror">{{ old('medicamentos_actuales', $consulta->medicamentos_actuales ?? '') }}</textarea>
            @error('medicamentos_actuales')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="alergias">Alergias</label>
            <textarea name="alergias" id="alergias" rows="3"
                      class="form-control @error('alergias') is-invalid @enderror">{{ old('alergias', $consulta->alergias ?? '') }}</textarea>
            @error('alergias')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="diagnostico_presuntivo">Diagnóstico presuntivo</label>
            <textarea name="diagnostico_presuntivo" id="diagnostico_presuntivo" rows="3"
                      class="form-control @error('diagnostico_presuntivo') is-invalid @enderror">{{ old('diagnostico_presuntivo', $consulta->diagnostico_presuntivo ?? '') }}</textarea>
            @error('diagnostico_presuntivo')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="plan_tratamiento">Plan de tratamiento / indicaciones</label>
    <textarea name="plan_tratamiento" id="plan_tratamiento" rows="3"
              class="form-control @error('plan_tratamiento') is-invalid @enderror">{{ old('plan_tratamiento', $consulta->plan_tratamiento ?? '') }}</textarea>
    @error('plan_tratamiento')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <label for="observaciones">Observaciones adicionales</label>
    <textarea name="observaciones" id="observaciones" rows="3"
              class="form-control @error('observaciones') is-invalid @enderror">{{ old('observaciones', $consulta->observaciones ?? '') }}</textarea>
    @error('observaciones')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Guardar consulta
    </button>
    <a href="{{ route('admin.consultas.index') }}" class="btn btn-secondary ml-2">
        Cancelar
    </a>
</div>

@if(!$esClinica)
    <script>
        (function () {
            const selClinica  = document.getElementById('clinica_id');
            const selPaciente = document.getElementById('paciente_id');
            if (!selClinica || !selPaciente) return;

            const allOptions = Array.from(selPaciente.options).map(o => ({
                value: o.value,
                text: o.text,
                clinica: o.getAttribute('data-clinica') || '',
                selected: o.selected,
                isPlaceholder: o.value === ''
            }));

            function rebuildPatients() {
                const cid = String(selClinica.value || '');
                const prev = String(selPaciente.value || '');

                selPaciente.innerHTML = '';
                allOptions.forEach(o => {
                    if (o.isPlaceholder) {
                        const opt = new Option(o.text, o.value);
                        selPaciente.add(opt);
                        return;
                    }

                    // Si no hay clínica seleccionada, mostramos todos (no rompe nada)
                    if (!cid || String(o.clinica) === cid) {
                        const opt = new Option(o.text, o.value);
                        opt.setAttribute('data-clinica', o.clinica);
                        selPaciente.add(opt);
                    }
                });

                // Si el paciente anterior sigue existiendo, lo restauramos; si no, volvemos al placeholder
                const exists = Array.from(selPaciente.options).some(o => o.value === prev);
                selPaciente.value = exists ? prev : '';
            }

            selClinica.addEventListener('change', rebuildPatients);

            // Primera carga: filtra según clínica seleccionada por defecto
            rebuildPatients();
        })();
    </script>
@endif
