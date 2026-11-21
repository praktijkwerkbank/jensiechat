# Ruta de instalación de Jensiechat (versión básica): el guion para Claude

> Los nombres de pantallas y botones de Meta e Instagram aparecen en español con el nombre en inglés entre paréntesis. Meta traduce sus menús de forma distinta según la cuenta y el país: si no encuentras el nombre en español, busca el nombre en inglés.

Lo que Claude dice y pregunta en cada fase, lo que hace el usuario, lo que sale mal y cuándo está terminada una fase. Los obstáculos con Meta ocurrieron de verdad durante la primera instalación del creador (noviembre de 2026).

Reglas fijas: una pregunta cada vez; nunca claves en el chat; el usuario inicia sesión por sí mismo; nada se pone en marcha sin un sí claro; pide una captura de pantalla cuando una pantalla se vea distinta, con la frase "Asegúrate de que no se vea ningún token, secreto ni contraseña."

Cada fase termina con tres líneas: **Qué funciona ahora**, **Cómo lo ves tú**, **Siguiente paso**. Después, actualiza `voortgang.md`.

El panel y el archivo de configuración usan nombres de ajustes en neerlandés (`adres`, `dashboard_wachtwoord`, `taal`, `naam`, `kleur`); cada uno se explica más abajo en español y en `config.voorbeeld.php`.

---

## Fase 0: bienvenida y requisitos

**Claude dice (brevemente, con sus propias palabras):**
> Con esto montas tu propia respuesta automática para Instagram: alguien comenta una palabra en tu publicación y recibe el enlace en sus mensajes, con una respuesta corta debajo del comentario. Funciona en tu propio alojamiento web, con tu propio nombre, sin suscripción.
> Con sinceridad, desde el principio: necesitas alojamiento web con PHP, creas tu propia app en Meta (es la parte más larga) y eres responsable de los datos de las personas que comentan. Lleva dos o tres horas, y puedes repartirlas. ¿Comprobamos primero que tienes todo?

Después, `requisitos.md`, punto por punto, una pregunta por mensaje. Si falta algo: primero ayuda con eso.

- **Qué funciona ahora:** todavía no hay nada instalado; sabes que tienes todo.
- **Cómo lo ves tú:** cada punto de `voortgang.md` está marcado.
- **Siguiente paso:** tomar las decisiones (fase 1).

## Fase 1: decisiones

Una a una: el nombre de la cuenta de Instagram; la dirección de la web; dónde va Jensiechat (una carpeta `tupractica.com/chat` o un subdominio `chat.tupractica.com`); el idioma (`nl`, `en` o `es`, ajuste `taal`); un nombre para el panel (`naam`; sin "Insta", "IG", "Facebook" ni "Meta") y un color de acento (`kleur`; el color principal de la web suele funcionar).

- **Qué funciona ahora:** las decisiones están tomadas.
- **Cómo lo ves tú:** están en `voortgang.md`.
- **Siguiente paso:** preparar el alojamiento.

## Fase 2: revisión del alojamiento y subida

