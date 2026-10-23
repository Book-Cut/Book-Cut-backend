# Confirmación automática de citas

La tarea `citas:procesar-confirmaciones` busca únicamente citas próximas al umbral de dos horas, además de avisos que necesiten reintento. Se ejecuta cada minuto y usa bloqueos del scheduler para evitar ejecuciones superpuestas. La base de datos evita registrar dos avisos para la misma cita y hora.

Las citas creadas con menos de dos horas de anticipación no reciben aviso. Si se reagenda, se registra la nueva fecha de programación y se aplica nuevamente esta regla.

## Correo SMTP

Configura estos valores en el archivo `.env` del backend. No guardes credenciales reales en `.env.example` ni en el repositorio:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-cuenta@gmail.com
MAIL_PASSWORD=tu-contraseña-de-aplicacion
MAIL_FROM_ADDRESS=tu-cuenta@gmail.com
MAIL_FROM_NAME="Book&Cut"
FRONTEND_URL=http://localhost:5173
```

Para Gmail, usa una contraseña de aplicación con verificación en dos pasos. Guarda las credenciales únicamente en `.env`, que no debe subirse al repositorio. Después de cambiarlas, ejecuta `php artisan config:clear`.

## Ejecutar en Windows para la presentación

1. Abre PowerShell en la carpeta del backend.
2. Comprueba la tarea registrada: `php artisan schedule:list`.
3. Deja activa la programación durante la presentación: `php artisan schedule:work`.
4. Mantén esa ventana o proceso activo. Para detenerlo, usa `Ctrl+C`.

Para dejarla ejecutándose sin una terminal abierta, crea una tarea en el Programador de tareas de Windows que se inicie al iniciar sesión:

- **Programa:** ruta completa al `php.exe` de PHP 8.3 o superior.
- **Argumentos:** `artisan schedule:work`.
- **Iniciar en:** ruta completa a `F:\Book-Cut-backend`.

La cuenta que ejecuta la tarea debe tener acceso al backend, a la base de datos y a SMTP. Revisa `storage/logs/laravel.log` si hay errores al enviar. Los avisos se guardan en `notificacion` y pueden consultarse en “Mis citas”.
