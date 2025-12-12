@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="nombre">Nombre de la clínica</label>
            <input type="text" name="nombre" id="nombre"
                   class="form-control @error('nombre') is-invalid @enderror"
                   value="{{ old('nombre', $clinica->nombre ?? '') }}" required>
            @error('nombre')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="ruc">RUC</label>
            <input type="text" name="ruc" id="ruc"
                   class="form-control @error('ruc') is-invalid @enderror"
                   value="{{ old('ruc', $clinica->ruc ?? '') }}">
            @error('ruc')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label for="plan">Plan</label>
            <select name="plan" id="plan"
                    class="form-control @error('plan') is-invalid @enderror">
                @php
                    $planActual = old('plan', $clinica->plan ?? 'standard');
                @endphp
                <option value="free" {{ $planActual === 'free' ? 'selected' : '' }}>Free</option>
                <option value="standard" {{ $planActual === 'standard' ? 'selected' : '' }}>Standard</option>
                <option value="premium" {{ $planActual === 'premium' ? 'selected' : '' }}>Premium</option>
            </select>
            @error('plan')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="ciudad">Ciudad</label>
            <input type="text" name="ciudad" id="ciudad"
                   class="form-control @error('ciudad') is-invalid @enderror"
                   value="{{ old('ciudad', $clinica->ciudad ?? '') }}">
            @error('ciudad')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" name="direccion" id="direccion"
                   class="form-control @error('direccion') is-invalid @enderror"
                   value="{{ old('direccion', $clinica->direccion ?? '') }}">
            @error('direccion')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" name="telefono" id="telefono"
                   class="form-control @error('telefono') is-invalid @enderror"
                   value="{{ old('telefono', $clinica->telefono ?? '') }}">
            @error('telefono')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" name="email" id="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $clinica->email ?? '') }}">
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="observaciones">Observaciones</label>
    <textarea name="observaciones" id="observaciones" rows="3"
              class="form-control @error('observaciones') is-invalid @enderror">{{ old('observaciones', $clinica->observaciones ?? '') }}</textarea>
    @error('observaciones')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="form-group form-check">
    <input type="checkbox" name="is_active" id="is_active"
           class="form-check-input"
           value="1"
           {{ old('is_active', $clinica->is_active ?? true) ? 'checked' : '' }}>
    <label for="is_active" class="form-check-label">Clínica activa</label>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save mr-1"></i> Guardar
    </button>
    <a href="{{ route('admin.clinicas.index') }}" class="btn btn-secondary ml-2">
        Cancelar
    </a>
</div>