1. **Carpeta o subdominio.** El usuario crea una carpeta dentro de la carpeta web (a menudo `public_html/chat`) o un subdominio en el panel de control del proveedor. Con un subdominio: activa también el certificado (gratuito); puede tardar hasta una hora.
2. **Subida.** Pon el contenido de `code/jensiechat/` en esa carpeta. Lo más fácil: sube la carpeta como zip en el administrador de archivos y descomprímela allí. Comprobación: la carpeta contiene `index.php`, `lib.php`, `keuring.php` y las carpetas `r`, `taal` y `data`. Los archivos ocultos (`.htaccess`) tienen que ir también; algunos administradores de archivos los ocultan.
3. **Revisión del alojamiento.** Abre `https://<dirección>/keuring.php`. Cada punto sale en verde, naranja o rojo, con lo que hay que hacer. Arregla primero lo rojo. Lo más importante: "Protección con .htaccess" y "Carpeta para los datos"; la base de datos nunca debe poder descargarse por internet.
4. **La pantalla de configuración.** Abre `https://<dirección>/`. Mientras no haya archivo de ajustes, el panel te lleva a la pantalla de configuración `instellen.php`. Allí el usuario hace esto:
   - elige el idioma arriba (Nederlands, English o Español);
   - escribe el nombre del chat;
   - elige un color: uno de los seis círculos, o "Color propio";
   - comprueba la dirección de la carpeta (normalmente ya es correcta);
   - elige una contraseña del panel de al menos 12 caracteres. El botón "Créame una" genera una contraseña segura; guárdala enseguida en tu gestor de contraseñas. Con "Mostrar" ves lo que hay escrito.

   Después pulsa **Configurar**. La pantalla escribe sola el archivo de ajustes: si puede, una carpeta por encima de la carpeta web como `jensiechat-config.php` (junto a `public_html`, no dentro); si no, como `config.php` dentro de la carpeta de Jensiechat. También crea sola un `webhook_controlewoord` (palabra de verificación del webhook) y toma la zona horaria del navegador. Luego aparece "Listo. Tu chat está configurado." con el botón "Abrir tu panel". A partir de ese momento la pantalla de configuración queda cerrada para siempre. Las claves de Meta no van aquí; llegan en la fase 4.

   Claude puede ayudar con el nombre, el color y la dirección, también en el navegador. En los campos de contraseña Claude deja de usar el navegador: el usuario los rellena solo, y Claude no pide la contraseña.
   - **"Esta pantalla está cerrada":** la pantalla de configuración solo funciona durante las primeras 24 horas después de subir los archivos. En el administrador de archivos, crea un archivo vacío `instellen.aan` en la carpeta de Jensiechat y vuelve a cargar la página.
   - **"Tu hosting no deja que Jensiechat escriba un archivo aquí...":** pulsa "Descargar config.php" y pon ese archivo como `config.php` en la carpeta de Jensiechat, con el administrador de archivos.
   - **A mano (la forma antigua):** en el administrador de archivos, copia `config.voorbeeld.php`. Lo más seguro: llama a la copia `jensiechat-config.php` y ponla una carpeta por encima de la carpeta web. Si no: `config.php` dentro de la carpeta de Jensiechat. Rellena: `adres` (la dirección web de la carpeta), `dashboard_wachtwoord` (contraseña del panel, mínimo 12 caracteres, del gestor de contraseñas), `taal`, `naam`, `kleur` y un `webhook_controlewoord` inventado por ti (por ejemplo veinte letras y cifras al azar del gestor de contraseñas). Los campos de Meta se quedan vacíos. El resultado es el mismo.
5. **Abre el panel** en `https://<dirección>/` e inicia sesión con la contraseña.

**Lo que sale mal:**
- Una página en blanco o "500": a menudo una versión de PHP demasiado antigua. Mira lo que dice la revisión del alojamiento.
- "Esta pantalla está cerrada", "Tu hosting no deja que Jensiechat escriba un archivo aquí..." o "Jensiechat ya está configurado" sin que el usuario haya rellenado la pantalla: mira `si-ves-esto.md`.
- "La dirección debe empezar por https://": en la pantalla de configuración o en `adres` hay `http://` o nada.
- Solo con la forma a mano: "Hay un error de escritura en config.php hacia la línea ..." (There is a typo in config.php around line ...): falta una comilla o una coma. Cada línea tiene la forma `'naam' => 'text',`.

- **Qué funciona ahora:** el panel está en tu propia web, protegido con tu contraseña.
- **Cómo lo ves tú:** en Estado (Health), "Todavía no hay ningún token" (Token: no token filled in yet) en rojo, que ahora es lo correcto, y "Dónde se guardan tus datos" (Where your data is stored) en verde o naranja.
- **Siguiente paso:** la app en Meta (fase 3, la parte más larga).

## Fase 3: la app en Meta

**Claude dice primero:** "Esta es la parte más larga, cuenta con entre 45 minutos y hora y media. Lo haces todo en tu propio navegador, donde ya has iniciado sesión en Facebook. Te digo en cada pantalla qué buscar. Meta a veces te pide la contraseña o un código; es normal. Si una pantalla se ve distinta, mándame una captura, sin claves a la vista."

Meta llama a esta ruta "API de Instagram con inicio de sesión con Facebook" (Instagram API with Facebook login) (también Unified login). La ruta "Inicio de sesión con Instagram" (Instagram login) no se ofrecía para apps nuevas cuando el creador hizo la instalación, aunque explicaciones más antiguas todavía la mencionan.

