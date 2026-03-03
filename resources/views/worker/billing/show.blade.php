@extends('layouts.app')

@section('title', 'Documento de Facturación | ElectroFix-AI')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Documento {{ strtoupper($document->document_type) }} - {{ $document->document_number }}</h1>
            <p class="text-muted mb-0">Emitido por {{ $document->user->name }} el {{ $document->issued_at?->format('Y-m-d H:i') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-ui btn-outline-ui" href="{{ route('worker.billing') }}">Volver</a>
            <a class="btn btn-ui btn-primary-ui" href="{{ route('worker.billing.pdf', $document) }}">Descargar PDF</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card card-ui"><div class="card-body"><small class="text-muted d-block">Cliente</small><strong>{{ $document->customerDisplayName() }}</strong></div></div></div>
        <div class="col-md-4"><div class="card card-ui"><div class="card-body"><small class="text-muted d-block">IVA</small><strong>{{ strtoupper($document->tax_mode) }} / {{ number_format((float) $document->vat_percentage, 2) }}%</strong></div></div></div>
        <div class="col-md-4"><div class="card card-ui"><div class="card-body"><small class="text-muted d-block">Origen</small><strong>{{ strtoupper($document->source) }}</strong></div></div></div>
    </div>

    <div class="card card-ui mb-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-ui mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="px-3">Descripción</th>
                            <th>Tipo</th>
                            <th>Cant.</th>
                            <th>P. Unit.</th>
                            <th>Base</th>
                            <th>IVA</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($document->items as $item)
                            <tr>
                                <td class="px-3">{{ $item->description }}</td>
                                <td>{{ strtoupper($item->item_kind) }}</td>
                                <td>{{ number_format((float) $item->quantity, 2) }}</td>
                                <td>${{ number_format((float) $item->unit_price, 2) }}</td>
                                <td>${{ number_format((float) $item->line_subtotal, 2) }}</td>
                                <td>${{ number_format((float) $item->line_vat, 2) }}</td>
                                <td class="fw-semibold">${{ number_format((float) $item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-ui">
        <div class="card-body">
            <div class="row g-2 justify-content-end text-end">
                <div class="col-md-3"><small class="text-muted">Subtotal</small><div class="fw-semibold">${{ number_format((float) $document->subtotal, 2) }}</div></div>
                <div class="col-md-3"><small class="text-muted">IVA</small><div class="fw-semibold">${{ number_format((float) $document->vat_amount, 2) }}</div></div>
                <div class="col-md-3"><small class="text-muted">Total</small><div class="h5 fw-bold mb-0">${{ number_format((float) $document->total, 2) }}</div></div>
            </div>
            @if($document->notes)
                <hr>
                <p class="mb-0"><strong>Notas:</strong> {{ $document->notes }}</p>
            @endif
        </div>
    </div>
</div>
@endsection
