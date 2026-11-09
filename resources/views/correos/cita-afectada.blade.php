<!DOCTYPE html>
<html lang="es">
  <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Book&amp;Cut</title></head>
    <body style="margin:0;padding:0;background-color:#F3F4F6;font-family:Arial,Helvetica,sans-serif;color:#18181B;">
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#F3F4F6;">
      <tr><td align="center" style="padding:32px 16px;">

        <table role="presentation" width="500" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:500px;background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;">
            <tr><td align="center" style="padding:36px 32px 12px;font-size:28px;font-weight:700;">Book&amp;Cut</td></tr>
            <tr><td align="center" style="padding:12px 32px 0;"><h1 style="margin:0;font-size:24px;line-height:32px;color:#E11D48;">Tu cita no podrá realizarse</h1></td></tr>
            <tr><td style="padding:20px 32px 0;font-size:16px;line-height:24px;color:#374151;">Hola {{ $nombreCliente }},</td></tr>
            <tr><td style="padding:16px 32px 0;font-size:16px;line-height:24px;color:#4B5563;">
            Tu barbero no podrá atender tu cita del <strong>{{ $fecha }}</strong> a las <strong>{{ $hora }}</strong>.
          </td></tr>
            @if (!empty($motivo))
          <tr><td style="padding:12px 32px 0;font-size:16px;line-height:24px;color:#4B5563;">Motivo: <strong>{{ $motivo }}</strong></td></tr>
          @endif
          <tr><td style="padding:12px 32px 0;font-size:16px;line-height:24px;color:#4B5563;">Entra a Mis citas para reagendarla o cancelarla.</td></tr>
          <tr><td align="center" style="padding:28px 32px 32px;">
              <a href="{{ $enlace }}" target="_blank" style="display:inline-block;padding:16px 36px;border-radius:50px;background-color:#18181B;color:#FFFFFF;font-size:16px;font-weight:700;text-decoration:none;">Ir a Mis citas</a>
          </td></tr>
        </table>
      </td></tr></table>
    </body>
</html>