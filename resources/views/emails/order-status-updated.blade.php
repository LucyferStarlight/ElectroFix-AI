<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualización de orden</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h1 style="margin-bottom: 8px;">{{ $headline }}</h1>
    <p>{{ $messageBody }}</p>

    <p><strong>Orden:</strong> #{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}</p>
    <p><strong>Cliente:</strong> {{ $order->customer?->name }}</p>
    <p><strong>Equipo:</strong> {{ trim(($order->equipment?->brand ?? '').' '.($order->equipment?->type ?? '').' '.($order->equipment?->model ?? '')) ?: 'Equipo no especificado' }}</p>
    <p><strong>Estado actual:</strong> {{ \App\Support\OrderStatus::label((string) $order->status) }}</p>

    @if($order->symptoms)
        <p><strong>Síntomas reportados:</strong> {{ $order->symptoms }}</p>
    @endif

    <hr style="margin: 24px 0;">

    <p>Este aviso fue enviado por ElectroFix-AI.</p>
    <p>Soporte: {{ $supportEmail }}</p>
    @if($supportWhatsapp)
        <p>WhatsApp: {{ $supportWhatsapp }}</p>
    @endif
</body>
</html>
