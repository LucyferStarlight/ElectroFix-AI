@extends('layouts.app')

@section('title', 'Módulo Trabajadores | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="card card-ui"><div class="card-body">
        <h1 class="h4 fw-bold mb-3">Accesos de Worker</h1>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-ui btn-primary-ui" href="{{ route('dashboard.worker') }}">Dashboard Worker</a>
            <a class="btn btn-ui btn-outline-ui" href="{{ route('worker.orders') }}">Órdenes</a>
            <a class="btn btn-ui btn-outline-ui" href="{{ route('worker.customers') }}">Clientes</a>
            <a class="btn btn-ui btn-outline-ui" href="{{ route('worker.equipments') }}">Equipos</a>
        </div>
    </div></div>
</div>
@endsection