### 3.1 Condiciones previas
Mira en business.facebook.com, Configuración del negocio (Business settings), en Cuentas (Accounts): ¿están la página y la cuenta de Instagram en tu portfolio? ¿Eres administrador? ¿Está activada la autenticación en dos pasos? Si no: primero los puntos 2 y 3 de `requisitos.md`.

### 3.2 Cuenta de desarrollador
Ve a developers.facebook.com e inicia sesión. Si es la primera vez, Meta te registra como desarrollador (unas pocas pantallas, a veces un código por teléfono). Buscas una pantalla con "Mis apps" (My apps).

**Sale mal:** Meta exige una verificación de identidad y se queda colgado. Pasa en un navegador desconocido o en una ventana integrada. Usa tu propio navegador de confianza.

### 3.3 Crear la app
"Crear app" (Create app). Meta pregunta para qué es la app; busca la opción relacionada con Instagram o con empresas. El tipo de app es **Empresa** (Business). Nombre: el nombre de tu panel. Conecta la app a tu portfolio empresarial.

**Sale mal:** el portfolio no aparece en la lista o se queda en gris. Comprueba que eres administrador del portfolio. Si no funciona desde la pantalla de la app, prueba al revés: Configuración del negocio (Business settings), Cuentas (Accounts), Apps, añade una app con el identificador de la app (app ID). Al creador, la conexión solo le funcionó en su propio navegador.

### 3.4 El producto Instagram
En el menú de la app, busca "Añadir productos" (Add products) o una lista de casos de uso (use cases). Elige Instagram, y dentro de él la configuración **con inicio de sesión con Facebook** (with Facebook login) (no "con inicio de sesión con Instagram" (with Instagram login)). Activa los permisos `instagram_basic`, `instagram_manage_comments` e `instagram_manage_messages`.

**Sale mal:** la plantilla activa permisos extra (como `instagram_content_publish`, `ads_read`, `ads_management`). Jensiechat no los usa. Puedes dejarlos mientras la app esté en desarrollo.

### 3.5 Tu cuenta de Instagram como tester
En la app: Roles de la app (App roles), Roles, añadir personas, rol Tester de Instagram (Instagram tester), escribe la cuenta de tu consulta. Después acéptalo en la **web** de Instagram, instagram.com, Configuración (Settings), Apps y sitios web (Apps and websites), Invitaciones de tester (Tester invites). No siempre aparece en la app del móvil.

**Sale mal:** la invitación se queda "pendiente" (pending): has iniciado sesión en instagram.com con otra cuenta, o la estás buscando en la app del móvil.

### 3.6 Usuario del sistema y token
Un token de usuario del sistema no caduca. Un token del panel de la app caduca a los 60 días; ese no lo queremos.
1. business.facebook.com, Configuración del negocio (Business settings), Usuarios (Users), Usuarios del sistema (System users): añade un usuario del sistema con el rol de administrador.
2. Para ese usuario del sistema, "Asignar activos" (Assign assets) (o similar): la app, la página y la cuenta de Instagram, con control total.
3. "Generar token" (Generate token): elige la app, marca `instagram_basic`, `instagram_manage_comments` e `instagram_manage_messages`. Si también te ofrece `pages_show_list` o `business_management` y no estás seguro, márcalos también; solo dan acceso de lectura a tu propia página y portfolio.
4. Guarda el token en tu gestor de contraseñas enseguida. **No lo pegues en el chat.** Meta solo lo muestra una vez.

**Sale mal:**
- El token se creó antes de que el usuario del sistema tuviera acceso a la cuenta de Instagram: entonces Jensiechat no ve ninguna cuenta. Vuelve a generarlo después de asignarla.
- Token perdido: no pasa nada, crea uno nuevo.

### 3.7 Identificador y secreto de la app
En la app: Configuración de la app (App settings), Básica (Basic). El **identificador de la app** (app ID, solo cifras) está arriba. El **secreto de la app** (app secret) está detrás de un botón "Mostrar" (Show) (Meta te pide la contraseña). Los dos al gestor de contraseñas.

**Sale mal:** confundir el token con el secreto de la app. El token viene del usuario del sistema y es largo; el secreto de la app viene de la app y tiene 32 caracteres.

