<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document->document_number }}</title>
    <style>
        @page { margin: 26px 30px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #27313a;
            background: #f5f6f7;
        }
        .sheet {
            background: #ffffff;
            border: 1px solid #d8dcdf;
            padding: 24px 26px 20px 26px;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #6f7a82; }
        .small { font-size: 10px; }
        .title {
            font-size: 42px;
            letter-spacing: 8px;
            font-weight: bold;
            margin: 8px 0 6px 0;
        }
        .doc-chip {
            display: inline-block;
            background: #0a807b;
            color: #ffffff;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 2px;
            padding: 4px 18px;
        }
        .head-grid {
            width: 100%;
            margin-top: 18px;
            border-collapse: collapse;
        }
        .head-grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .block-title {
            margin: 0 0 6px 0;
            color: #112127;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .block-line { margin: 0 0 2px 0; }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        .items th {
            background: #0a807b;
            color: #ffffff;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1px;
            font-weight: bold;
            border: 1px solid #0a807b;
            padding: 7px 6px;
        }
        .items td {
            border: 1px solid #b8bec4;
            padding: 7px 6px;
            font-size: 10.5px;
        }
        .total-row td {
            background: #0a807b;
            color: #ffffff;
            font-weight: bold;
            font-size: 12px;
            border-color: #0a807b;
        }
        .bottom-grid {
            width: 100%;
            margin-top: 14px;
            border-collapse: collapse;
        }
        .bottom-grid td {
            vertical-align: top;
        }
        .note {
            width: 50%;
            padding-right: 14px;
            font-size: 10.5px;
            line-height: 1.45;
            color: #49545d;
        }
        .summary {
            width: 50%;
        }
        .summary table {
            width: 100%;
            border-collapse: collapse;
            background: #0a807b;
            color: #ffffff;
        }
        .summary td {
            padding: 7px 10px;
            font-size: 12px;
            font-weight: bold;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        .summary tr:last-child td {
            border-bottom: 0;
            font-size: 15px;
        }
    </style>
</head>
<body>
    @php
        $docTypeLabel = $document->document_type === 'invoice' ? 'FACTURA' : 'COTIZACION';
        $issueDate = $document->issued_at?->format('d/m/Y') ?? $document->created_at->format('d/m/Y');
        $quoteValidUntil = $document->issued_at ? $document->issued_at->copy()->addDays(15)->format('d/m/Y') : now()->addDays(15)->format('d/m/Y');
        $itemsQty = $document->items->sum(fn ($item) => (float) $item->quantity);
    @endphp

    <div class="sheet">
        <div class="center muted small">Fecha: {{ $issueDate }}</div>
        <div class="center title">{{ $docTypeLabel }}</div>
        <div class="center"><span class="doc-chip">#{{ $document->document_number }}</span></div>

        <table class="head-grid">
            <tr>
                <td>
                    <p class="block-title">Datos del cliente</p>
                    <p class="block-line"><strong>Nombre:</strong> {{ $document->customerDisplayName() }}</p>
                    <p class="block-line"><strong>ID documento:</strong> {{ $document->document_number }}</p>
                    <p class="block-line"><strong>Origen:</strong> {{ strtoupper($document->source) }}</p>
                    <p class="block-line"><strong>IVA:</strong> {{ strtoupper($document->tax_mode) }} ({{ number_format((float) $document->vat_percentage, 2) }}%)</p>
                    @if($document->customer_mode === 'walk_in')
                        <p class="block-line"><strong>Tipo cliente:</strong> Mostrador</p>
                    @endif
                </td>
                <td>
                    <p class="block-title">Datos de la empresa</p>
                    <p class="block-line"><strong>Empresa:</strong> {{ $document->company->name }}</p>
                    <p class="block-line"><strong>Dirección:</strong> {{ trim(($document->company->address_line ?? '').' '.($document->company->city ?? '').' '.($document->company->state ?? '')) ?: 'No especificada' }}</p>
                    <p class="block-line"><strong>Email:</strong> {{ $document->company->billing_email ?: $document->company->owner_email }}</p>
                    <p class="block-line"><strong>Teléfono:</strong> {{ $document->company->billing_phone ?: $document->company->owner_phone }}</p>
                    <p class="block-line"><strong>Moneda:</strong> {{ $document->company->currency ?: 'MXN' }}</p>
                </td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:44%;">Producto / Servicio</th>
                    <th style="width:10%;">Cantidad</th>
                    <th style="width:14%;">Precio</th>
                    <th style="width:16%;">IVA</th>
                    <th style="width:16%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($document->items as $item)
                    <tr>
                        <td>{{ $item->description }} ({{ strtoupper($item->item_kind) }})</td>
                        <td class="center">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="right">${{ number_format((float) $item->line_subtotal, 2) }}</td>
                        <td class="right">${{ number_format((float) $item->line_vat, 2) }}</td>
                        <td class="right">${{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>Total de partidas</strong></td>
                    <td class="center">{{ number_format((float) $itemsQty, 2) }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <table class="bottom-grid">
            <tr>
                <td class="note">
                    @if($document->document_type === 'quote')
                        <strong>Nota:</strong> Esta cotización tiene validez de 15 días a partir de su emisión (hasta {{ $quoteValidUntil }}).<br>
                    @else
                        <strong>Nota:</strong> Documento emitido para control fiscal y administrativo interno.<br>
                    @endif
                    @if($document->notes)
                        <strong>Comentarios:</strong> {{ $document->notes }}
                    @else
                        Sin comentarios adicionales.
                    @endif
                </td>
                <td class="summary">
                    <table>
                        <tr><td>SubTotal</td><td class="right">${{ number_format((float) $document->subtotal, 2) }}</td></tr>
                        <tr><td>IVA</td><td class="right">${{ number_format((float) $document->vat_amount, 2) }}</td></tr>
                        <tr><td>Total</td><td class="right">${{ number_format((float) $document->total, 2) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
