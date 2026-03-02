@extends('layouts.app')

@section('title', 'Empresas | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <h1 class="h4 fw-bold mb-3">Empresas suscritas</h1>
    <div class="card card-ui">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-ui align-middle">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Plan</th>
                            <th>Vigencia</th>
                            <th>Dueño</th>
                            <th>Contacto</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companies as $company)
                            <tr>
                                <td>{{ $company->name }}</td>
                                <td class="text-uppercase">{{ $company->subscription?->plan ?? 'N/A' }}</td>
                                <td>{{ optional($company->subscription?->starts_at)->toDateString() }} - {{ optional($company->subscription?->ends_at)->toDateString() }}</td>
                                <td>{{ $company->owner_name }}</td>
                                <td>{{ $company->owner_email }}<br><small class="text-muted">{{ $company->owner_phone }}</small></td>
                                <td><a class="btn btn-ui btn-outline-ui btn-sm" href="{{ route('developer.companies.show', $company) }}">Detalle</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
