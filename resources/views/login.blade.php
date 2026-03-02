@extends('layouts.guest')

@section('title', 'Iniciar sesión | ElectroFix-AI')

@section('content')
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <span class="brand-mark mb-3 d-inline-flex">EF</span>
                        <h1 class="h4 fw-bold">Acceso al panel</h1>
                        <p class="text-muted mb-0">Ingresa con una sesión acreditada para acceder al dashboard.</p>
                    </div>

                    <form class="d-grid gap-3" action="{{ route('login.store') }}" method="post" novalidate>
                        @csrf
                        @if ($errors->any())
                            <div class="alert alert-ui-warning mb-0" role="alert">
                                {{ $errors->first() }}
                            </div>
                        @endif
                        <div>
                            <label for="email" class="form-label fw-semibold">Correo de usuario</label>
                            <input id="email" name="email" type="email" class="form-control input-ui" placeholder="usuario@empresa.com" value="{{ old('email') }}" required autofocus>
                        </div>
                        <div>
                            <label for="password" class="form-label fw-semibold">Contraseña</label>
                            <input id="password" name="password" type="password" class="form-control input-ui" placeholder="••••••••" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                            <label class="form-check-label text-muted" for="remember">
                                Mantener sesión
                            </label>
                        </div>
                        <button type="submit" class="btn btn-ui btn-primary-ui w-100">Ingresar</button>
                    </form>

                    <div class="mt-4 pt-3 border-top">
                        <p class="mb-1 small text-muted">Credenciales demo: <code>admin@electrofix.ai</code>, <code>worker@electrofix.ai</code>, <code>developer@electrofix.ai</code></p>
                        <p class="mb-1 small text-muted">Password común: <code>password123</code></p>
                        <a href="{{ route('landing') }}" class="small link-ui">Volver a landing</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
