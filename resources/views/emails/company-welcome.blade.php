<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido a ElectroFix-AI</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h1 style="margin-bottom: 8px;">Bienvenido a ElectroFix-AI</h1>
    <p>Hola {{ $company->owner_name }},</p>
    <p>Tu taller <strong>{{ $company->name }}</strong> ya está activo con el plan <strong>{{ strtoupper($plan) }}</strong>.</p>
    <p>Accede al dashboard aquí:</p>
    <p><a href="{{ route('dashboard') }}">{{ route('dashboard') }}</a></p>

    <p style="margin-top: 24px;">Soporte:</p>
    <p>Correo: {{ $supportEmail }}</p>
    @if(!empty($supportWhatsapp))
        <p>WhatsApp: <a href="{{ $supportWhatsapp }}">{{ $supportWhatsapp }}</a></p>
    @endif

    <p style="margin-top: 24px;">— ArakataDevs</p>
</body>
</html>
