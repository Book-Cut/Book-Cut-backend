@extends('correos.layout')
@section('titulo', 'Restablece tu contraseña')
@section('preheader', 'Crea una nueva contraseña. El enlace vence en 60 minutos.')
@section('encabezado', 'Restablece tu contraseña')
@section('contenido')
    <p style="margin:0 0 12px;font-size:16px;line-height:26px;color:#374151;">Hola <strong style="color:#0F172A;">{{ $nombre }}</strong>,</p>
    <p style="margin:0;font-size:16px;line-height:26px;color:#374151;">Recibimos una solicitud para cambiar la contraseña de tu cuenta. Toca el botón para crear una nueva.</p>

    @include('correos.boton', ['url' => $url, 'texto' => 'Crear nueva contraseña'])

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;">
        <tr>
            <td style="padding:14px 16px;background-color:#FBF6E9;border-left:4px solid #C9A24B;border-radius:8px;font-size:14px;line-height:21px;color:#6B5316;">
                Este enlace vence en <strong>60 minutos</strong> y solo se puede usar una vez.
            </td>
        </tr>
    </table>

    <p style="margin:22px 0 4px;font-size:13px;line-height:20px;color:#6B7280;">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
    <p style="margin:0;font-size:12px;line-height:18px;word-break:break-all;"><a href="{{ $url }}" style="color:#BE123C;">{{ $url }}</a></p>
@endsection
@section('pie')
    Si no solicitaste este cambio, ignora este correo: tu contraseña seguirá igual.
@endsection
