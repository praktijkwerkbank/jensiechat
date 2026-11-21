<?php
// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// Hostingkeuring: past deze hosting bij Jensiechat? Open dit bestand in je browser voordat je aan Meta begint.
// Geschreven in oude PHP-schrijfwijze, zodat het ook op een te oude PHP-versie een duidelijke uitslag geeft.
// Toont geen paden of sleutels. Zodra er een instellingenbestand is (Jensiechat is ingesteld), blijft alleen een korte keuring over:
// geen versienummer, geen mappen maken, geen proefbestand en geen aanroep naar de eigen server. Je mag het bestand daarna ook weghalen.
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
$taal = isset($_GET['lang']) && in_array($_GET['lang'], array('nl', 'en', 'es'), true) ? $_GET['lang'] : 'nl';
$en = $taal === 'en';
// Spaans (sinds 1.1): per Nederlandse zin de vertaling; ontbreekt die, dan het Engels.
// KES-BEGIN
$KES = array(
  'PHP-versie' => 'Versión de PHP',
  'Goed.' => 'Bien.',
  'Jensiechat heeft PHP 8.1 of hoger nodig. Zet in het paneel van je host de PHP-versie hoger (meestal een keuzelijst bij PHP-instellingen). Gebruik je WordPress op dezelfde hosting, kijk dan eerst of je site de nieuwe versie aankan.' => 'Jensiechat necesita PHP 8.1 o superior. Sube la versión de PHP en el panel de control de tu hosting (normalmente es una lista desplegable en los ajustes de PHP). Si en el mismo hosting tienes WordPress, comprueba antes que tu web admite la nueva versión.',
  'Uitbreiding ' => 'Extensión ',
  'Staat aan.' => 'Activada.',
  'Staat uit. Zet hem aan in het paneel van je host (PHP-instellingen, uitbreidingen), of vraag je host.' => 'Desactivada. Actívala en el panel de control de tu hosting (ajustes de PHP, extensiones), o pregunta a tu hosting.',
  'Deze pagina komt via een beveiligde verbinding binnen.' => 'Esta página llega por una conexión segura.',
  'Deze pagina is niet via https:// geopend. Open hem via https://. Lukt dat niet, zet dan in het paneel van je host een (gratis) certificaat aan voor dit domein of subdomein; dat kan tot een uur duren.' => 'Esta página no se ha abierto con https://. Ábrela con https://. Si no funciona, activa en el panel de control de tu hosting un certificado (gratuito) para este dominio o subdominio; puede tardar hasta una hora.',
  'Jensiechat is al ingesteld' => 'Jensiechat ya está configurado',
  'De uitgebreide keuring (map voor de gegevens, afscherming, cronjob) staat nu uit, zodat deze pagina niets over je server prijsgeeft. Open het dashboard: onder Gezondheid staat of alles werkt en of je gegevens veilig staan.' => 'La comprobación completa (carpeta de datos, protección, cron job) está ahora desactivada, para que esta página no revele nada de tu servidor. Abre el panel: en Estado verás si todo funciona y si tus datos están seguros.',
  'Map voor de gegevens' => 'Carpeta para los datos',
  'Er is een map jensiechat-data naast je webmap gemaakt, buiten bereik van internet. Daar komt de database.' => 'Se ha creado una carpeta jensiechat-data junto a tu carpeta web, fuera del alcance de internet. Ahí va la base de datos.',
  'Er kan geen map naast je webmap gemaakt worden. Jensiechat gebruikt dan de map data/ hier, afgeschermd met .htaccess. Controleer hieronder of die afscherming werkt. Beter: maak zelf een map buiten je webmap en zet het pad bij datamap in config.php.' => 'No se puede crear una carpeta junto a tu carpeta web. Jensiechat usa entonces aquí la carpeta data/, protegida con .htaccess. Comprueba abajo si esa protección funciona. Mejor aún: crea tú una carpeta fuera de tu carpeta web y pon su ruta en datamap en config.php.',
  'Afscherming met .htaccess' => 'Protección con .htaccess',
  'Werkt: de map data/ is niet via internet te openen. Ook de korte links zullen werken.' => 'Funciona: la carpeta data/ no se puede abrir por internet. Los enlaces cortos también funcionarán.',
  'Werkt niet: je server leest .htaccess niet. Zet dan in config.php mooie_links op false, en zorg dat de gegevens buiten je webmap staan (zie hierboven). Lukt dat niet, ga dan niet verder en vraag je host.' => 'No funciona: tu servidor no lee .htaccess. Pon mooie_links en false en config.php y asegúrate de que los datos están fuera de tu carpeta web (mira arriba). Si no es posible, no sigas y pregunta a tu hosting.',
  'Kon niet getest worden. Open na de installatie het dashboard: onder Gezondheid staat of de gegevens veilig staan.' => 'No se ha podido comprobar. Después de la instalación, abre el panel: en Estado verás si tus datos están seguros.',
  'Geplande taak (cronjob) per minuut' => 'Tarea programada (cron job) cada minuto',
  'Dit kan deze pagina niet zien. Kijk in het paneel van je host of je een cronjob kunt maken die elke minuut draait (bij de meeste pakketten heet het "Cronjobs" of "Geplande taken"). Het aanmaken zelf komt later.' => 'Esta página no puede verlo. Mira en el panel de control de tu hosting si puedes crear un cron job que se ejecute cada minuto (en la mayoría de los planes se llama "Cron jobs" o "Tareas programadas"). Crearlo viene más adelante.',
  'Hostingkeuring' => 'Comprobación del hosting',
  'Past deze hosting?' => '¿Sirve este hosting?',
  'Geen rode punten. Je kunt verder.' => 'Ningún punto en rojo. Puedes continuar.',
  'Er staat iets op rood. Los dat eerst op; bij elk punt staat wat je doet.' => 'Hay algo en rojo. Resuélvelo primero; en cada punto pone qué hacer.',
  'Gemaakt met Jensiechat van Jens Kemna,' => 'Hecho con Jensiechat de Jens Kemna,',
);
// KES-EINDE
function k_tt($nl, $engels) { global $taal, $KES; if ($taal === 'es') return isset($KES[$nl]) ? $KES[$nl] : $engels; return $taal === 'en' ? $engels : $nl; }
$rijen = array();
function k_rij($ok, $naam, $uitleg) { global $rijen; $rijen[] = array($ok, $naam, $uitleg); }

