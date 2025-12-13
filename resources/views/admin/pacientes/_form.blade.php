@csrf

@php
    $u = auth()->user();
    $esClinica = $u && $u->hasRole('clinica');

    // Clinica fija para el rol clinica
    $clinicaFijaId = $esClinica ? (int) ($u->clinica_id ?? 0) : 0;

    // Para mostrar el nombre sin romper nada (usa $clinicas si viene cargado)
    $clinicaFija = null;
    if ($esClinica && $clinicaFijaId) {
        $clinicaFija = ($clinicas ?? collect())->firstWhere('id', $clinicaFijaId);
    }

    // Valor inicial de edad (si ya existe en BD o viene de old)
    $edadInicial = old('edad', $paciente->edad ?? '');
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="clinica_id">Clínica</label>

            @if($esClinica)
                {{-- Rol clínica: NO puede elegir. Se fija automáticamente --}}
                <input type="hidden" name="clinica_id"
                       value="{{ old('clinica_id', $paciente->clinica_id ?? $clinicaFijaId) }}">

                <input type="text"
                       class="form-control"
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
                            {{ (int) old('clinica_id', $paciente->clinica_id ?? 0) === (int) $clinica->id ? 'selected' : '' }}>
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
            <label for="nombre">Nombre</label>
            <input type="text" name="nombre" id="nombre"
                   class="form-control @error('nombre') is-invalid @enderror"
                   value="{{ old('nombre', $paciente->nombre ?? '') }}" required>
            @error('nombre')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="apellido">Apellido</label>
            <input type="text" name="apellido" id="apellido"
                   class="form-control @error('apellido') is-invalid @enderror"
                   value="{{ old('apellido', $paciente->apellido ?? '') }}">
            @error('apellido')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="documento">Documento</label>
            <input type="text" name="documento" id="documento"
                   class="form-control @error('documento') is-invalid @enderror"
                   value="{{ old('documento', $paciente->documento ?? '') }}">
            @error('documento')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="fecha_nacimiento">Fecha de nacimiento</label>
            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                   class="form-control @error('fecha_nacimiento') is-invalid @enderror"
                   value="{{ old('fecha_nacimiento', optional($paciente->fecha_nacimiento ?? null)->format('Y-m-d')) }}">
            @error('fecha_nacimiento')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- ✅ NUEVO: Edad --}}
    <div class="col-md-2">
        <div class="form-group">
            <label for="edad">Edad</label>
            <input type="number" name="edad" id="edad"
                   min="0" max="130"
                   class="form-control @error('edad') is-invalid @enderror"
                   value="{{ $edadInicial }}">
            @error('edad')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            <small class="text-muted">Se calcula si hay fecha</small>
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label for="genero">Género</label>
            @php
                $genero = old('genero', $paciente->genero ?? '');
            @endphp
            <select name="genero" id="genero"
                    class="form-control @error('genero') is-invalid @enderror">
                <option value="">Sin especificar</option>
                <option value="M" {{ $genero === 'M' ? 'selected' : '' }}>Masculino</option>
                <option value="F" {{ $genero === 'F' ? 'selected' : '' }}>Femenino</option>
                <option value="O" {{ $genero === 'O' ? 'selected' : '' }}>Otro</option>
            </select>
            @error('genero')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" name="telefono" id="telefono"
                   class="form-control @error('telefono') is-invalid @enderror"
                   value="{{ old('telefono', $paciente->telefono ?? '') }}">
            @error('telefono')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" name="email" id="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $paciente->email ?? '') }}">
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" name="direccion" id="direccion"
                   class="form-control @error('direccion') is-invalid @enderror"
                   value="{{ old('direccion', $paciente->direccion ?? '') }}">
            @error('direccion')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="ciudad">Ciudad</label>
            <input type="text" name="ciudad" id="ciudad"
                   class="form-control @error('ciudad') is-invalid @enderror"
                   value="{{ old('ciudad', $paciente->ciudad ?? '') }}">
            @error('ciudad')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group form-check mt-4 pt-2">
            <input type="checkbox" name="is_active" id="is_active"
                   class="form-check-input"
                   value="1"
                {{ old('is_active', $paciente->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="form-check-label">Paciente activo</label>
        </div>
    </div>
</div>

<div class="form-group">
    <label for="observaciones">Observaciones</label>
    <textarea name="observaciones" id="observaciones" rows="3"
              class="form-control @error('observaciones') is-invalid @enderror">{{ old('observaciones', $paciente->observaciones ?? '') }}</textarea>
    @error('observaciones')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Guardar
    </button>
    <a href="{{ route('admin.pacientes.index') }}" class="btn btn-secondary ml-2">
        Cancelar
    </a>
</div>

{{-- ✅ JS: calcular edad al seleccionar fecha --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fecha = document.getElementById('fecha_nacimiento');
    const edad  = document.getElementById('edad');

    function calcAge(yyyy_mm_dd) {
        const parts = (yyyy_mm_dd || '').split('-');
        if (parts.length !== 3) return '';

        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);
        const d = parseInt(parts[2], 10);
        if (!y || !m || !d) return '';

        const hoy = new Date();
        const nac = new Date(y, m - 1, d);

        let years = hoy.getFullYear() - nac.getFullYear();
        const mm = hoy.getMonth() - nac.getMonth();

        if (mm < 0 || (mm === 0 && hoy.getDate() < nac.getDate())) {
            years--;
        }
        return (years >= 0) ? years : '';
    }

    function syncEdad() {
        const v = (fecha?.value || '').trim();

        if (!v) {
            // Sin fecha: permitir editar manualmente
            if (edad) edad.readOnly = false;
            return;
        }

        if (edad) {
            edad.value = calcAge(v);
            edad.readOnly = true;
        }
    }

    if (fecha && edad) {
        fecha.addEventListener('change', syncEdad);
        syncEdad();
    }
});
</script>
