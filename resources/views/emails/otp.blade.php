<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de acceso CitasPro</title>
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
                        <td style="background-color:#111827;border-radius:20px;border:1px solid rgba(99,102,241,0.3);overflow:hidden;">

                            <!-- Barra superior de color -->
                            <div style="height:4px;background:linear-gradient(90deg,#6366F1,#8B5CF6,#06B6D4);"></div>

                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:40px 40px 32px;">

                                        <!-- Saludo -->
                                        <p style="color:#9CA3AF;font-size:15px;margin:0 0 8px;">Hola, <strong style="color:#F9FAFB;">{{ $nombreUsuario }}</strong> 👋</p>
                                        <h1 style="color:#F9FAFB;font-size:24px;font-weight:700;margin:0 0 16px;line-height:1.3;">
                                            Tu código de acceso a CitasPro
                                        </h1>
                                        <p style="color:#9CA3AF;font-size:15px;margin:0 0 32px;line-height:1.6;">
                                            Usa el siguiente código para verificar tu identidad e iniciar sesión. 
                                            Este código es personal e intransferible.
                                        </p>

                                        <!-- CÓDIGO OTP -->
                                        <div style="background:rgba(99,102,241,0.12);border:2px solid #6366F1;border-radius:16px;padding:28px;text-align:center;margin-bottom:28px;">
                                            <p style="color:#9CA3AF;font-size:12px;font-weight:600;letter-spacing:2px;text-transform:uppercase;margin:0 0 12px;">Código de verificación</p>
                                            <div style="color:#6366F1;font-size:48px;font-weight:800;letter-spacing:12px;line-height:1;font-family:'Courier New',monospace;">
                                                {{ $codigo }}
                                            </div>
                                            <p style="color:#6B7280;font-size:13px;margin:16px 0 0;">
                                                ⏱ Válido por <strong style="color:#F9FAFB;">{{ $expiraMinutos }} minutos</strong>
                                            </p>
                                        </div>

                                        <!-- Aviso de seguridad -->
                                        <div style="background:rgba(16,185,129,0.08);border-left:3px solid #10B981;border-radius:0 8px 8px 0;padding:14px 16px;margin-bottom:28px;">
                                            <p style="color:#6EE7B7;font-size:13px;margin:0;line-height:1.6;">
                                                🔒 <strong>Seguridad:</strong> CitasPro nunca te pedirá este código por teléfono. 
                                                Si no solicitaste este acceso, ignora este correo.
                                            </p>
                                        </div>

                                        <!-- Separador -->
                                        <hr style="border:none;border-top:1px solid rgba(55,65,81,0.5);margin:0 0 24px;">

                                        <!-- Footer del cuerpo -->
                                        <p style="color:#6B7280;font-size:13px;margin:0;line-height:1.6;">
                                            Si tienes algún problema para acceder, visita 
                                            <a href="https://citaspro.app" style="color:#6366F1;text-decoration:none;">citaspro.app</a> 
                                            o contáctanos en soporte.
                                        </p>

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