### 3.8 Acceso a los mensajes en Instagram
En la app de Instagram del móvil, con la sesión iniciada en la cuenta de tu consulta: Configuración (Settings), Mensajes y respuestas a historias (Messages and story replies), Controles de mensajes (Message controls) o Herramientas conectadas (Connected tools): "Permitir acceso a los mensajes" (Allow access to messages) activado. El nombre y el lugar cambian según la versión; buscas el interruptor que da a apps externas acceso a tus mensajes. Si está desactivado, Meta no da un error claro; simplemente no llega nada.

### Si se filtra una clave
Si alguien pega un token, un secreto de la app o una contraseña en el chat o lo enseña en una captura, considéralo filtrado:
- **token:** crea un token nuevo para el usuario del sistema y revoca el antiguo (en el usuario del sistema, gestionar o revocar tokens);
- **secreto de la app:** Configuración de la app (App settings), Básica (Basic), restablece el secreto;
- **contraseña del panel:** elige una nueva y ponla en el archivo de ajustes (`jensiechat-config.php` una carpeta por encima de la carpeta web, o `config.php` en la carpeta de Jensiechat), con el administrador de archivos.

El usuario pega un token o un secreto de la app nuevo en el panel, en Estado (Health), tarjeta "Conexión con Meta". Rellena solo el campo nuevo: un campo vacío conserva el valor actual.

- **Qué funciona ahora:** la app existe y tienes un token, un identificador de la app y un secreto de la app, sin que hayan aparecido en el chat.
- **Cómo lo ves tú:** en Usuarios del sistema (System users) ves tu usuario del sistema con la app, la página y la cuenta de Instagram; tu gestor de contraseñas guarda los tres valores.
- **Siguiente paso:** las claves en el panel y el cron job (fase 4).

## Fase 4: claves en el panel y el cron job

1. **Conexión con Meta.** Panel, Estado (Health), tarjeta "Conexión con Meta". El usuario pega él mismo desde el gestor de contraseñas: "Token de acceso" (el token del usuario del sistema), "ID de la app" y "Secreto de la app". "ID de Instagram" puede quedar vacío; Jensiechat lo busca con el token. Después pulsa **Guardar y comprobar**. Los valores se guardan en el archivo de ajustes de su propio servidor y no se vuelven a mostrar: en cada campo solo pone "guardado" o "vacío". No los pegues en el chat. Claude no usa el navegador en esta tarjeta; el usuario avisa cuando ha terminado.
   - "Una clave tiene un espacio o un carácter extraño. Cópiala de nuevo.": vuelve a copiar la clave del gestor de contraseñas, sin espacio ni salto de línea delante o detrás.
   - "Tu hosting no deja que Jensiechat cambie el archivo de ajustes...": entonces el usuario pone él mismo las claves en el archivo de ajustes, con el administrador de archivos: `meta_token`, `meta_app_id` y `meta_app_geheim` (secreto de la app), entre las comillas. Claude dice qué línea es; el usuario escribe o pega. Claude no abre el archivo en el editor.
   - La pantalla de configuración ya creó el `webhook_controlewoord`. Quien hizo el archivo de ajustes a mano lo inventó en la fase 2.
2. Panel, Estado (Health), **Comprobar ahora** (Check now). "Token" y "Cuenta de Instagram" (Instagram account) se ponen en verde, con el nombre de tu cuenta.
3. Estado (Health) muestra el comando para el cron job en "Comprobación propia" (Own check). En el panel de control del proveedor, crea un cron job que se ejecute **cada minuto** con ese comando. Si en tu proveedor PHP tiene otra ruta (por ejemplo una ruta con un número de versión), usa la ruta del proveedor.
4. Pasados dos minutos: vuelve a cargar Estado (Health).

**Sale mal:** mira `si-ves-esto.md`.

- **Qué funciona ahora:** Jensiechat habla con Meta y comprueba cada minuto.
- **Cómo lo ves tú:** en Estado (Health), Token, Cuenta de Instagram (Instagram account) y Comprobación propia (Own check) están en verde.
- **Siguiente paso:** si quieres, el webhook (fase 5); si no, la primera automatización (fase 6).

## Fase 5: webhook (opcional)

