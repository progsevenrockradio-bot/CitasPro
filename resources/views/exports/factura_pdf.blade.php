<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura_{{ $pago->id }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 14px; color: #333; margin: 0; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #6366f1; padding-bottom: 10px; }
        .header img { max-height: 80px; margin-bottom: 10px; }
        .header h1 { margin: 0; color: #1e1b4b; font-size: 24px; }
        .info-section { display: table; width: 100%; margin-bottom: 20px; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; }
        .info-col p { margin: 2px 0; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-success { background-color: #dcfce7; color: #166534; }
        .badge-warning { background-color: #fef08a; color: #854d0e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .total-row { font-size: 16px; font-weight: bold; background-color: #f1f5f9; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>

    <div class="header">
        @if($negocio && $negocio->logo)
            @php
                $logoPath = public_path('storage/' . $negocio->logo);
                // Si guardó la URL completa en BD en vez de ruta relativa, intentamos limpiar
                if(str_starts_with($negocio->logo, 'http')) {
                    $logoPath = public_path(str_replace(url('/'), '', $negocio->logo));
                }
            @endphp
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="{{ $negocio->nombre }}">
            @else
                <!-- Imagen no encontrada localmente: {{ $logoPath }} -->
            @endif
        @endif
        <h1>Comprobante de Pago #{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</h1>
        <p>Fecha de Emisión: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="info-section">
        <div class="info-col">
            <strong>DATOS DEL EMISOR (NEGOCIO)</strong>
            <p><strong>{{ $negocio->nombre ?? 'CitasPro' }}</strong></p>
            @if($negocio && $negocio->numero_fiscal)
                <p>NIF/CIF: {{ $negocio->numero_fiscal }}</p>
            @endif
            @if($negocio && $negocio->direccion)
                <p>{{ $negocio->direccion }}</p>
            @endif
            <p>{{ $negocio->telefono ?? '' }}</p>
            <p>{{ $negocio->email ?? '' }}</p>
        </div>
        <div class="info-col">
            <strong>DATOS DEL CLIENTE</strong>
            <p><strong>{{ $cliente->nombre ?? 'Cliente No Registrado' }} {{ $cliente->apellido ?? '' }}</strong></p>
            @if($cliente && $cliente->telefono)
                <p>Teléfono: {{ $cliente->telefono }}</p>
            @endif
            @if($cliente && $cliente->email)
                <p>Email: {{ $cliente->email }}</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Descripción del Servicio</th>
                <th>Fecha de la Cita</th>
                <th>Profesional</th>
                <th style="text-align: right;">Importe</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{ $cita && $cita->servicio ? $cita->servicio->nombre : 'Servicio / Consulta' }}
                    @if($pago->es_sena)
                        <br><small><em>(Pago en concepto de seña/reserva)</em></small>
                    @endif
                </td>
                <td>{{ $cita ? \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') . ' ' . substr($cita->hora_inicio, 0, 5) : 'N/A' }}</td>
                <td>{{ $cita && $cita->profesional ? $cita->profesional->nombre_completo : 'N/A' }}</td>
                <td style="text-align: right;">{{ number_format($pago->monto, 2) }} {{ $pago->moneda ?: 'EUR' }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">TOTAL PAGADO:</td>
                <td style="text-align: right;">{{ number_format($pago->monto, 2) }} {{ $pago->moneda ?: 'EUR' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="info-section">
        <div class="info-col">
            <p><strong>Estado del Pago:</strong> 
                @if($pago->estado === 'completado')
                    <span class="badge badge-success">Completado</span>
                @elseif($pago->estado === 'pendiente')
                    <span class="badge badge-warning">Pendiente</span>
                @else
                    <span class="badge badge-danger">{{ $pago->estado }}</span>
                @endif
            </p>
            <p><strong>Método de Pago:</strong> <span style="text-transform: capitalize;">{{ $pago->metodo }}</span></p>
            @if($pago->pagado_en)
                <p><strong>Fecha de Pago:</strong> {{ \Carbon\Carbon::parse($pago->pagado_en)->format('d/m/Y H:i:s') }}</p>
            @endif
        </div>
        <div class="info-col" style="text-align: right;">
            @if($cita)
                <p><strong>Código de Cita:</strong> {{ $cita->codigo_referencia }}</p>
                <p><strong>Estado Cita:</strong> <span style="text-transform: capitalize;">{{ $cita->estado }}</span></p>
            @endif
        </div>
    </div>

    <div class="footer">
        <p>Gracias por su preferencia.</p>
        <p>Este documento es un comprobante de pago generado electrónicamente por la plataforma CitasPro.</p>
    </div>

</body>
</html>
