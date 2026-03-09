@extends('layouts.app')

@section('title', 'Cambio obligatorio de contraseña | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card card-ui">
                <div class="card-body p-4">
                    <h1 class="h5 fw-bold mb-2">Actualiza tu contraseña</h1>
                    <p class="text-muted small mb-4">Por seguridad, debes cambiar tu contraseña temporal antes de continuar.</p>

                    <form method="post" action="{{ route('password.force.update') }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Nueva contraseña</label>
                            <input type="password" class="form-control input-ui" name="password" minlength="8" required>
                            @error('password')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Confirmar contraseña</label>
                            <input type="password" class="form-control input-ui" name="password_confirmation" minlength="8" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-ui btn-primary-ui" type="submit">Guardar nueva contraseña</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
