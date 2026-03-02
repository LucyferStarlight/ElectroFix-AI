@extends('layouts.guest')

@section('title', 'Error de plataforma | ElectroFix-AI')

@section('content')
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4 p-lg-5 text-center">
                    <span class="badge badge-ui badge-ui-warning mb-3">Error UI</span>
                    <h1 class="h3 fw-bold mb-3">No pudimos completar la acción</h1>
                    <p class="text-muted mb-4">Pantalla genérica para fallas de proceso o restricciones temporales. Lista para mostrar mensajes dinámicos después.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-ui btn-primary-ui">Volver al panel</a>
                        <a href="{{ route('landing') }}" class="btn btn-ui btn-outline-ui">Ir a inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
