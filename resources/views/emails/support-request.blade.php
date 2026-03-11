@php($support = $supportRequest)
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Soporte ElectroFix-AI</title>
</head>
<body>
    <h2>Nuevo mensaje de soporte</h2>
    <p><strong>Nombre:</strong> {{ $support->name }}</p>
    <p><strong>Email:</strong> {{ $support->email }}</p>
    @if($support->company_id)
        <p><strong>Empresa ID:</strong> {{ $support->company_id }}</p>
    @endif
    @if($support->user_id)
        <p><strong>Usuario ID:</strong> {{ $support->user_id }}</p>
    @endif
    @if($support->source_url)
        <p><strong>Origen:</strong> {{ $support->source_url }}</p>
    @endif
    @if($support->user_agent)
        <p><strong>User Agent:</strong> {{ $support->user_agent }}</p>
    @endif
    <hr>
    <p>{{ $support->message }}</p>
</body>
</html>
