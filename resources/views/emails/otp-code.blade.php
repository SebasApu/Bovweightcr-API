<x-mail::message>
# Tu código de verificación

Hola, **{{ $usuario->nombre }}**.

Usa el siguiente código para continuar con el cambio de tu contraseña en **BovWeight CR**:

<x-mail::panel>
<div style="font-size: 32px; font-weight: 700; letter-spacing: 8px; text-align: center;">
{{ $codigo }}
</div>
</x-mail::panel>

> Este código vence en **2 minutos**. Si no solicitaste este cambio, puedes ignorar este correo: tu contraseña actual seguirá funcionando.

Saludos,<br>
**Equipo BovWeight CR**
</x-mail::message>
