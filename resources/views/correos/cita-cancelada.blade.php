<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Cita cancelada - Book&amp;Cut</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F3F4F6; font-family: Arial, Helvetica, sans-serif; color: #18181B;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; background-color: #F3F4F6;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="500" cellspacing="0" cellpadding="0" border="0" style="width: 100%; max-width: 500px; background-color: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 12px;">
                    <tr>
                        <td align="center" style="padding: 36px 32px 12px;">
                            <div style="font-size: 28px; line-height: 36px; font-weight: 700; letter-spacing: -0.5px; color: #18181B;">
                                Book&amp;Cut
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 12px 32px 0;">
                            <h1 style="margin: 0; font-size: 24px; line-height: 32px; font-weight: 700; color: #E11D48;">
                                Cita Cancelada
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 32px 0; font-size: 16px; line-height: 24px; color: #374151;">
                            Hola {{ $nombreCliente }},
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 16px 32px 0; font-size: 16px; line-height: 24px; color: #4B5563;">
                            Tu cita ha sido cancelada automáticamente debido a que no recibimos tu confirmación en el tiempo establecido (30 minutos).
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 20px 32px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; background-color: #F8F9FA; border-radius: 8px;">
                                <tr>
                                    <td align="center" style="padding: 16px; font-size: 16px; line-height: 24px; color: #374151;">
                                        Fecha programada: <strong style="color: #18181B;">{{ $fecha }}</strong>
                                        a las <strong style="color: #18181B;">{{ $hora }}</strong>.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 28px 32px 32px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" bgcolor="#18181B" style="border-radius: 50px;">
                                        <a href="{{ $enlaceReservas }}" target="_blank" style="display: inline-block; padding: 16px 36px; border: 1px solid #18181B; border-radius: 50px; background-color: #18181B; color: #FFFFFF; font-size: 16px; line-height: 20px; font-weight: 700; text-align: center; text-decoration: none;">
                                            Agendar nueva cita
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 32px;">
                            <div style="height: 1px; background-color: #E5E7EB; line-height: 1px;">&nbsp;</div>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 20px 32px 32px; font-size: 12px; line-height: 18px; color: #9CA3AF;">
                            Si tienes algún problema, por favor contáctanos. Book&amp;Cut - Tu estilo, tu momento.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
