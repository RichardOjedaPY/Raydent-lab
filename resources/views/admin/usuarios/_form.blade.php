{{-- resources/views/admin/usuarios/_form.blade.php --}}

@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name">Nombre completo</label>
            <input type="text" name="name" id="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $user->name ?? '') }}" required>
            @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input type="email" name="email" id="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $user->email ?? '') }}" required>
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="password">
                @if($user->exists)
                    Nueva contraseña (opcional)
                @else
                    Contraseña
                @endif
            </label>
            <input type="password" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror"
                   @if(! $user->exists) required @endif>
            @error('password')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            @if($user->exists)
                <small class="form-text text-muted">
                    Deja en blanco si no quieres cambiar la contraseña.
                </small>
            @endif
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="password_confirmation">
                Confirmar contraseña
            </label>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   class="form-control"
                   @if(! $user->exists) required @endif>
        </div>
    </div>
</div>
<div class="row">
    {{-- ROL --}}
    <div class="col-md-4">
        <div class="form-group">
            <label for="role">Rol</label>
            <select name="role" id="role"
                    class="form-control @error('role') is-invalid @enderror" required>
                <option value="">-- Seleccione un rol --</option>
                @foreach($roles as $role)
                    @php
                        $label = match ($role->name) {
                            'clinica' => 'Clínica',
                            'tecnico' => 'Técnico',
                            default   => ucfirst($role->name),
                        };
                    @endphp
                    <option value="{{ $role->name }}"
                        {{ old('role', $user->roles->first()->name ?? '') === $role->name ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('role')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- CLÍNICA (solo rol clínica) --}}
    <div class="col-md-4">
        <div class="form-group" id="clinica-wrapper">
            <label for="clinica_id">Clínica</label>
            <select name="clinica_id" id="clinica_id"
                    class="form-control @error('clinica_id') is-invalid @enderror">
                <option value="">-- Seleccione una clínica --</option>
                @foreach($clinicas as $clinica)
                    <option value="{{ $clinica->id }}"
                        {{ (string) old('clinica_id', $user->clinica_id ?? '') === (string) $clinica->id ? 'selected' : '' }}>
                        {{ $clinica->nombre }}
                    </option>
                @endforeach
            </select>
            @error('clinica_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- TIPO USUARIO DENTRO DE CLÍNICA --}}
    <div class="col-md-4">
        <div class="form-group" id="tipo-usuario-wrapper">
            <label for="tipo_usuario_clinica">Tipo de usuario en la clínica</label>
            <select name="tipo_usuario_clinica" id="tipo_usuario_clinica"
                    class="form-control @error('tipo_usuario_clinica') is-invalid @enderror">
                <option value="">-- Seleccione tipo --</option>
                @php
                    $tipoActual = old('tipo_usuario_clinica', $user->tipo_usuario_clinica ?? '');
                @endphp
                <option value="owner" {{ $tipoActual === 'owner' ? 'selected' : '' }}>
                    Owner / Administrador
                </option>
                <option value="staff" {{ $tipoActual === 'staff' ? 'selected' : '' }}>
                    Staff / Operador
                </option>
            </select>
            @error('tipo_usuario_clinica')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>



<div class="form-group form-check">
    <input type="checkbox" name="is_active" id="is_active"
           class="form-check-input"
           value="1"
           {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
    <label for="is_active" class="form-check-label">Usuario activo</label>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i>
        Guardar
    </button>
    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary ml-2">
        Cancelar
    </a>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect      = document.getElementById('role');
        const clinicaWrapper  = document.getElementById('clinica-wrapper');
        const tipoWrapper     = document.getElementById('tipo-usuario-wrapper');

        function toggleClinicaFields() {
            if (!roleSelect) return;
            const val = roleSelect.value;

            const show = (val === 'clinica');

            if (clinicaWrapper) {
                clinicaWrapper.style.display = show ? 'block' : 'none';
            }
            if (tipoWrapper) {
                tipoWrapper.style.display = show ? 'block' : 'none';
            }
        }

        roleSelect.addEventListener('change', toggleClinicaFields);
        toggleClinicaFields();
    });
</script>
@endpush

