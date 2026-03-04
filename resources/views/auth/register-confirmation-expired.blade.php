@extends('layouts.guest')

@section('title', 'Confirmación no disponible | ElectroFix-AI')

@section('content')
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4 text-center">
                    <h1 class="h5 fw-bold">Esta confirmación ya no está disponible</h1>
                    <p class="text-muted mb-4">Por seguridad, el detalle de credenciales solo puede consultarse una vez.</p>
                    <a class="btn btn-ui btn-primary-ui" href="{{ route('login') }}">Ir a iniciar sesión</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
