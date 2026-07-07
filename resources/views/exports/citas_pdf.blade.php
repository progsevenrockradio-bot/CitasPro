<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Listado de Citas - {{ $negocio->nombre }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #111;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        .badge {
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .bg-green { background-color: #e6f4ea; color: #137333; }
        .bg-yellow { background-color: #fef7e0; color: #b06000; }
        .bg-red { background-color: #fce8e6; color: #c5221f; }
        .bg-gray { background-color: #f1f3f4; color: #5f6368; }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #777;
            text-align: right;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $negocio->nombre }}</h1>
        <p>Listado de Citas - Generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%">Fecha</th>
                <th style="width: 10%">Hora</th>
                <th style="width: 25%">Cliente</th>
                <th style="width: 25%">Servicio</th>
                <th style="width: 15%">Profesional</th>
                <th style="width: 10%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($citas as $cita)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</td>
                    <td>{{ substr($cita->hora_inicio, 0, 5) }}</td>
                    <td>
                        <strong>{{ $cita->cliente->nombre_completo ?? 'N/A' }}</strong><br/>
                        <span style="font-size: 10px; color: #666;">{{ $cita->cliente->telefono ?? '' }}</span>
                    </td>
                    <td>{{ $cita->servicio->nombre ?? 'N/A' }}</td>
                    <td>{{ $cita->profesional->nombre_completo ?? 'N/A' }}</td>
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

    <div class="footer">
        CitasPro - Gestión de Citas
    </div>
</body>
</html>
