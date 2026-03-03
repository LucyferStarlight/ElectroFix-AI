<div class="container-fluid px-3 px-lg-4 pt-3">
    @if (session('success'))
        <div class="alert alert-ui-info mb-2" role="alert">{{ session('success') }}</div>
    @endif

    @if (session('warning'))
        <div class="alert alert-ui-warning mb-2" role="alert">{{ session('warning') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-ui-warning mb-2" role="alert">{{ $errors->first() }}</div>
    @endif

    @auth
        <div class="row g-2">
            <div class="col-12">
                <div class="alert alert-ui-info d-flex align-items-center mb-0" role="alert">
                    <span class="badge badge-ui badge-ui-info me-2">SESSION</span>
                    <span>{{ auth()->user()->name }} conectado como {{ auth()->user()->role }}.</span>
                </div>
            </div>
        </div>
    @endauth
</div>
