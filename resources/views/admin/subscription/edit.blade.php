@extends('layouts.app')

@section('title', 'Suscripción | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="card card-ui">
        <div class="card-body">
            <h1 class="h4 fw-bold mb-3">Suscripción de empresa</h1>
            <form method="post" action="{{ route('admin.subscription.update') }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-4">
                    <label class="form-label">Plan</label>
                    <select class="form-select input-ui" name="plan" required>
                        @foreach(['starter','pro','enterprise','developer_test'] as $plan)
                            <option value="{{ $plan }}" @selected(old('plan', $subscription->plan) === $plan)>{{ strtoupper($plan) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select class="form-select input-ui" name="status" required>
                        @foreach(['active','trial','past_due','canceled','suspended'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $subscription->status) === $status)>{{ strtoupper($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ciclo</label>
                    <select class="form-select input-ui" name="billing_cycle" required>
                        @foreach(['monthly','yearly'] as $cycle)
                            <option value="{{ $cycle }}" @selected(old('billing_cycle', $subscription->billing_cycle) === $cycle)>{{ strtoupper($cycle) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Inicio</label><input type="date" class="form-control input-ui" name="starts_at" value="{{ old('starts_at', $subscription->starts_at?->toDateString()) }}" required></div>
                <div class="col-md-4"><label class="form-label">Fin</label><input type="date" class="form-control input-ui" name="ends_at" value="{{ old('ends_at', $subscription->ends_at?->toDateString()) }}" required></div>
                <div class="col-md-4"><label class="form-label">Límite usuarios</label><input type="number" class="form-control input-ui" name="user_limit" value="{{ old('user_limit', $subscription->user_limit) }}"></div>
                <div class="col-12"><button class="btn btn-ui btn-primary-ui" type="submit">Actualizar suscripción</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