// De webmap (public_html en dergelijke), gevonden door vanaf deze map omhoog te lopen.
$webroot = '';
$p = dirname(__FILE__);
while ($p !== '' && $p !== '/' && $p !== '.' && dirname($p) !== $p) {
  if (in_array(basename($p), array('public_html', 'htdocs', 'httpdocs', 'html', 'www', 'web', 'wwwroot', 'public'), true)) { $webroot = $p; break; }
  $p = dirname($p);
}
// Is Jensiechat al ingesteld? Dan is deze pagina voor iedereen te openen terwijl de installatie al draait: alleen nog de korte keuring.
$ingesteld = is_file(dirname(__FILE__) . '/config.php') || ($webroot !== '' && is_file(dirname($webroot) . '/jensiechat-config.php')) || (getenv('JC_CONFIG') && is_file(getenv('JC_CONFIG')));

// 1. PHP-versie
$phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
k_rij($phpOk, k_tt('PHP-versie', 'PHP version') . ($ingesteld ? '' : ': ' . PHP_VERSION),
  $phpOk ? k_tt('Goed.', 'Good.') : k_tt('Jensiechat heeft PHP 8.1 of hoger nodig. Zet in het paneel van je host de PHP-versie hoger (meestal een keuzelijst bij PHP-instellingen). Gebruik je WordPress op dezelfde hosting, kijk dan eerst of je site de nieuwe versie aankan.', 'Jensiechat needs PHP 8.1 or higher. Raise the PHP version in your host\'s control panel (usually a drop-down under PHP settings). If WordPress runs on the same hosting, first check that your site supports the new version.'));