En la app de Meta: Instagram, Webhooks (a veces dentro del caso de uso). La URL de callback aparece en Estado (Health), en "Avisos de Meta (webhook)" (Notifications from Meta (webhook)). La palabra de verificación (en Meta: Token de verificación (Verify token)) es el `webhook_controlewoord` del archivo de ajustes; Estado (Health) no la muestra. El usuario la copia solo del archivo, en el administrador de archivos; Claude no usa el navegador ahí. Suscríbete a `comments`. Después pulsa **Activar avisos** (Switch on notifications) en Estado (Health).

Dilo con sinceridad: sin App Review, Meta normalmente solo envía avisos de comentarios para cuentas con un rol en la app. "Aún no ha llegado ningún aviso." (No notification received yet) es normal. El cron job hace el trabajo; con el webhook, el comentario de un tester a veces llega antes.

**Sale mal:** Meta dice que no puede verificar la dirección. La palabra de verificación se copió con un espacio al final, no hay un certificado válido o la dirección está bloqueada. Al abrir la dirección en el navegador debería aparecer "nee", no una página de error del proveedor.

- **Qué funciona ahora:** Meta conoce la dirección de tu webhook.
- **Cómo lo ves tú:** el registro dice "Meta ha verificado la dirección del webhook" (Meta has verified the webhook address).
- **Siguiente paso:** la primera automatización.

## Fase 6: la primera automatización, en modo de prueba

Pregunta de una en una: la palabra clave (corta, una sola palabra); el enlace; qué publicación (una publicación, todas las publicaciones o "la próxima publicación que suba").

Claude escribe una propuesta con el tono de la consulta: el mensaje (encima de botones como máximo 610 caracteres; sin botones como máximo 1000 bytes), el texto del botón (como máximo 20 caracteres), tres respuestas públicas. Compruébalo con "Prueba un comentario" (Try a comment).

Guarda con **Activada** (On) y **Modo de prueba** (Test mode) activado, con la segunda cuenta en la lista de prueba. Después el usuario comenta la palabra clave en la publicación desde la segunda cuenta.

Comprobación: en más o menos un minuto llega el mensaje privado a la segunda cuenta, la respuesta pública está debajo del comentario y, después de tocar el botón, Actividad (Activity) muestra "Ha hecho clic" (Clicked). Después, tras un sí claro: modo de prueba desactivado.

Consejos: di en tu publicación qué recibe la gente y qué palabra tiene que escribir; nada de "sígueme primero"; sigue respondiendo también en persona; si otra herramienta (como ManyChat) está activa en la misma publicación, desactívala primero, porque Meta solo permite una respuesta privada por comentario.

- **Qué funciona ahora:** todo el que escribe la palabra clave recibe el mensaje.
- **Cómo lo ves tú:** Actividad (Activity) muestra tu comentario de prueba con "Mensaje con botón enviado" (Message with button sent), "Respuesta pública" (Public reply) y "Ha hecho clic" (Clicked).
- **Siguiente paso:** el texto de privacidad (fase 7).

## Fase 7: privacidad

Claude rellena `code/privacy/privacy-texto.es.md` con los datos de la consulta. El usuario lo lee, lo ajusta y lo pone en la página de privacidad. Señala "Borrar los datos de alguien" (Erase someone's data) en Estado (Health) para las solicitudes, y el contrato de encargo del tratamiento con el proveedor de alojamiento (a menudo una casilla o una descarga en la cuenta). Di que esto no es asesoramiento jurídico.

- **Qué funciona ahora:** cuentas a los visitantes qué pasa con sus datos.
- **Cómo lo ves tú:** el texto está en tu página de privacidad.
- **Siguiente paso:** terminar (fase 8).

## Fase 8: terminado y mantenimiento

Transmite: mira Estado (Health) una vez a la semana; si algo se para, los comentarios de menos de 7 días se siguen respondiendo en cuanto vuelve a funcionar; una vez al año revisa el texto de privacidad y cambia la contraseña del panel (en el archivo de ajustes, con el administrador de archivos). Anota en `voortgang.md` que la instalación está terminada.

Actualizar: sube el código nuevo encima de la carpeta antigua. El archivo de ajustes y la carpeta de datos se quedan. Nunca sobrescribas el archivo de ajustes con `config.voorbeeld.php`.

Dejar de usarlo: automatizaciones desactivadas, cron job eliminado, la carpeta y la carpeta de datos borradas, el archivo de ajustes borrado también (mira también una carpeta por encima de la carpeta web, `jensiechat-config.php`), la app eliminada en Meta, texto de privacidad actualizado.
