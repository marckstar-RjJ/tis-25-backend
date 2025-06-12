@component('mail::message')
# Restablecer Contraseña

Has recibido este correo electrónico porque se ha solicitado un restablecimiento de contraseña para tu cuenta.

Por favor, haz clic en el siguiente botón para restablecer tu contraseña:

@component('mail::button', ['url' => $resetUrl])
Restablecer Contraseña
@endcomponent

Este enlace de restablecimiento de contraseña caducará en 24 horas.

Si no solicitaste un restablecimiento de contraseña, no se requiere ninguna acción adicional.

Saludos,
{{ config('app.name') }}
@endcomponent 