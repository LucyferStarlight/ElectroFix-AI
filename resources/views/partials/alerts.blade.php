<div class="container-fluid px-3 px-lg-4 pt-3" id="flashAlerts" data-alerts-root data-session-id="{{ session()->getId() }}">
    @if (session('success'))
        <div class="alert alert-ui-info mb-2 d-flex justify-content-between align-items-center gap-2" role="alert">
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" aria-label="Cerrar" data-alert-dismiss></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-ui-warning mb-2 d-flex justify-content-between align-items-center gap-2" role="alert">
            <span>{{ session('warning') }}</span>
            <button type="button" class="btn-close" aria-label="Cerrar" data-alert-dismiss></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-ui-warning mb-2 d-flex justify-content-between align-items-center gap-2" role="alert">
            <span>{{ $errors->first() }}</span>
            <button type="button" class="btn-close" aria-label="Cerrar" data-alert-dismiss></button>
        </div>
    @endif

    @auth
        <div class="row g-2">
            <div class="col-12">
                <div class="alert alert-ui-info d-flex align-items-center justify-content-between mb-0" role="alert" data-session-alert>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge badge-ui badge-ui-info">SESSION</span>
                        <span>{{ auth()->user()->name }} conectado como {{ auth()->user()->role }}.</span>
                    </div>
                    <button type="button" class="btn-close" aria-label="Cerrar" data-alert-dismiss></button>
                </div>
            </div>
        </div>
    @endauth
</div>
