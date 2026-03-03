<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document->document_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1c2a35; }
        .head { margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .muted { color: #607385; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d5e0ea; padding: 7px; text-align: left; }
        th { background: #edf4fb; font-size: 11px; text-transform: uppercase; }
        .right { text-align: right; }
        .summary { margin-top: 14px; width: 45%; margin-left: auto; }
        .summary td { border: none; padding: 4px 2px; }
        .summary .total { font-size: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="head">
        <div class="title">ElectroFix-AI - {{ strtoupper($document->document_type) }}</div>
        <div><strong>Documento:</strong> {{ $document->document_number }}</div>
        <div><strong>Empresa:</strong> {{ $document->company->name }}</div>
        <div><strong>Cliente:</strong> {{ $document->customerDisplayName() }}</div>
        <div class="muted">IVA {{ strtoupper($document->tax_mode) }} - {{ number_format((float) $document->vat_percentage, 2) }}% | Emitido: {{ $document->issued_at?->format('Y-m-d H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Cant.</th>
                <th>P.Unit.</th>
                <th>Base</th>
                <th>IVA</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ strtoupper($item->item_kind) }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
                    <td class="right">${{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">${{ number_format((float) $item->line_subtotal, 2) }}</td>
                    <td class="right">${{ number_format((float) $item->line_vat, 2) }}</td>
                    <td class="right">${{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr><td>Subtotal</td><td class="right">${{ number_format((float) $document->subtotal, 2) }}</td></tr>
        <tr><td>IVA</td><td class="right">${{ number_format((float) $document->vat_amount, 2) }}</td></tr>
        <tr class="total"><td>Total</td><td class="right">${{ number_format((float) $document->total, 2) }}</td></tr>
    </table>

    @if($document->notes)
        <p><strong>Notas:</strong> {{ $document->notes }}</p>
    @endif
</body>
</html>
