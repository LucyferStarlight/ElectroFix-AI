@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', 'Soporte | ElectroFix-AI')

@section('content')
<section class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card card-ui shadow-soft">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 fw-bold mb-2">Soporte</h1>
                    <p class="text-muted mb-4">Escríbenos tu solicitud y te responderemos por correo. También puedes usar WhatsApp.</p>

                    <div class="d-flex flex-wrap gap-3 align-items-center mb-4">
                        <a class="btn btn-ui btn-outline-ui" href="mailto:{{ config('support.email') }}">
                            {{ config('support.email') }}
                        </a>
                        @if(config('support.whatsapp_url'))
                            <a class="btn btn-ui btn-outline-ui" href="{{ config('support.whatsapp_url') }}" target="_blank" rel="noopener">
                                WhatsApp
                            </a>
                        @endif
                        <a class="btn btn-ui btn-ghost" href="{{ config('support.arakatadevs_url') }}" target="_blank" rel="noopener">
                            ArakataDevs
                        </a>
                    </div>

                    <form method="post" action="{{ route('support.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input class="form-control input-ui" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control input-ui" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Mensaje *</label>
                                <textarea class="form-control input-ui" name="message" rows="5" required>{{ old('message') }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-ui btn-primary-ui">Enviar a soporte</button>
                        </div>
                    </form>

                    <div class="alert alert-ui-info mt-4 mb-0">
                        Soporte operado por <a class="link-ui" href="{{ config('support.arakatadevs_url') }}" target="_blank" rel="noopener">ArakataDevs</a>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
