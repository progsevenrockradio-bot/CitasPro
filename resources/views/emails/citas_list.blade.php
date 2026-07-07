<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Listado de Citas - {{ $negocio->nombre }}</title>
    <style>
        body { font-family: sans-serif; color: #333; line-height: 1.5; }
        .header { background: #f4f4f4; padding: 20px; text-align: center; border-bottom: 1px solid #ddd; }
        .content { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #eee; padding: 10px; text-align: left; }
        th { background: #fafafa; font-weight: bold; }
        .footer { font-size: 12px; color: #777; text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .bg-green { background: #e6f4ea; color: #137333; }
        .bg-yellow { background: #fef7e0; color: #b06000; }
        .bg-red { background: #fce8e6; color: #c5221f; }
        .bg-gray { background: #f1f3f4; color: #5f6368; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $negocio->nombre }}</h2>
        <p>Listado de Citas</p>
    </div>

    <div class="content">
        @if($mensaje)
            <p><strong>Mensaje:</strong><br/>{{ $mensaje }}</p>
            <hr>
        @endif

        <p>A continuación se detalla el listado de citas solicitado:</p>

        <table>
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Cliente</th>
                    <th>Servicio</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($citas as $cita)
                    <tr>
                        <td>
                            {{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}<br/>
                            <small>{{ substr($cita->hora_inicio, 0, 5) }}</small>
                        </td>
                        <td>
                            {{ $cita->cliente->nombre_completo ?? 'N/A' }}<br/>
                            <small>{{ $cita->cliente->telefono ?? '' }}</small>
                        </td>
                        <td>
                            {{ $cita->servicio->nombre ?? 'N/A' }}
                        </td>
                        <td>
                            @php
                                $estado = strtolower($cita->estado);
                                $badgeClass = 'bg-gray';
                                if(in_array($estado, ['completada', 'confirmada'])) $badgeClass = 'bg-green';
                                elseif($estado === 'pendiente') $badgeClass = 'bg-yellow';
                                elseif($estado === 'cancelada') $badgeClass = 'bg-red';
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ strtoupper($estado) }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Este es un correo generado automáticamente por CitasPro para {{ $negocio->nombre }}.
    </div>
</body>
</html>
