# Lo que necesitas antes de empezar

> Los nombres de pantallas y botones de Meta e Instagram aparecen en español con el nombre en inglés entre paréntesis. Meta traduce sus menús de forma distinta según la cuenta y el país: si no encuentras el nombre en español, busca el nombre en inglés.

Repasa esta lista antes de instalar nada. Para cada punto: qué es, cómo comprobar si lo tienes y qué hacer si no lo tienes. Claude te los pregunta uno a uno.

## 1. Una cuenta de Instagram de empresa o de creador, y pública

**Qué es.** Instagram tiene cuentas personales, de empresa y de creador. Solo las cuentas de empresa y de creador permiten que una app lea comentarios y envíe mensajes. La cuenta tiene que ser pública; si es privada, Meta no transmite nada.

**Cómo comprobarlo.** En Instagram: tu perfil, el menú de arriba a la derecha, Configuración (Settings). Si hay un apartado para cuentas profesionales (el nombre cambia según la versión, por ejemplo "Tipo de cuenta y herramientas" (Account type and tools)), tienes una cuenta profesional. En Privacidad de la cuenta (Account privacy), "Cuenta privada" (Private account) tiene que estar desactivada.

**Si no lo tienes.** Cambiar es gratis y se hace en el propio Instagram: Configuración (Settings), Tipo de cuenta y herramientas (Account type and tools), Cambiar a cuenta profesional (Switch to professional account). Tus seguidores y publicaciones se quedan.

## 2. Una página de Facebook conectada a esa cuenta de Instagram

**Qué es.** Jensiechat usa la conexión de Instagram de Meta a través de Facebook (Meta lo llama Unified login). Para ello, tu cuenta de Instagram tiene que estar conectada a una página de Facebook de tu consulta.

**Cómo comprobarlo.** En Instagram: Editar perfil (Edit profile). Si aparece una página en "Página" (Page), está conectada. O en Facebook, en tu página: Configuración (Settings), Cuentas vinculadas (Linked accounts).

**Si no lo tienes.** Crea una página de Facebook para tu consulta (gratis). Después conecta tu cuenta de Instagram a ella, desde Editar perfil (Edit profile) en Instagram o desde el Centro de cuentas (Accounts Center) de Meta.

## 3. Una cuenta de Facebook con derechos de administrador, en un portfolio empresarial

**Qué es.** Tu cuenta personal de Facebook tiene que ser administradora de la página y de un portfolio empresarial en business.facebook.com (antes Business Manager). La página y la cuenta de Instagram están en ese portfolio. Más adelante creas allí un "usuario del sistema" (system user): una cuenta técnica cuyo token no caduca. La autenticación en dos pasos tiene que estar activada; Meta la pide en estas pantallas.

**Cómo comprobarlo.** Ve a business.facebook.com y abre Configuración del negocio (Business settings). Si ves tu página y tu cuenta de Instagram en Cuentas (Accounts), y eres administrador en Usuarios, Personas (Users, People), está todo bien.

**Si no lo tienes.** Crea un portfolio en business.facebook.com (gratis) y añade tu página y tu cuenta de Instagram. Activa la autenticación en dos pasos en la configuración de tu cuenta de Facebook (Centro de cuentas (Accounts Center), Contraseña y seguridad (Password and security)).

## 4. Alojamiento web con PHP, HTTPS y una tarea programada cada minuto

**Qué es.** Jensiechat es un pequeño programa en PHP que tiene que estar en un servidor día y noche: Meta tiene que poder llegar a él y él tiene que poder comprobar cada minuto. Lo que hace falta exactamente:
- PHP 8.1 o superior, con las extensiones pdo_sqlite (la base de datos), curl (hablar con Meta) y mbstring (texto);
- HTTPS con un certificado válido en la dirección donde va Jensiechat;
- una tarea programada (los proveedores lo llaman cron job) que pueda ejecutar un archivo PHP cada minuto;
- preferiblemente un servidor Apache o LiteSpeed, que lee `.htaccess` (para la protección y los enlaces cortos). La mayoría de los alojamientos web normales lo tienen.

