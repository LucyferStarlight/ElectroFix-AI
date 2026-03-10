@extends('layouts.guest')

@section('title', 'Registro Completado | ElectroFix-AI')

@section('content')
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 fw-bold mb-1">Registro completado</h1>
                    <p class="text-muted mb-4">Tu empresa fue creada correctamente. Esta información sensible se mostrará solo una vez.</p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><strong>Empresa:</strong> {{ data_get($snapshot, 'company.name') }}</div>
                        <div class="col-md-6"><strong>Administrador:</strong> {{ data_get($snapshot, 'admin.name') }} ({{ data_get($snapshot, 'admin.email') }})</div>
                        <div class="col-md-6"><strong>Plan:</strong> {{ strtoupper((string) data_get($snapshot, 'subscription.plan')) }}</div>
                        <div class="col-md-6"><strong>Periodo:</strong> {{ strtoupper((string) data_get($snapshot, 'subscription.billing_period')) }}</div>
                        <div class="col-md-6"><strong>Estado suscripción:</strong> {{ strtoupper((string) data_get($snapshot, 'subscription.status')) }}</div>
                        @if(data_get($snapshot, 'payment.status'))
                            <div class="col-md-6"><strong>Pago Stripe:</strong> {{ strtoupper((string) data_get($snapshot, 'payment.status')) }}</div>
                        @elseif(data_get($snapshot, 'payment_simulation.result'))
                            <div class="col-md-6"><strong>Pago simulado:</strong> {{ strtoupper((string) data_get($snapshot, 'payment_simulation.result')) }}</div>
                        @endif
                    </div>

                    @if(data_get($snapshot, 'workers'))
                        <h2 class="h6 fw-bold mt-4">Credenciales iniciales de workers</h2>
                        <div class="table-responsive mt-2">
                            <table class="table table-ui align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Password</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(data_get($snapshot, 'workers', []) as $worker)
                                        <tr>
                                            <td>{{ $worker['index'] }}</td>
                                            <td>{{ $worker['name'] }}</td>
                                            <td>{{ $worker['email'] }}</td>
                                            <td><code>{{ $worker['password'] }}</code></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if(data_get($snapshot, 'subscription.status') === 'past_due')
                        <div class="alert alert-ui-warning mt-4 mb-0">
                            La suscripción quedó pendiente de acreditación. Puedes iniciar sesión como administrador y solo tendrás acceso al módulo de Suscripción para regularizarla.
                        </div>
                    @endif

                    <div class="d-flex gap-2 mt-4">
                        <a class="btn btn-ui btn-primary-ui" href="{{ route('login') }}">Ir a iniciar sesión</a>
                        <a class="btn btn-ui btn-outline-ui" href="{{ route('landing') }}">Ir al inicio</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
