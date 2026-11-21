# Jensiechat (versión básica)

[English: README.md](README.md), [Nederlands: README.nl.md](README.nl.md)

Tu propia respuesta automática para Instagram, en tu propio alojamiento web, sin suscripción. Alguien comenta una palabra que tú eliges (por ejemplo GUÍA) debajo de tu publicación o reel, y Jensiechat le envía un mensaje privado con un botón hacia tu enlace, publica una respuesta breve debajo del comentario y cuenta los clics. Un panel te muestra qué ha pasado y si todo funciona.

Hecho por Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). Gratuito, licencia MIT. No es un producto de ManyChat ni de Meta: usa la API oficial de Instagram de Meta, para tu propia cuenta.

Este repositorio también es una skill de Claude: Claude te guía en la instalación, paso a paso. Código fuente: github.com/praktijkwerkbank/jensiechat.

## Qué hace

- Reglas de palabra clave (automatizaciones) para una publicación o reel, para todas las publicaciones o para "la próxima publicación que publique".
- Una respuesta privada con texto y hasta dos botones. Si Meta rechaza los botones, sale el mismo texto con los enlaces debajo.
- Una respuesta pública debajo del comentario, alternando entre varias opciones.
- Enlaces cortos que cuentan clics, sin direcciones IP ni cookies; las vistas previas de enlaces hechas por robots no se cuentan.
- Una comprobación propia mediante un cron job (la vía principal, porque sin el App Review de Meta los avisos de comentarios solo llegan para cuentas con un rol en la app), más el webhook de Meta para cuando sí llegan.
- Frena según el consumo que indica Meta, hace una pausa cuando hay límites, pone mensajes en cola y reintenta, un mensaje por persona y por regla cada 24 horas, anonimiza a los 90 días.
- Panel en neerlandés, inglés o español: Resumen, Reglas (editor con vista previa), Actividad, Personas (con etiquetas), Estado (situación con siguientes pasos, registro, borrar los datos de alguien).

No incluido en esta versión básica: recoger direcciones de correo electrónico, una conversación en el chat. Hay prevista una versión ampliada; sigue a @praktijkwerkbank en Instagram para enterarte.

## Qué necesitas antes de empezar

| Necesitas | Cómo comprobarlo | Si no lo tienes |
|---|---|---|
| Una cuenta de Instagram de empresa o de creador, y pública | Instagram, Configuración: un apartado para cuentas profesionales; "Cuenta privada" está desactivado | Cámbiala gratis: Configuración, Tipo de cuenta y herramientas |
| Una página de Facebook conectada a esa cuenta de Instagram | Instagram, Editar perfil, "Página" | Crea una página y conéctala desde Editar perfil o desde el Centro de cuentas de Meta |
| Derechos de administrador en esa página y en un portfolio comercial (business.facebook.com) que contenga la página y la cuenta de Instagram, con la autenticación en dos pasos activada | Business settings, Accounts and Users | Crea un portfolio (gratis), añade la página y la cuenta, activa la autenticación en dos pasos |
| Alojamiento web con PHP 8.1+, pdo_sqlite, curl, mbstring, HTTPS y un cron job cada minuto; mejor con Apache o LiteSpeed | Sube y abre `keuring.php`; busca "Cron jobs" en el panel de tu hosting | Contrata un plan de hosting pequeño y un subdominio como `chat.tuconsulta.com` |
| Acceso al administrador de archivos o al FTP de tu hosting | Inicia sesión en tu hosting | Pregunta a tu hosting o a quien hizo tu web |
| Tu propia web con una página de privacidad | ¿Puedes editar tu política de privacidad? | Crea una página de privacidad; se incluye una plantilla |
| Una segunda cuenta de Instagram propia, para hacer pruebas | | Añade una cuenta en Instagram |
| Un gestor de contraseñas | | El de tu móvil o tu navegador sirve |
| De dos a tres horas, se pueden repartir | | |
| Claude (la app de Claude o Claude Code) con esta skill | | Mira "Instalación" |

Lista completa: [referentie/es/requisitos.md](referentie/es/requisitos.md).

## Instalación