// 2. Uitbreidingen
$ext = array('pdo_sqlite' => 'SQLite (pdo_sqlite)', 'curl' => 'curl', 'mbstring' => 'mbstring', 'json' => 'json');
foreach ($ext as $e => $naam) {
  $ok = extension_loaded($e);
  k_rij($ok, k_tt('Uitbreiding ', 'Extension ') . $naam, $ok ? k_tt('Staat aan.', 'Switched on.') : k_tt('Staat uit. Zet hem aan in het paneel van je host (PHP-instellingen, uitbreidingen), of vraag je host.', 'Switched off. Switch it on in your host\'s control panel (PHP settings, extensions), or ask your host.'));
}
// 3. HTTPS
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
k_rij($https, 'HTTPS', $https ? k_tt('Deze pagina komt via een beveiligde verbinding binnen.', 'This page arrives over a secure connection.') : k_tt('Deze pagina is niet via https:// geopend. Open hem via https://. Lukt dat niet, zet dan in het paneel van je host een (gratis) certificaat aan voor dit domein of subdomein; dat kan tot een uur duren.', 'This page was not opened through https://. Open it through https://. If that does not work, switch on a (free) certificate for this domain or subdomain in your host\'s control panel; this can take up to an hour.'));
if ($ingesteld) {
  k_rij(null, k_tt('Jensiechat is al ingesteld', 'Jensiechat has already been set up'), k_tt('De uitgebreide keuring (map voor de gegevens, afscherming, cronjob) staat nu uit, zodat deze pagina niets over je server prijsgeeft. Open het dashboard: onder Gezondheid staat of alles werkt en of je gegevens veilig staan.', 'The full check (data folder, protection, cron job) is now switched off, so this page reveals nothing about your server. Open the dashboard: Health shows whether everything works and whether your data is safe.'));
} else {
// 4. Een map buiten de webmap voor de gegevens
$buiten = false;
if ($webroot !== '') {
  $dm = dirname($webroot) . '/jensiechat-data';
  if (!is_dir($dm)) @mkdir($dm, 0700, true);
  $buiten = is_dir($dm) && is_writable($dm);
}
k_rij($buiten ? true : null, k_tt('Map voor de gegevens', 'Folder for the data'), $buiten
  ? k_tt('Er is een map jensiechat-data naast je webmap gemaakt, buiten bereik van internet. Daar komt de database.', 'A folder jensiechat-data was created next to your web folder, out of reach of the internet. The database goes there.')
  : k_tt('Er kan geen map naast je webmap gemaakt worden. Jensiechat gebruikt dan de map data/ hier, afgeschermd met .htaccess. Controleer hieronder of die afscherming werkt. Beter: maak zelf een map buiten je webmap en zet het pad bij datamap in config.php.', 'No folder can be created next to your web folder. Jensiechat then uses the folder data/ here, protected with .htaccess. Check below whether that protection works. Better: create a folder outside your web folder yourself and put its path at datamap in config.php.'));
// 5. Werkt .htaccess? Probeer de afgeschermde map data/ via internet te openen.
$proef = dirname(__FILE__) . '/data/keuring-proef.txt';
@file_put_contents($proef, 'proef');
$afgeschermd = null;
if (is_file($proef) && function_exists('curl_init') && isset($_SERVER['HTTP_HOST']) && preg_match('/^[A-Za-z0-9.-]+(:[0-9]{1,5})?$/', $_SERVER['HTTP_HOST'])) {
  $pad = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/data/keuring-proef.txt';
  $url = ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $pad;
  $ch = curl_init($url);
  curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_SSL_VERIFYPEER => true));
  $body = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($body !== false) $afgeschermd = !($code === 200 && trim($body) === 'proef');
}
@unlink($proef);
k_rij($afgeschermd, k_tt('Afscherming met .htaccess', 'Protection with .htaccess'), $afgeschermd === true
  ? k_tt('Werkt: de map data/ is niet via internet te openen. Ook de korte links zullen werken.', 'Works: the folder data/ cannot be opened over the internet. The short links will work too.')
  : ($afgeschermd === false
    ? k_tt('Werkt niet: je server leest .htaccess niet. Zet dan in config.php mooie_links op false, en zorg dat de gegevens buiten je webmap staan (zie hierboven). Lukt dat niet, ga dan niet verder en vraag je host.', 'Does not work: your server does not read .htaccess. Set mooie_links to false in config.php, and make sure the data is stored outside your web folder (see above). If that is not possible, stop here and ask your host.')
    : k_tt('Kon niet getest worden. Open na de installatie het dashboard: onder Gezondheid staat of de gegevens veilig staan.', 'Could not be tested. After installation, open the dashboard: Health shows whether your data is safe.')));
