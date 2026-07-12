<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cita Cancelada</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="color: #e53e3e; text-align: center;">Cita Cancelada</h2>
        
        @if($destinatario === 'paciente')
            <p>Hola {{ $cita->cliente->nombre }},</p>
            <p>Te informamos que tu cita en <strong>{{ $cita->negocio->nombre ?? 'nuestro centro' }}</strong> ha sido cancelada.</p>
        @else
            <p>Hola {{ $cita->negocio->nombre }},</p>
            <p>La cita con el paciente <strong>{{ $cita->cliente->nombre }} {{ $cita->cliente->apellido }}</strong> ha sido cancelada.</p>
        @endif

        <div style="background-color: #f8fafc; padding: 15px; border-left: 4px solid #e53e3e; margin: 20px 0;">
            <p style="margin: 5px 0;"><strong>Servicio:</strong> {{ $cita->servicio->nombre ?? 'Consulta' }}</p>
            <p style="margin: 5px 0;"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</p>
            <p style="margin: 5px 0;"><strong>Hora:</strong> {{ \Carbon\Carbon::parse($cita->hora_inicio)->format('H:i') }}</p>
            @if($destinatario === 'profesional')
                <p style="margin: 5px 0;"><strong>Paciente:</strong> {{ $cita->cliente->nombre }} {{ $cita->cliente->apellido }} ({{ $cita->cliente->telefono }})</p>
            @endif
        </div>

        @if($motivo)
            <p><strong>Motivo de la cancelación:</strong> {{ $motivo }}</p>
        @endif

        <p style="margin-top: 30px; font-size: 0.9em; color: #666; text-align: center;">
            Este es un mensaje automático generado por CitasPro, por favor no respondas a este correo.
        </p>
    </div>
</body>
</html>
