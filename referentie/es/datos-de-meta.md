# Lo que Meta permite (a noviembre de 2026)

Fuente: la documentación de Meta para la plataforma de Instagram (Private replies, Webhooks setup, Messaging API), consultada el 16 y el 17 de noviembre de 2026, y la primera instalación del creador. Meta puede cambiarlo. Lo que no es seguro se indica.

## Seguro, según la documentación
- **Una respuesta privada por comentario**, hasta 7 días después del comentario (`POST /<instagram-id>/messages` con `recipient.comment_id`).
- **Una respuesta pública** debajo de un comentario siempre es posible (`POST /<comment-id>/replies`).
- **Longitudes:** un mensaje sin botones como máximo 1000 bytes; el texto encima de los botones como máximo 640 caracteres; el texto de un botón como máximo 20 caracteres.
- **La cuenta tiene que ser profesional y pública.** También llegan los comentarios de la propia cuenta; Jensiechat se los salta.
- **Un token de usuario del sistema no caduca.** Un token del panel de la app caduca a los 60 días.
- **Sin App Review, una app tiene unas 200 llamadas por hora.** Jensiechat lee el consumo que Meta indica en cada respuesta y comprueba con menos frecuencia cuando sube.

## Visto en la primera instalación, no prometido por Meta
- La respuesta privada con botón, la respuesta pública y el recuento de clics funcionaron sin App Review, también para una cuenta sin rol en la app, mientras la app no estaba publicada.
- Los avisos de comentarios (webhook) solo llegaron sin App Review para cuentas con un rol en la app. La documentación dice que hace falta Advanced Access para estos avisos. Por eso Jensiechat comprueba por su cuenta, cada 15 a 60 segundos.
- El panel de Meta dice que los datos en vivo requieren App Review. En la práctica funcionó para la propia cuenta. Si Meta lo hace obligatorio, también hará falta App Review para la versión básica.

## Lo que nunca es posible
- Enviar un mensaje a alguien que no ha comentado.
- Más de una respuesta privada por comentario. Si otra herramienta (como ManyChat) ya respondió al mismo comentario, Meta rechaza el mensaje de Jensiechat.
- Ver si alguien te sigue.
