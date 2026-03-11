<footer class="border-top bg-surface">
    <div class="container-fluid px-3 px-lg-4 py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
        <p class="mb-0 small text-muted"><strong>ElectroFix-AI</strong> fue diseñado y desarrollado por <strong>ArakataDevs</strong>.</p>
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="small text-muted">Soporte:</span>
                <a class="small link-ui" href="mailto:{{ config('support.email') }}">{{ config('support.email') }}</a>
                @if(config('support.whatsapp_url'))
                    <a class="small link-ui" href="{{ config('support.whatsapp_url') }}" target="_blank" rel="noopener">WhatsApp</a>
                @endif
                <a class="small link-ui" href="{{ route('support') }}">Formulario</a>
            </div>
            <p class="mb-0 small text-muted font-mono">
                Desarrollo y propiedad intelectual:
                <a class="link-ui" href="{{ config('support.arakatadevs_url') }}" target="_blank" rel="noopener">ArakataDevs</a>
            </p>
            <a class="small link-ui" href="{{ route('terms') }}">Términos y Condiciones</a>
        </div>
    </div>
</footer>
