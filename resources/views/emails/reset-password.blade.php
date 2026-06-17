<x-mail::message>
# Recupera tu contraseña

Hola, **{{ $usuario->nombre }}**.

Recibimos una solicitud para restablecer la contraseña de tu cuenta en **BovWeight CR**.

<x-mail::button :url="$url" color="green">
Crear nueva contraseña
</x-mail::button>

> Este enlace vence en 30 minutos. Si no solicitaste este cambio, puedes ignorar este correo: tu contraseña actual seguirá funcionando.

Saludos,<br>
**Equipo BovWeight CR**
</x-mail::message>
