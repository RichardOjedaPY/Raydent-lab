@csrf

@php
    $fechaValor = old('fecha_hora', optional($consulta->fecha_hora ?? now())->format('Y-m-d\TH:i'));
    $pacienteSeleccionado = old('paciente_id', $consulta->paciente_id ?? ($pacienteId ?? ''));
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="clinica_id">Clínica</label>
            <select name="clinica_id" id="clinica_id"
                    class="form-control @error('clinica_id') is-invalid @enderror" required>
                <option value="">-- Seleccione clínica --</option>
                @foreach($clinicas as $clinica)
                    <option value="{{ $clinica->id }}"
                        {{ (int) old('clinica_id', $consulta->clinica_id ?? 0) === $clinica->id ? 'selected' : '' }}>
                        {{ $clinica->nombre }}
                    </option>
                @endforeach
            </select>
            @error('clinica_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="paciente_id">Paciente</label>
            <select name="paciente_id" id="paciente_id"
                    class="form-control @error('paciente_id') is-invalid @enderror" required>
                <option value="">-- Seleccione paciente --</option>
                @foreach($pacientes as $p)
                    <option value="{{ $p->id }}"
                        {{ (int) $pacienteSeleccionado === $p->id ? 'selected' : '' }}>
                        {{ $p->nombre }} {{ $p->apellido }}
                        @if($p->clinica)
                            ({{ $p->clinica->nombre }})
                        @endif
                    </option>
                @endforeach
            </select>
            @error('paciente_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            <small class="form-text text-muted">
                Más adelante haremos este select dinámico por clínica / Livewire.
            </small>
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