// 6. Cronjob: kan alleen de gebruiker zelf zien
k_rij(null, k_tt('Geplande taak (cronjob) per minuut', 'Scheduled task (cron job) every minute'), k_tt('Dit kan deze pagina niet zien. Kijk in het paneel van je host of je een cronjob kunt maken die elke minuut draait (bij de meeste pakketten heet het "Cronjobs" of "Geplande taken"). Het aanmaken zelf komt later.', 'This page cannot see this. Check in your host\'s control panel whether you can create a cron job that runs every minute (on most plans it is called "Cron jobs" or "Scheduled tasks"). Creating it comes later.'));
}

$alles = true; foreach ($rijen as $r) if ($r[0] === false) $alles = false;
?><!doctype html>
<html lang="<?php echo $taal; ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?php echo k_tt('Hostingkeuring', 'Hosting check'); ?></title><link rel="stylesheet" href="jensiechat.css"></head>
<body><div class="app"><section class="paneel">
<div class="paneel-kop"><div><h2><?php echo k_tt('Past deze hosting?', 'Does this hosting fit?'); ?></h2>
<p><?php echo $alles ? k_tt('Geen rode punten. Je kunt verder.', 'No red items. You can continue.') : k_tt('Er staat iets op rood. Los dat eerst op; bij elk punt staat wat je doet.', 'Something is red. Fix that first; each item says what to do.'); ?>
 <?php $andere = array(); foreach (array('nl' => 'Nederlands', 'en' => 'English', 'es' => 'Español') as $code => $naamTaal) if ($code !== $taal) $andere[] = '<a href="?lang=' . $code . '" lang="' . $code . '">' . $naamTaal . '</a>'; echo ' ' . implode(', ', $andere); ?></p></div></div>
<ul class="glas checks">
<?php foreach ($rijen as $r) { $stand = $r[0] === true ? 'ok' : ($r[0] === false ? 'mis' : 'let'); ?>
<li class="check" data-stand="<?php echo $stand; ?>"><span class="check-stip" aria-hidden="true"><?php echo $stand === 'ok' ? '&#10003;' : ($stand === 'mis' ? '&#10005;' : '!'); ?></span><div><b><?php echo htmlspecialchars($r[1], ENT_QUOTES, 'UTF-8'); ?></b><p><?php echo htmlspecialchars($r[2], ENT_QUOTES, 'UTF-8'); ?></p></div></li>
<?php } ?>
</ul>
<footer class="voet"><?php echo k_tt('Gemaakt met Jensiechat van Jens Kemna,', 'Made with Jensiechat by Jens Kemna,'); ?> <a href="https://praktijkwerkbank.nl" target="_blank" rel="noopener">Praktijkwerkbank</a>.</footer>
</section></div></body></html>
