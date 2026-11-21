# Si ves esto, haz esto

> Los nombres de pantallas y botones de Meta e Instagram aparecen en español con el nombre en inglés entre paréntesis. Meta traduce sus menús de forma distinta según la cuenta y el país: si no encuentras el nombre en español, busca el nombre en inglés.

Problemas conocidos durante la instalación y el uso. Si tienes dudas, pide una captura de pantalla, sin token, secreto ni contraseña a la vista.

## Alojamiento y panel

| Lo que ves | Qué es | Qué hacer |
|---|---|---|
| Página en blanco o "500 Internal Server Error" | Normalmente una versión de PHP demasiado antigua | Abre `keuring.php`. Si PHP sale en rojo: elige PHP 8.1 o superior en el panel de control del proveedor. |
| "Todavía no está listo" con "falta el archivo config.php" (Not ready yet: there is no config.php) | Falta el archivo de ajustes y la pantalla de configuración `instellen.php` no está en la carpeta | Vuelve a subir `instellen.php` y abre `https://<dirección>/`. O a mano: copia `config.voorbeeld.php` a `config.php` (o a `jensiechat-config.php` una carpeta por encima de la carpeta web) y rellénalo. |
| "Esta pantalla está cerrada" | La pantalla de configuración solo funciona durante las primeras 24 horas después de subir los archivos | En el administrador de archivos, crea un archivo vacío `instellen.aan` en la carpeta de Jensiechat y vuelve a cargar la página. |
| "Tu hosting no deja que Jensiechat escriba un archivo aquí..." | El proveedor no deja que la pantalla de configuración escriba el archivo de ajustes | Pulsa "Descargar config.php" y pon ese archivo como `config.php` en la carpeta de Jensiechat, con el administrador de archivos. |
| "Jensiechat ya está configurado", sin que tú hayas rellenado la pantalla de configuración | Otra persona rellenó la pantalla, o quedaba un archivo de ajustes antiguo | En el administrador de archivos, borra el archivo de ajustes: mira en los dos sitios (`jensiechat-config.php` una carpeta por encima de la carpeta web, `config.php` en la carpeta de Jensiechat), y también la base de datos `jensiechat.db` (en `jensiechat-data` junto a la carpeta web, o en `data/` en la carpeta de Jensiechat): una base de datos existente también mantiene cerrada la pantalla. Después vuelve a hacer la pantalla de configuración, con `instellen.aan` si hace falta. Elige una contraseña nueva que no uses en ningún otro sitio. |
| "Hay un error de escritura en ... hacia la línea ..." (There is a typo in ... around line ...) | Sobra o falta una comilla o una coma en el archivo de ajustes | Revisa esa línea. Cada línea tiene la forma `'naam' => 'text',` |
| "El adres ... tiene que empezar por https://" (The adres ... must start with https://) o "La dirección debe empezar por https://." | La dirección en la pantalla de configuración o `adres` en el archivo de ajustes es http o está vacía | Escribe la dirección completa con https://, sin / al final. |
| "La dashboard_wachtwoord ... es demasiado corta" (The dashboard_wachtwoord ... is too short) | Menos de 12 caracteres | Elige una contraseña más larga en el archivo de ajustes. |
| "Esta contraseña no es correcta" (This password is not correct) | Contraseña distinta de la del archivo de ajustes | Cópiala de tu gestor de contraseñas. Ojo con los espacios. |
| "Demasiadas contraseñas incorrectas" (Too many wrong passwords) | 20 intentos fallidos en quince minutos | Espera quince minutos. |
| "La extensión de PHP pdo_sqlite está desactivada" (The PHP extension pdo_sqlite is switched off) | SQLite está desactivado en el proveedor | Márcala en las extensiones de PHP del panel de control, o pregunta al proveedor. |
| "Jensiechat no puede guardar datos en ningún sitio" (Jensiechat cannot store data anywhere) | No hay permiso de escritura | Crea una carpeta `jensiechat-data` junto a la carpeta web, o pon en `datamap` una ruta donde el servidor pueda escribir. |
| Estado (Health): "Dónde se guardan tus datos" (Where your data is stored) en rojo | La base de datos se puede descargar por internet | No actives nada. Pon `datamap` en el archivo de ajustes en una carpeta fuera de la carpeta web, o pregunta al proveedor por qué `.htaccess` no funciona. |
| Estado (Health): "Dónde se guardan tus datos" (Where your data is stored) en naranja | La base de datos está dentro de la carpeta web, pero protegida | Funciona, pero es mejor una carpeta `jensiechat-data` junto a `public_html`. |
| Un enlace corto da "Not Found" | El servidor no lee `.htaccess` (a menudo nginx) | Pon `mooie_links` en `false` en el archivo de ajustes. Los mensajes nuevos llevarán entonces enlaces con `?s=`. |

## Meta

| Lo que ves | Qué es | Qué hacer |
|---|---|---|
| En "Conexión con Meta": "Una clave tiene un espacio o un carácter extraño. Cópiala de nuevo." | Al copiar se coló un espacio, un salto de línea u otro carácter | Vuelve a copiar la clave del gestor de contraseñas y pégala, sin espacio delante ni detrás. |
| En "Conexión con Meta": "Tu hosting no deja que Jensiechat cambie el archivo de ajustes..." | El proveedor no deja que Jensiechat modifique el archivo de ajustes | Pon tú las claves en el archivo de ajustes con el administrador de archivos: `meta_token`, `meta_app_id` y `meta_app_geheim`, entre las comillas. Claude te dice qué línea; tú pegas. |
| Meta pide una verificación de identidad y se queda colgado | Navegador desconocido o ventana integrada | Usa tu propio navegador de confianza, en el que ya has iniciado sesión en Facebook. |
| En "Crear app" (Create app) falta tu portfolio o sale en gris | No eres administrador del portfolio, o la conexión falla desde esta pantalla | Comprueba en Configuración del negocio (Business settings) que eres administrador. Prueba al revés: Configuración del negocio (Business settings), Cuentas (Accounts), Apps, añade la app con el identificador de la app (app ID). |
| Solo se puede elegir "Inicio de sesión con Instagram" (Instagram login), no el inicio de sesión con Facebook | Otra pantalla u otro orden | Busca en el caso de uso o en la configuración "Configuración de la API con inicio de sesión con Facebook" (API setup with Facebook login). Manda una captura. |
| La invitación de tester se queda "pendiente" (pending) | Aceptada con otra cuenta, o buscada en la app del móvil | Inicia sesión en instagram.com con la cuenta de tu consulta: Configuración (Settings), Apps y sitios web (Apps and websites), Invitaciones de tester (Tester invites). |
| El token no funciona (Estado (Health) en rojo, código 190) | Mal pegado, caducado o revocado | Vuelve a copiarlo del gestor de contraseñas sin espacios y pégalo en Estado (Health), "Conexión con Meta"; si no, crea un token nuevo para el usuario del sistema. |
| Token en verde, pero "No se encuentra ninguna cuenta de Instagram con este token" (Instagram account not found) | El usuario del sistema no tiene acceso a la cuenta de Instagram | Asigna la cuenta de Instagram al usuario del sistema en Configuración del negocio (Business settings) y después crea un token nuevo. |
| Aviso del webhook rechazado por una firma incorrecta | El secreto de la app guardado no es correcto | Vuelve a copiar el secreto de la app (Configuración de la app (App settings), Básica (Basic), Mostrar (Show)) y pégalo en Estado (Health), "Conexión con Meta". |
| Meta dice que no puede verificar la dirección del webhook | Palabra de verificación incorrecta, o no hay un certificado válido | Vuelve a copiar la palabra del archivo de ajustes, sin espacios. Abre la dirección en el navegador: deberías ver "nee", no una página de error. |
| "Aún no ha llegado ningún aviso." (No notification received yet) en el webhook | Normal sin App Review | No hagas nada. La comprobación propia recoge todos los comentarios. |
| Error de permisos al enviar (código 10, 200 o 3) | Al token le falta un permiso, o el interruptor de mensajes está desactivado | Revisa los tres permisos del token; en Instagram activa "Permitir acceso a los mensajes" (Allow access to messages). |
| "(#4) Application request limit reached" o "Límite de Meta" (Meta limit) | Se ha agotado el presupuesto por hora de la app | Espera. Los mensajes se quedan en la cola y salen solos. |

## Pruebas y uso

| Lo que ves | Qué es | Qué hacer |
|---|---|---|
| Comentario publicado, no pasa nada | La palabra clave no coincide, la automatización está desactivada o comentas con la cuenta de tu consulta | Usa "Prueba un comentario" (Try a comment) en el panel; comenta con tu segunda cuenta. |
| Actividad (Activity): "omitido, Modo de prueba: esta cuenta no está en la lista de prueba" (Skipped, test mode) | La cuenta no está en la lista de prueba | Añade tu segunda cuenta en Cuentas de prueba (Test accounts), bien escrita. |
| Actividad (Activity): "Ya recibió este mensaje en las últimas 24 horas" (Already received this message in the last 24 hours) | Misma persona, misma automatización | Es intencionado. Prueba con otra automatización o espera un día. |
| El mensaje llega al cabo de un minuto más o menos | Lo ha recogido la comprobación propia | Normal sin App Review. |
| El mensaje llega como texto con el enlace debajo, sin botón | Meta rechazó el formato con botón | Funciona. Estado (Health) explica en "Botones" (Buttons) qué pasó. |
| Actividad (Activity): "Meta no permite este mensaje" (Meta does not allow this message) | Comentario de hace más de 7 días, o ya respondido por otra herramienta | Responde a mano. Desactiva primero la otra herramienta en la misma publicación. |
| Estado (Health): "Comprobación propia" (Own check) en rojo | El cron job no se está ejecutando | Revisa el cron job en el panel de control del proveedor; el comando está en Estado (Health). Algunos proveedores muestran en el panel la salida de la tarea. |
| Estado (Health): la "Cola" (Queue) no para de crecer | Límite o caída en Meta | Espera; mira el registro. Los comentarios de menos de 7 días siguen saliendo. |