**Con Claude (recomendado).** Añade esta carpeta como skill (en Claude Code: ponla en `~/.claude/skills/jensiechat`; en la app de Claude: sube el zip en el apartado de skills de la configuración, si tu plan admite skills). Después pídele a Claude: "Instala Jensiechat". Claude revisa contigo primero los requisitos y luego recorre las fases. Tú inicias sesión y haces clic; tus claves nunca entran en el chat.

**A mano.** Los pasos, en resumen (detalles en [referentie/es/ruta-de-instalacion.md](referentie/es/ruta-de-instalacion.md)):
1. Crea una carpeta (por ejemplo `public_html/chat`) o un subdominio con HTTPS.
2. Sube el contenido de `code/jensiechat/`, incluidos los archivos ocultos `.htaccess`.
3. Abre `https://<address>/keuring.php` y arregla todo lo que esté en rojo.
4. Copia `config.voorbeeld.php` como `jensiechat-config.php` una carpeta por encima de tu carpeta web (o como `config.php` en la carpeta) y rellena `adres`, `dashboard_wachtwoord` (12 caracteres o más), `taal` (`es`, `en` o `nl`), `naam` y `kleur`.
5. Crea la app en Meta (tipo Business, Instagram con inicio de sesión de Facebook), un usuario del sistema con acceso a la app, a la página y a la cuenta de Instagram, y un token con `instagram_basic`, `instagram_manage_comments` y `instagram_manage_messages`. Activa "Allow access to messages" en la app de Instagram.
6. Pon `meta_app_id`, `meta_app_geheim`, `meta_token` y un `webhook_controlewoord` en el archivo de configuración. Abre el panel, Estado, Comprobar ahora.
7. Crea un cron job que se ejecute cada minuto con el comando que aparece en Estado.
8. Opcional: el webhook en Meta con la dirección de Estado y el `webhook_controlewoord` (verify token) de tu archivo de configuración, suscrito a `comments`.
9. Crea tu primera regla en modo de prueba, pruébala desde tu segunda cuenta y luego desactiva el modo de prueba.
10. Añade el texto de privacidad de `code/privacy/` (`privacy-texto.es.md`) a tu página de privacidad.

## Actualizar

Sube el código nuevo encima de la carpeta antigua. Tu archivo de configuración y tu carpeta de datos se quedan. No sobrescribas nunca tu `config.php` con `config.voorbeeld.php`.

## Eliminar

Desactiva las reglas, borra el cron job, borra la carpeta de Jensiechat y la carpeta de datos (`jensiechat-data` junto a tu carpeta web), elimina la app en Meta y actualiza tu texto de privacidad.

## Privacidad

Jensiechat guarda el nombre de usuario y el ID de Instagram, el comentario, la publicación, la hora, si se envió el mensaje y si se tocó el enlace. Sin direcciones IP, sin cookies, sin fotos de perfil. A los 90 días se borran el nombre, el ID y el texto; los recuentos se quedan. El panel tiene "Borrar los datos de alguien" para las peticiones. Los datos se quedan en tu propio alojamiento, por defecto fuera de la carpeta web. Tú eres responsable de estos datos (RGPD); en `code/privacy/` hay un texto de plantilla. No es asesoramiento jurídico.

## Límites, con sinceridad

- Meta decide las reglas y cambia sus pantallas. Probado en noviembre de 2026 por el autor, y contra una API de Meta simulada; otros proveedores de hosting aún no se han probado.
- Sin App Review, los mensajes llegan en más o menos un minuto, y la app tiene unas 200 llamadas a la API por hora. Cuando hay mucho movimiento, la gente espera en la cola.
- No hay servicio de ayuda. La pestaña Estado dice qué falla y qué hacer a continuación.

## Licencia y autor

MIT, ver [LICENSE](LICENSE). Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). Por favor, mantén la línea "Hecho con Jensiechat de Jens Kemna, Praktijkwerkbank" en el pie del panel.

Jensiechat no es un producto de ManyChat ni de Meta, ni está respaldado por ellos. Instagram y Facebook son marcas registradas de Meta Platforms, Inc. ManyChat es una marca registrada de su propietario.

Versiones: [CHANGELOG.md](CHANGELOG.md). ¿Has encontrado un problema de seguridad? Mira [SECURITY.md](SECURITY.md).
