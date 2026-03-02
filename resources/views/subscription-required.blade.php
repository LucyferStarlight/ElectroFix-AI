@extends('layouts.app')

@section('title', 'Suscripción requerida | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4 p-lg-5">
                    <span class="badge badge-ui badge-ui-warning mb-3">Cuenta suspendida</span>
                    <h1 class="h4 fw-bold mb-3">Se requiere una suscripción activa</h1>
                    <p class="text-muted mb-4">Vista preparada para controlar acceso por estado de plan. Aquí se conectará la lógica de billing sin rehacer la interfaz.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card card-ui h-100">
                                <div class="card-body">
                                    <p class="small text-muted mb-1">Plan actual</p>
                                    <p class="h5 fw-bold mb-3">Inactivo</p>
                                    <button class="btn btn-ui btn-outline-ui w-100" type="button">Ver detalle</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-ui h-100 highlight-card">
                                <div class="card-body">
                                    <p class="small text-muted mb-1">Acción recomendada</p>
                                    <p class="h5 fw-bold mb-3">Reactivar suscripción</p>
                                    <button class="btn btn-ui btn-primary-ui w-100" type="button">Actualizar plan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
