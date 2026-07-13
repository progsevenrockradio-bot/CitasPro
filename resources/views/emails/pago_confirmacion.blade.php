<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Confirmado - CitasPro</title>
</head>
<body style="margin:0;padding:0;background-color:#0B0F19;font-family:'Segoe UI',Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0B0F19;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">

                    <!-- LOGO / CABECERA -->
                    <tr>
                        <td align="center" style="padding-bottom:32px;">
                            <div style="display:inline-block;background:linear-gradient(135deg,#6366F1,#8B5CF6);border-radius:16px;padding:14px 28px;">
                                <span style="color:#ffffff;font-size:26px;font-weight:800;letter-spacing:-0.5px;">CitasPro</span>
                            </div>
                        </td>
                    </tr>

                    <!-- TARJETA PRINCIPAL -->
                    <tr>
                        <td style="background-color:#111827;border-radius:20px;border:1px solid rgba(16,185,129,0.3);overflow:hidden;">

                            <!-- Barra superior de color VERDE para pagos -->
                            <div style="height:4px;background:linear-gradient(90deg,#10B981,#34D399,#6EE7B7);"></div>

                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:40px 40px 32px;">

                                        <!-- Ícono de éxito -->
                                        <div style="text-align:center;margin-bottom:24px;">
                                            <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;background:rgba(16,185,129,0.15);border:2px solid #10B981;border-radius:50%;">
                                                <span style="font-size:28px;">✅</span>
                                            </div>
                                        </div>

                                        @if($rol === 'cliente')
                                        <!-- Versión para el CLIENTE -->
                                        <h1 style="color:#F9FAFB;font-size:22px;font-weight:700;margin:0 0 8px;text-align:center;line-height:1.3;">
                                            ¡Pago Confirmado!
                                        </h1>
                                        <p style="color:#9CA3AF;font-size:15px;margin:0 0 28px;text-align:center;line-height:1.6;">
                                            Hola <strong style="color:#F9FAFB;">{{ $pago->cliente?->nombre }}</strong>,
                                            tu pago ha sido procesado correctamente. Tu cita está confirmada.
                                        </p>
                                        @else
                                        <!-- Versión para el NEGOCIO -->
                                        <h1 style="color:#F9FAFB;font-size:22px;font-weight:700;margin:0 0 8px;text-align:center;line-height:1.3;">
                                            Nuevo Pago Recibido
                                        </h1>
                                        <p style="color:#9CA3AF;font-size:15px;margin:0 0 28px;text-align:center;line-height:1.6;">
                                            El cliente <strong style="color:#F9FAFB;">{{ $pago->cliente?->nombre }} {{ $pago->cliente?->apellido }}</strong>
                                            ha completado el pago de su cita.
                                        </p>
                                        @endif

                                        <!-- BLOQUE RESUMEN DEL PAGO -->
                                        <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:16px;padding:24px;margin-bottom:24px;">
                                            <p style="color:#6B7280;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin:0 0 16px;">Resumen del Pago</p>

                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="padding:6px 0;border-bottom:1px solid rgba(55,65,81,0.4);">
                                                        <span style="color:#9CA3AF;font-size:13px;">Servicio</span>
                                                    </td>
                                                    <td align="right" style="padding:6px 0;border-bottom:1px solid rgba(55,65,81,0.4);">
                                                        <span style="color:#F9FAFB;font-size:13px;font-weight:600;">{{ $pago->cita?->servicio?->nombre ?? 'Servicio' }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:6px 0;border-bottom:1px solid rgba(55,65,81,0.4);">
                                                        <span style="color:#9CA3AF;font-size:13px;">Fecha de la Cita</span>
                                                    </td>
                                                    <td align="right" style="padding:6px 0;border-bottom:1px solid rgba(55,65,81,0.4);">
                                                        <span style="color:#F9FAFB;font-size:13px;font-weight:600;">
                                                            @if($pago->cita?->fecha)
                                                                {{ \Carbon\Carbon::parse($pago->cita->fecha)->translatedFormat('l d \d\e F, Y') }}
                                                                a las {{ substr($pago->cita->hora_inicio, 0, 5) }}
                                                            @else
                                                                —
                                                            @endif
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:6px 0;border-bottom:1px solid rgba(55,65,81,0.4);">
                                                        <span style="color:#9CA3AF;font-size:13px;">Negocio</span>
                                                    </td>
                                                    <td align="right" style="padding:6px 0;border-bottom:1px solid rgba(55,65,81,0.4);">
                                                        <span style="color:#F9FAFB;font-size:13px;font-weight:600;">{{ $pago->negocio?->nombre }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:6px 0;border-bottom:1px solid rgba(55,65,81,0.4);">
                                                        <span style="color:#9CA3AF;font-size:13px;">Método de Pago</span>
                                                    </td>
                                                    <td align="right" style="padding:6px 0;border-bottom:1px solid rgba(55,65,81,0.4);">
                                                        <span style="color:#F9FAFB;font-size:13px;font-weight:600;">{{ ucfirst($pago->metodo) }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:12px 0 0;">
                                                        <span style="color:#9CA3AF;font-size:15px;font-weight:700;">Total Pagado</span>
                                                    </td>
                                                    <td align="right" style="padding:12px 0 0;">
                                                        <span style="color:#10B981;font-size:22px;font-weight:800;">
                                                            {{ number_format($pago->monto, 2) }} {{ strtoupper($pago->moneda ?? 'EUR') }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>

                                        <!-- Código de referencia -->
                                        @if($pago->cita?->codigo_referencia)
                                        <div style="text-align:center;background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);border-radius:12px;padding:16px;margin-bottom:24px;">
                                            <p style="color:#9CA3AF;font-size:11px;letter-spacing:2px;text-transform:uppercase;margin:0 0 6px;">Código de Referencia</p>
                                            <p style="color:#6366F1;font-size:18px;font-weight:800;font-family:'Courier New',monospace;margin:0;letter-spacing:2px;">{{ $pago->cita->codigo_referencia }}</p>
                                        </div>
                                        @endif

                                        <!-- Aviso -->
                                        <div style="background:rgba(99,102,241,0.05);border-left:3px solid #6366F1;border-radius:0 8px 8px 0;padding:14px 16px;margin-bottom:4px;">
                                            <p style="color:#A5B4FC;font-size:13px;margin:0;line-height:1.6;">
                                                📋 Guarda este correo como comprobante de tu pago. Si tienes cualquier consulta,
                                                contacta directamente con <strong>{{ $pago->negocio?->nombre }}</strong>.
                                            </p>
                                        </div>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- PIE DE PÁGINA -->
                    <tr>
                        <td align="center" style="padding:28px 0 0;">
                            <p style="color:#4B5563;font-size:12px;margin:0;line-height:1.6;">
                                © {{ date('Y') }} CitasPro · Sistema de reservas inteligente<br>
                                <a href="https://citaspro.app" style="color:#6366F1;text-decoration:none;">citaspro.app</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