No hace falta MySQL, ni Node, ni SSH.

**Cómo comprobarlo.** Si tienes una web de WordPress en tu propio alojamiento, casi siempre ya lo tienes. Para estar seguro, usa `keuring.php` del paquete: súbelo y ábrelo en tu navegador; te dice verde o rojo para cada punto. El cron job no lo puede ver: mira en el panel de control de tu proveedor si hay "Cron jobs" o "Tareas programadas" y si puedes elegir "cada minuto".

**Si no lo tienes.** Si tu web está en un creador de webs sin alojamiento propio (como Wix, Squarespace o Jimdo), Jensiechat no puede funcionar allí. Contrata además un pequeño plan de alojamiento y haz que un subdominio (por ejemplo `chat.tupractica.com`) apunte a él; tu web se queda donde está. El proveedor lo eliges tú; Claude puede explicarte en qué fijarte, pero no contrata nada por ti. Si tu proveedor no puede ejecutar una tarea cada minuto, pregunta si es posible o elige otro plan.

## 5. Acceso al administrador de archivos o FTP de tu proveedor

**Qué es.** Tienes que poder subir archivos, descomprimir un zip, crear una carpeta o un subdominio y editar un archivo (`config.php`).

**Cómo comprobarlo.** Entra en tu proveedor. Si ves algo como "Administrador de archivos", "File Manager" o datos de FTP, está todo bien.

**Si no lo tienes.** Pide a tu proveedor los datos de acceso al panel de control, o los datos de FTP. Si otra persona hizo tu web, pídele acceso para ti.

## 6. Tu propia web con una página de privacidad

**Qué es.** Tú eres responsable de los datos de las personas que comentan (RGPD). Tu web tiene que decir qué guardas, por qué, durante cuánto tiempo y cómo puede alguien pedir que se borre. El paquete incluye una plantilla.

**Cómo comprobarlo.** ¿Hay una política de privacidad en tu web, y puedes editarla tú?

**Si no lo tienes.** Crea una página "Privacidad" en tu web. Claude te ayuda con el texto sobre Jensiechat; para el resto de tu política de privacidad, lo sensato es una plantilla de tu asociación profesional o de un abogado.

## 7. Una segunda cuenta de Instagram tuya

**Qué es.** Para probar, comentas con la palabra clave desde otra cuenta distinta de la de tu consulta. Jensiechat se salta los comentarios de tu propia cuenta de la consulta, y no quieres hacer pruebas con tus seguidores.

**Cómo comprobarlo.** ¿Tienes una cuenta personal, o puedes crear una con otra dirección de correo?

**Si no lo tienes.** Añade una cuenta extra en Instagram (Perfil, tu nombre arriba, Añadir cuenta (Add account)).

## 8. Un gestor de contraseñas u otro lugar seguro

**Qué es.** Meta te da un token y un secreto de la app (app secret), y tú eliges una contraseña para el panel. Nada de eso va en una nota, un correo o un chat.

**Cómo comprobarlo.** ¿Ya usas un gestor de contraseñas (por ejemplo el de tu navegador o tu móvil, o una app aparte)?

**Si no lo tienes.** Con el gestor de contraseñas de tu móvil o de tu navegador basta.

## 9. Dos o tres horas, que puedes repartir

**Qué es.** Una estimación para quien ya tiene las cuentas. Si todavía tienes que montar un portfolio o un alojamiento, lleva más tiempo. Puedes parar después de cada fase; Claude lleva la cuenta en `voortgang.md`.

## 10. Esta skill en Claude

**Qué es.** Claude te guía: en la app de Claude o en Claude Code. Tú inicias sesión y haces clic, en tu propio navegador, donde ya has iniciado sesión en Facebook. Claude te dice en cada pantalla qué buscar.

**Cómo comprobarlo.** Si estás leyendo esto a través de Claude, está todo bien.

---

**¿Has terminado la lista?** La instalación empieza en la fase 1 de `ruta-de-instalacion.md`.
