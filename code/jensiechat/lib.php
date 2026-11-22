<?php
// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// Geen product van ManyChat of Meta. Gebruikt de officiële Instagram API van Meta, voor je eigen account.
//
// Dit is de motor. Wat het doet: iemand reageert onder een post of reel met een trefwoord. Dan stuurt Jensiechat
// een privébericht (Private Reply van Meta: een per reactie, tot 7 dagen na de reactie), plaatst een wisselend publiek
// antwoord onder de reactie en zet een regel in het dashboard. Links in het bericht lopen via een korte link (map r/),
// zodat het dashboard ziet of iemand klikte, zonder IP-adres en zonder cookies.
//
// Twee wegen naar binnen, met een afhandeling: de eigen controle (cron.php, elke minuut gestart, kijkt elke 15 tot 60
// seconden) en de webhook van Meta (webhook.php). Zonder App Review stuurt Meta meldingen over reacties alleen voor
// accounts met een rol op de app; de eigen controle is daarom de hoofdweg en de webhook een extra.
// Een reactie wordt maar een keer afgehandeld: de tabel 'gezien' heeft het reactie-ID als sleutel.
//
// Instellingen: config.php (sjabloon: config.voorbeeld.php). Opslag: SQLite, standaard buiten de webmap.
// Bewaard: gebruikersnaam en -ID, reactie, post, tijd, verstuurd, geklikt. Na 90 dagen worden naam, ID en tekst gewist; de cijfers blijven.
if (!defined('JC_APP')) { http_response_code(404); exit; }

const JC_VERSIE = '1.2.1';
const JC_BEWAAR_DAGEN = 90;          // daarna naam, ID en tekst wissen
const JC_ZELFDE_PERSOON_UREN = 24;   // dezelfde persoon krijgt hetzelfde bericht hooguit een keer per etmaal
const JC_PRIVATE_REPLY_DAGEN = 7;    // Meta: een privébericht op een reactie kan tot 7 dagen na de reactie
const JC_PAUZE_LIMIET = 600;         // seconden wachten na een limietfout van Meta bij het lezen
const JC_PAUZE_TUSSEN = 1500000;     // microseconden tussen twee berichten uit de wachtrij
const JC_BUDGET_UUR = 600;           // noodrem op het eigen lezen; de echte rem is het verbruik dat Meta zelf meldt (jc_gebruik)
const JC_MAX_BYTES = 1000;           // Meta: een bericht zonder knoppen is maximaal 1000 bytes
const JC_MAX_KNOPTEKST = 640;        // Meta: de tekst boven de knoppen is maximaal 640 tekens
const JC_MAX_KNOP = 20;              // Meta: de tekst op een knop is maximaal 20 tekens

// ── Instellingen ─────────────────────────────────────────────────────────────
/** De webmap (public_html en dergelijke), gevonden door vanaf deze map omhoog te lopen. Leeg als er geen bekende naam is. */
function jc_webroot() {
  static $w = null; if ($w !== null) return $w;
  $w = ''; $namen = ['public_html', 'htdocs', 'httpdocs', 'html', 'www', 'web', 'wwwroot', 'public'];
  for ($p = __DIR__; $p !== '' && $p !== '/' && $p !== '.' && dirname($p) !== $p; $p = dirname($p)) {
    if (in_array(basename($p), $namen, true)) { $w = $p; break; }
  }
  return $w;
}
/** Waar staat het instellingenbestand? Eerst een map boven de webmap (veiligst), dan in deze map. */
function jc_config_pad() {
  $k = [];
  if (getenv('JC_CONFIG')) $k[] = (string)getenv('JC_CONFIG');
  if (jc_webroot() !== '') $k[] = dirname(jc_webroot()) . '/jensiechat-config.php';
  $k[] = __DIR__ . '/config.php';
  foreach ($k as $pad) if (@is_file($pad)) return $pad; // @: op een host met open_basedir geeft een map boven de webmap een waarschuwing
  return '';
}
$JC_C = []; $JC_CONFIG_FOUT = ''; $JC_CONFIG_VARS = []; $JC_CONFIG_PAD = jc_config_pad();
if ($JC_CONFIG_PAD === '') {
  $JC_CONFIG_FOUT = '@cfg.geen';
} else {
  try { $x = (static function ($p) { return include $p; })($JC_CONFIG_PAD); }
  catch (Throwable $e) {
    // De foutmelding van PHP kan een stuk uit het bestand aanhalen (bijvoorbeeld een token zonder aanhalingstekens). Die pagina is
    // openbaar, dus alles tussen aanhalingstekens gaat eruit, behalve leestekens en de namen van de instellingen zelf.
    $namen = ['adres', 'dashboard_wachtwoord', 'meta_app_id', 'meta_app_geheim', 'meta_token', 'instagram_id', 'webhook_controlewoord', 'taal', 'naam', 'kleur', 'logo', 'website', 'tijdzone', 'datamap', 'mooie_links', 'korte_link_adres', 'graph_adres'];
    $veilig = preg_replace_callback('/"([^"]*)"/', fn($m) => (strlen($m[1]) <= 2 || in_array($m[1], $namen, true)) ? $m[0] : '"..."', $e->getMessage());
    $x = null; $JC_CONFIG_FOUT = '@cfg.tikfout'; $JC_CONFIG_VARS = ['bestand' => basename($JC_CONFIG_PAD), 'regel' => $e->getLine(), 'fout' => mb_substr((string)$veilig, 0, 160)];
  }
  if ($JC_CONFIG_FOUT === '' && !is_array($x)) { $JC_CONFIG_FOUT = '@cfg.geen_lijst'; $JC_CONFIG_VARS = ['bestand' => basename($JC_CONFIG_PAD)]; }
  elseif ($JC_CONFIG_FOUT === '') $JC_C = $x;
}
function jc_cfg($k, $def = '') { global $JC_C; $v = $JC_C[$k] ?? $def; return is_string($v) ? trim($v) : $v; }
/**
 * Het instellingenbestand schrijven (sinds 1.2, voor het invulscherm en de Meta-sleutels in het dashboard).
 * Met var_export, dus aanhalingstekens, backslashes en ?> in een waarde kunnen de PHP-code niet breken.
 * Eerst naar een tijdelijk bestand (alleen voor de eigenaar leesbaar), dan in een keer op zijn plek, zodat er nooit een half bestand staat.
 * $alleenNieuw: alleen aanmaken als er nog geen bestand is (het invulscherm); twee gelijktijdige verzoeken kunnen dan niet allebei winnen.
 * Geeft '' of een foutsleutel.
 */
function jc_config_schrijf(array $c, $pad, $alleenNieuw = false) {
  foreach ($c as $k => $v) if (is_string($v)) {
    // Per byte opschonen (zonder /u): werkt ook bij een waarde die geen geldige UTF-8 is, en raakt geen letters met accenten.
    $schoon = preg_replace('/[\x00-\x1F\x7F]/', '', $v);
    if ($schoon === null) return 'in.niet_schrijfbaar';
    $c[$k] = trim($schoon);
  }
  $inhoud = "<?php\n// Jensiechat " . JC_VERSIE . ", instellingen. Gemaakt of bijgewerkt op " . date('Y-m-d H:i') . " via het invulscherm of het dashboard.\n"
    . "// Uitleg per instelling: config.voorbeeld.php. Je mag dit bestand ook zelf bewerken in de bestandsbeheerder.\n"
    . "return " . var_export($c, true) . ";\n";
  $map = dirname($pad);
  if (!is_dir($map) || !is_writable($map) || (is_file($pad) && !is_writable($pad))) return 'in.niet_schrijfbaar';
  $slot = @fopen($map . '/.jc-config.lock', 'c'); if ($slot) flock($slot, LOCK_EX);
  $klaar = function ($fout) use ($slot) { if ($slot) { flock($slot, LOCK_UN); fclose($slot); } return $fout; };
  if ($alleenNieuw && is_file($pad)) return $klaar('in.al_bestand');
  $tmp = $map . '/.jc-config-' . bin2hex(random_bytes(8)) . '.tmp';
  $oud = umask(0027); $ok = @file_put_contents($tmp, $inhoud, LOCK_EX); umask($oud);
  if ($ok === false) return $klaar('in.niet_schrijfbaar');
  @chmod($tmp, 0640);
  $test = (static function ($p) { try { return include $p; } catch (Throwable $e) { return null; } })($tmp);
  if (!is_array($test) || $test != $c) { @unlink($tmp); return $klaar('in.niet_schrijfbaar'); }
  if ($alleenNieuw) {
    // link() mislukt als het bestand intussen al bestaat; kan de host geen link maken, dan nog een laatste controle en rename.
    if (@link($tmp, $pad)) { @unlink($tmp); }
    elseif (is_file($pad)) { @unlink($tmp); return $klaar('in.al_bestand'); }
    elseif (!@rename($tmp, $pad)) { @unlink($tmp); return $klaar('in.niet_schrijfbaar'); }
  } elseif (!@rename($tmp, $pad)) { @unlink($tmp); return $klaar('in.niet_schrijfbaar'); }
  if (function_exists('opcache_invalidate')) @opcache_invalidate($pad, true);
  return $klaar('');
}
/** Bevat het instellingenbestand alleen vaste waarden (tekst, getallen, true/false)? Dan mag het dashboard het herschrijven. */
function jc_config_alleen_waarden($pad) {
  $mag = [T_OPEN_TAG, T_RETURN, T_ARRAY, T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER, T_DOUBLE_ARROW, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_CLOSE_TAG];
  foreach (token_get_all((string)@file_get_contents($pad)) as $t) {
    if (is_string($t)) { if (strpos('[](),;-', $t) === false) return false; continue; }
    if (in_array($t[0], $mag, true)) continue;
    if ($t[0] === T_STRING && in_array(strtolower($t[1]), ['true', 'false', 'null'], true)) continue;
    return false;
  }
  return true;
}
/** Waar het invulscherm het instellingenbestand neerzet: liefst een map boven de webmap, anders in deze map. */
function jc_config_doel() {
  if (jc_webroot() !== '') { $p = dirname(jc_webroot()) . '/jensiechat-config.php'; if (@is_writable(dirname($p))) return $p; }
  return __DIR__ . '/config.php';
}
/** De taal van het dashboard en de standaardteksten: 'nl' (standaard), 'en' of 'es' (sinds 1.1), uit config.php. */
function jc_taal() { $t = strtolower((string)jc_cfg('taal', 'nl')); return in_array($t, ['nl', 'en', 'es'], true) ? $t : 'nl'; }
/** Een tekst uit taal/<taal>.json. {naam} wordt vervangen door $v['naam']. Ontbreekt de tekst, dan de Nederlandse. */
function jc_t($k, $v = [], $taal = null) {
  static $L = [];
  $taal = $taal ?: jc_taal();
  // Ontbreekt een tekst: Spaans valt eerst terug op Engels (beter leesbaar voor een Spaanstalige), daarna op Nederlands.
  $volg = $taal === 'es' ? ['es', 'en', 'nl'] : [$taal, 'nl'];
  foreach ($volg as $t) if (!isset($L[$t])) $L[$t] = json_decode((string)@file_get_contents(__DIR__ . '/taal/' . $t . '.json'), true) ?: [];
  $s = $k; foreach ($volg as $t) if (isset($L[$t][$k])) { $s = $L[$t][$k]; break; }
  foreach ($v as $a => $b) $s = str_replace('{' . $a . '}', (string)$b, $s);
  return $s;
}
/** Voor meldingen als de taal nog niet bekend is (geen of een kapotte config.php): alle drie de talen. */
function jc_t2($k, $v = []) { return jc_t($k, $v, 'nl') . ' / ' . jc_t($k, $v, 'en') . ' / ' . jc_t($k, $v, 'es'); }

$JC_ADRES      = rtrim((string)jc_cfg('adres'), '/');
$JC_APP_ID     = (string)jc_cfg('meta_app_id');
$JC_APP_SECRET = (string)jc_cfg('meta_app_geheim');
$JC_TOKEN      = (string)jc_cfg('meta_token');
$JC_IG_ID      = (string)jc_cfg('instagram_id');
$JC_VERIFY     = (string)jc_cfg('webhook_controlewoord');
$JC_DASH_KEY   = (string)jc_cfg('dashboard_wachtwoord');

if ($JC_CONFIG_FOUT === '') {
  $lokaal = (bool)preg_match('#^http://(localhost|127\.0\.0\.1)(:\d+)?(/|$)#', $JC_ADRES);
  if ($JC_ADRES === '') $JC_CONFIG_FOUT = jc_t('cfg.adres_leeg');
  elseif (!$lokaal && !preg_match('#^https://[^/\s]+#', $JC_ADRES)) $JC_CONFIG_FOUT = jc_t('cfg.adres_https');
} else {
  // Zonder leesbare config.php is de taal onbekend: de melding in beide talen.
  $JC_CONFIG_FOUT = jc_t2(substr($JC_CONFIG_FOUT, 1), $JC_CONFIG_VARS);
}
/**
 * Klopt het dashboardwachtwoord in de kop X-Sleutel? Geeft 'ok', 'fout' of 'te_vaak'.
 * Tegen raden: na 20 foute pogingen in een kwartier wordt een kwartier niets meer aangenomen. Geldt voor api.php en voor cron.php via het web.
 */
function jc_sleutel_check() {
  global $JC_DASH_KEY;
  // De rem telt per bezoeker (gehasht IP-adres, geen IP in de database), zodat een ander de eigenaar niet kan buitensluiten.
  // Daarnaast een ruime rem voor alle bezoekers samen, tegen raden vanaf veel adressen tegelijk.
  $wie = substr(hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $JC_DASH_KEY), 0, 16);
  $alles = json_decode((string)jc_get('dash_fout', '{}'), true); if (!is_array($alles) || array_is_list($alles)) $alles = [];
  $grens = time() - 900; $totaal = 0;
  foreach ($alles as $k => $tijden) {
    $tijden = array_values(array_filter((array)$tijden, fn($t) => $t > $grens));
    if ($tijden) { $alles[$k] = $tijden; $totaal += count($tijden); } else unset($alles[$k]);
  }
  $goed = $JC_DASH_KEY !== '' && hash_equals($JC_DASH_KEY, (string)($_SERVER['HTTP_X_SLEUTEL'] ?? ''));
  if (count($alles[$wie] ?? []) >= 20) { usleep(400000); return 'te_vaak'; }
  if ($goed) return 'ok';
  if ($totaal >= 300) { usleep(400000); return 'te_vaak'; }
  $alles[$wie][] = time(); $alles[$wie] = array_slice($alles[$wie], -30);
  if (count($alles) > 200) $alles = array_slice($alles, -200, null, true);
  jc_set('dash_fout', json_encode($alles));
  usleep(400000); return 'fout';
}
/** Is het dashboard te openen? Leeg = ja, anders de reden. */
function jc_dashboard_fout() {
  global $JC_DASH_KEY;
  if ($JC_DASH_KEY === '') return jc_t('cfg.wachtwoord_leeg');
  if (strlen($JC_DASH_KEY) < 12) return jc_t('cfg.wachtwoord_kort');
  return '';
}

define('JC_GRAPH', rtrim((string)(getenv('JC_GRAPH_BASE') ?: (jc_cfg('graph_adres') ?: 'https://graph.facebook.com/v27.0')), '/'));
$jcTz = (string)jc_cfg('tijdzone', 'Europe/Amsterdam');
define('JC_TZ', in_array($jcTz, timezone_identifiers_list(), true) ? $jcTz : 'Europe/Amsterdam');
date_default_timezone_set(JC_TZ);

/** Het begin van de korte links, bijvoorbeeld https://jouwpraktijk.nl/chat/r */
function jc_korte_link_basis() { global $JC_ADRES; return rtrim((string)(jc_cfg('korte_link_adres') ?: $JC_ADRES . '/r'), '/'); }
/** De homepage van de site (voor een korte link die niet bestaat). */
function jc_homepage() {
  global $JC_ADRES; $h = (string)jc_cfg('website');
  if ($h !== '' && preg_match('#^https?://#', $h)) return $h;
  return preg_match('#^(https?://[^/]+)#', $JC_ADRES, $m) ? $m[1] . '/' : '/';
}
function jc_merk() {
  $naam = mb_substr((string)(jc_cfg('naam') ?: 'Jensiechat'), 0, 40);
  $logo = (string)jc_cfg('logo');
  if ($logo !== '' && (!preg_match('#^[A-Za-z0-9._\-/]+\.(png|jpe?g|svg|webp)$#i', $logo) || strpos($logo, '..') !== false || $logo[0] === '/')) $logo = '';
  if ($logo !== '' && !is_file(__DIR__ . '/' . $logo)) $logo = '';
  return ['naam' => $naam, 'logo' => $logo, 'kleur' => jc_kleur()];
}
function jc_kleur() { $k = (string)jc_cfg('kleur'); return preg_match('/^#[0-9a-fA-F]{6}$/', $k) ? strtolower($k) : '#2e6be6'; }

/** Stopt met een duidelijke melding in plaats van een witte pagina. De vorm hangt af van wie het vroeg. */
function jc_stop($melding, $code = 503) {
  $soort = defined('JC_APP') ? JC_APP : '';
  $tt = fn($k) => empty($GLOBALS['JC_C']) ? jc_t2($k) : jc_t($k); // zonder leesbare config.php: beide talen
  if (PHP_SAPI === 'cli') { fwrite(STDERR, "Jensiechat: $melding\n"); exit(1); }
  if (!headers_sent()) http_response_code($code);
  if ($soort === 'api' || $soort === 'cron') { if (!headers_sent()) header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok' => false, 'fout' => $melding, 'instellen' => true], JSON_UNESCAPED_UNICODE); exit; }
  if ($soort === 'html') {
    if (!headers_sent()) header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="' . jc_taal() . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Jensiechat</title>'
      . '<link rel="stylesheet" href="jensiechat.css?v=' . JC_VERSIE . '"><link rel="stylesheet" href="thema.php?v=' . JC_VERSIE . '"></head><body><main class="slot"><div class="slot-kaart">'
      . '<h1>' . htmlspecialchars($tt('stop.titel'), ENT_QUOTES, 'UTF-8') . '</h1><p class="fout">' . htmlspecialchars($melding, ENT_QUOTES, 'UTF-8') . '</p><p>' . htmlspecialchars($tt('stop.uitleg'), ENT_QUOTES, 'UTF-8') . '</p></div></main></body></html>';
    exit;
  }
  if (!headers_sent()) header('Content-Type: text/plain; charset=utf-8');
  echo $soort === 'link' ? $tt('stop.link') : $tt('stop.niet_ingesteld'); exit;
}
// Een onverwachte fout geeft een melding en geen witte pagina. De details gaan naar het logboek, niet naar de bezoeker.
set_exception_handler(function ($e) {
  // Zonder instellingenbestand niet loggen: dat zou een lege database aanmaken, en die houdt het invulscherm dicht.
  if ($GLOBALS['JC_CONFIG_PAD'] !== '') jc_log_veilig('fout', jc_t('log.onverwacht') . ' ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
  jc_stop($e instanceof JcFout ? $e->getMessage() : jc_t('stop.onverwacht'), 500);
});
class JcFout extends RuntimeException {}

// ── Opslag ───────────────────────────────────────────────────────────────────
/**
 * De datamap. Standaard een map boven de webmap (jensiechat-data), zodat niemand de database via internet kan opvragen.
 * Lukt dat niet, dan de map data/ hier, afgeschermd met .htaccess; Gezondheid waarschuwt dan.
 */
function jc_dir() {
  static $dir = null; if ($dir !== null) return $dir;
  $k = [];
  if ((string)jc_cfg('datamap') !== '') $k[] = rtrim((string)jc_cfg('datamap'), '/');
  elseif (jc_webroot() !== '') $k[] = dirname(jc_webroot()) . '/jensiechat-data';
  $k[] = __DIR__ . '/data';
  foreach ($k as $pad) {
    if (!is_dir($pad)) @mkdir($pad, 0700, true);
    if (is_dir($pad) && is_writable($pad)) { $dir = $pad; break; }
  }
  if ($dir === null) { $dir = ''; return ''; }
  if ($dir === __DIR__ . '/data' && !is_file($dir . '/.htaccess')) @file_put_contents($dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
  return $dir;
}
/** Staat de database binnen de webmap? Dan is hij misschien via internet op te vragen. */
function jc_data_in_webroot() {
  $d = realpath(jc_dir()) ?: jc_dir(); if ($d === '') return false;
  $w = jc_webroot() !== '' ? (realpath(jc_webroot()) ?: jc_webroot()) : (realpath(__DIR__) ?: __DIR__);
  return strpos($d . '/', rtrim($w, '/') . '/') === 0;
}
/**
 * De cronjob, de webhook en het dashboard schrijven soms op hetzelfde moment. SQLite meldt dan af en toe meteen
 * "database is locked", ook met een wachttijd ingesteld. Daarom probeert elke opdracht het bij die melding opnieuw.
 */
class JcStmt extends PDOStatement {
  protected function __construct() {}
  // After one row or one value, close the statement right away (1.0.2). A statement left open holds a read snapshot; if another
  // process (the second cron job, the webhook) writes meanwhile, every write from this process fails at once with "database is locked",
  // and retrying does not help while the statement stays open. Rows are always read with foreach or fetchAll in this code, never with
  // repeated fetch(), so those keep working.
  public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed {
    $r = parent::fetch($mode, $cursorOrientation, $cursorOffset); $this->closeCursor(); return $r;
  }
  public function fetchColumn(int $column = 0): mixed { $r = parent::fetchColumn($column); $this->closeCursor(); return $r; }
  public function execute(?array $params = null): bool {
    // Voor een nieuwe poging eerst het statement terugzetten (closeCursor), anders geeft SQLite 'bad parameter or other API misuse'.
    for ($i = 0; ; $i++) {
      try { return parent::execute($params); }
      catch (PDOException $e) {
        if ($i >= 12 || stripos($e->getMessage(), 'locked') === false) throw $e;
        try { $this->closeCursor(); } catch (Throwable $e2) {}
        usleep(120000 * ($i + 1));
      }
    }
  }
}
class JcPdo extends PDO {
  public function exec(string $sql): int|false {
    for ($i = 0; ; $i++) { try { return parent::exec($sql); } catch (PDOException $e) { if ($i >= 12 || stripos($e->getMessage(), 'locked') === false) throw $e; usleep(120000 * ($i + 1)); } }
  }
  public function query(string $sql, ?int $mode = null, mixed ...$args): PDOStatement|false {
    for ($i = 0; ; $i++) { try { return $mode === null ? parent::query($sql) : parent::query($sql, $mode, ...$args); } catch (PDOException $e) { if ($i >= 12 || stripos($e->getMessage(), 'locked') === false) throw $e; usleep(120000 * ($i + 1)); } }
  }
}
function jc_db() {
  static $db = null; if ($db) return $db;
  if (!extension_loaded('pdo_sqlite')) throw new JcFout(jc_t('fout.sqlite'));
  if (jc_dir() === '') throw new JcFout(jc_t('fout.opslag'));
  $db = new JcPdo('sqlite:' . jc_dir() . '/jensiechat.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 10]);
  $db->setAttribute(PDO::ATTR_STATEMENT_CLASS, [JcStmt::class]);
  $db->exec('PRAGMA busy_timeout=10000'); $db->exec('PRAGMA journal_mode=WAL'); $db->exec('PRAGMA synchronous=NORMAL'); $db->exec('PRAGMA foreign_keys=ON');
  $db->exec('PRAGMA secure_delete=ON'); // gewiste gegevens worden in het bestand overschreven, niet alleen vrijgegeven
  $db->exec('CREATE TABLE IF NOT EXISTS instellingen (k TEXT PRIMARY KEY, v TEXT);
    CREATE TABLE IF NOT EXISTS automations (id INTEGER PRIMARY KEY, naam TEXT NOT NULL, trefwoorden TEXT NOT NULL, media_id TEXT NOT NULL DEFAULT "",
      dm_tekst TEXT NOT NULL, link TEXT NOT NULL DEFAULT "", link2 TEXT NOT NULL DEFAULT "", slug TEXT NOT NULL UNIQUE, antwoorden TEXT NOT NULL DEFAULT "[]",
      aan INTEGER NOT NULL DEFAULT 0, test INTEGER NOT NULL DEFAULT 0, testers TEXT NOT NULL DEFAULT "[]", aan_sinds INTEGER NOT NULL DEFAULT 0,
      stijl TEXT NOT NULL DEFAULT "tekst", knop TEXT NOT NULL DEFAULT "", knop2 TEXT NOT NULL DEFAULT "", verwijderd INTEGER NOT NULL DEFAULT 0,
      gemaakt INTEGER NOT NULL, gewijzigd INTEGER NOT NULL);
    CREATE TABLE IF NOT EXISTS gezien (comment_id TEXT PRIMARY KEY, ts INTEGER NOT NULL);
    CREATE TABLE IF NOT EXISTS reacties (id INTEGER PRIMARY KEY, comment_id TEXT NOT NULL UNIQUE, automation_id INTEGER, media_id TEXT NOT NULL DEFAULT "",
      ig_user_id TEXT NOT NULL DEFAULT "", username TEXT NOT NULL DEFAULT "", tekst TEXT NOT NULL DEFAULT "", ts INTEGER NOT NULL, ontvangen INTEGER NOT NULL,
      bron TEXT NOT NULL, code TEXT NOT NULL UNIQUE, test INTEGER NOT NULL DEFAULT 0,
      dm_status TEXT NOT NULL, dm_fout TEXT NOT NULL DEFAULT "", dm_ts INTEGER, bezig_sinds INTEGER, pogingen INTEGER NOT NULL DEFAULT 0, vorm TEXT NOT NULL DEFAULT "",
      antwoord_status TEXT NOT NULL DEFAULT "wacht", antwoord_tekst TEXT NOT NULL DEFAULT "", antwoord_fout TEXT NOT NULL DEFAULT "",
      kliks INTEGER NOT NULL DEFAULT 0, klik_ts INTEGER, gewist INTEGER NOT NULL DEFAULT 0);
    CREATE INDEX IF NOT EXISTS reacties_ts ON reacties(ts);
    CREATE INDEX IF NOT EXISTS reacties_status ON reacties(dm_status);
    CREATE TABLE IF NOT EXISTS klikken (id INTEGER PRIMARY KEY, automation_id INTEGER, reactie_id INTEGER, welke INTEGER NOT NULL DEFAULT 1, ts INTEGER NOT NULL);
    CREATE TABLE IF NOT EXISTS media (id TEXT PRIMARY KEY, caption TEXT NOT NULL DEFAULT "", permalink TEXT NOT NULL DEFAULT "", thumb TEXT NOT NULL DEFAULT "",
      soort TEXT NOT NULL DEFAULT "", ts INTEGER NOT NULL DEFAULT 0, comments_count INTEGER NOT NULL DEFAULT -1, bijgewerkt INTEGER NOT NULL DEFAULT 0);
    CREATE TABLE IF NOT EXISTS logboek (id INTEGER PRIMARY KEY, ts INTEGER NOT NULL, soort TEXT NOT NULL, tekst TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS labels (persoon TEXT NOT NULL, label TEXT NOT NULL, ts INTEGER NOT NULL, PRIMARY KEY (persoon, label));');
  // Schemaversie, voor latere updates (een nieuwe versie voegt hier kolommen toe als user_version lager is).
  if ((int)$db->query('PRAGMA user_version')->fetchColumn() < 1) $db->exec('PRAGMA user_version=1');
  return $db;
}
function jc_get($k, $def = null) { $s = jc_db()->prepare('SELECT v FROM instellingen WHERE k=?'); $s->execute([$k]); $v = $s->fetchColumn(); return $v === false ? $def : $v; }
function jc_set($k, $v) { jc_db()->prepare('INSERT INTO instellingen (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v=excluded.v')->execute([$k, (string)$v]); }
/** Loggen dat zelf nooit een fout mag geven (voor in een catch). */
function jc_log_veilig($soort, $tekst) { try { jc_log($soort, $tekst); } catch (Throwable $e) {} }
function jc_log($soort, $tekst) {
  jc_db()->prepare('INSERT INTO logboek (ts, soort, tekst) VALUES (?,?,?)')->execute([time(), $soort, mb_substr($tekst, 0, 500)]);
}
/** Een waarschuwing in het logboek, hooguit een keer per $elke seconden per soort: een storing mag het logboek niet vol zetten. */
function jc_melding($soort, $tekst, $elke = 900) {
  if ((int)jc_get('melding_' . $soort, 0) > time() - $elke) return false;
  jc_set('melding_' . $soort, time()); jc_log('fout', $tekst); return true;
}

// ── Meta Graph API ───────────────────────────────────────────────────────────
/** Een aanroep. Geeft ['ok'=>bool, 'status'=>int, 'data'=>array, 'code'=>int, 'fout'=>string]. $json = body als JSON (voor /messages). */
function jc_graph($methode, $pad, $params = [], $json = null, $opt = []) {
  global $JC_TOKEN, $JC_APP_SECRET;
  if ($JC_TOKEN === '') return ['ok' => false, 'status' => 0, 'data' => [], 'code' => -1, 'fout' => jc_t('fout.geen_token')];
  if (!function_exists('curl_init')) return ['ok' => false, 'status' => 0, 'data' => [], 'code' => -1, 'fout' => jc_t('fout.curl')];
  $auth = ['access_token' => $JC_TOKEN];
  // Geeft de aanroeper zelf een token mee (het app-token voor debug_token), dan hoort daar geen proof van het gewone token bij.
  if ($JC_APP_SECRET !== '' && !isset($params['access_token'])) $auth['appsecret_proof'] = hash_hmac('sha256', $JC_TOKEN, $JC_APP_SECRET);
  $url = JC_GRAPH . '/' . ltrim($pad, '/');
  $ch = curl_init();
  $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $opt['timeout'] ?? 15, CURLOPT_CONNECTTIMEOUT => min(6, $opt['timeout'] ?? 6)];
  if ($methode === 'GET') { $opts[CURLOPT_URL] = $url . '?' . http_build_query($params + $auth); }
  else {
    $opts[CURLOPT_POST] = true;
    if ($json !== null) { $opts[CURLOPT_URL] = $url . '?' . http_build_query($auth); $opts[CURLOPT_POSTFIELDS] = json_encode($json, JSON_UNESCAPED_UNICODE); $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json']; }
    else { $opts[CURLOPT_URL] = $url; $opts[CURLOPT_POSTFIELDS] = http_build_query($params + $auth); }
  }
  // Meta meldt in de antwoordkoppen hoeveel van het budget verbruikt is; de controle remt daarop af.
  $opts[CURLOPT_HEADERFUNCTION] = function ($ch, $regel) {
    if (preg_match('/^x-(app|business-use-case)-usage:\s*(.+)$/i', trim($regel), $m) && preg_match_all('/"(?:call_count|total_time|total_cputime)":\s*(\d+)/', $m[2], $g)) {
      $p = max(array_map('intval', $g[1])); $GLOBALS['JC_GEBRUIK'] = max($GLOBALS['JC_GEBRUIK'] ?? 0, $p);
      // Het laatste percentage per soort kop apart, zodat te zien is welke limiet oploopt: die van de app of die van het account.
      $GLOBALS['JC_GEBRUIK_NU'][strtolower($m[1])] = $p;
    }
    return strlen($regel);
  };
  // HTTP/1.1: via HTTP/2 brak de verbinding met Meta soms af ("stream reset"). Lezen (GET) mag bij zo'n hapering een keer meteen opnieuw.
  $opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
  curl_setopt_array($ch, $opts);
  jc_tel_aanroep();
  $body = curl_exec($ch);
  if ($body === false && $methode === 'GET' && ($opt['herhaal'] ?? true)) { usleep(300000); $body = curl_exec($ch); }
  $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $cerr = curl_error($ch);
  if ($body === false) return ['ok' => false, 'status' => 0, 'data' => [], 'code' => -2, 'fout' => jc_t('fout.verbinding', ['fout' => $cerr])];
  $data = json_decode($body, true); if (!is_array($data)) $data = [];
  if (isset($data['error'])) {
    $e = $data['error'];
    return ['ok' => false, 'status' => $status, 'data' => $data, 'code' => (int)($e['code'] ?? 0), 'sub' => (int)($e['error_subcode'] ?? 0),
      'fout' => jc_uitleg($e) . trim(($e['message'] ?? jc_t('fout.onbekend')) . ' (code ' . ($e['code'] ?? '?') . (isset($e['error_subcode']) ? '/' . $e['error_subcode'] : '') . ')')];
  }
  return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'data' => $data, 'code' => 0, 'fout' => $status >= 300 ? "HTTP $status" : ''];
}
/** Een korte Nederlandse uitleg bij bekende fouten van Meta, voor in het dashboard. */
function jc_uitleg($e) {
  $c = (int)($e['code'] ?? 0); $s = (int)($e['error_subcode'] ?? 0); $m = strtolower((string)($e['message'] ?? ''));
  if ($s === 2534022 || strpos($m, 'outside of allowed window') !== false) return jc_t('meta.venster') . ' ';
  if ($c === 551 || $s === 2534014 || strpos($m, 'not available') !== false) return jc_t('meta.niet_beschikbaar') . ' ';
  if ($c === 190) return jc_t('meta.token') . ' ';
  if ($c === 10 || $c === 200 || $c === 3) return jc_t('meta.recht') . ' ';
  if (in_array($c, [4, 17, 32, 613], true)) return jc_t('meta.limiet') . ' ';
  return '';
}
/** Telt de aanroepen naar Meta per klokuur. Meta begrenst een app zonder beoordeling op ongeveer 200 per uur; daarboven weigert het ook het versturen. */
function jc_tel_aanroep() {
  try { $k = 'aanroepen_' . date('YmdH'); jc_db()->prepare("INSERT INTO instellingen (k, v) VALUES (?, '1') ON CONFLICT(k) DO UPDATE SET v = CAST(v AS INTEGER) + 1")->execute([$k]); } catch (Throwable $e) {}
}
/** Aanroepen in het lopende uur plus het deel van het vorige uur dat nog binnen de laatste 60 minuten valt (geschat). */
function jc_aanroepen_uur() {
  $nu = (int)jc_get('aanroepen_' . date('YmdH'), 0); $vorig = (int)jc_get('aanroepen_' . date('YmdH', time() - 3600), 0);
  return $nu + (int)round($vorig * (1 - ((int)date('i')) / 60));
}
/** Is het leesbudget voor dit uur op? Dan wordt er alleen nog verstuurd en niet meer zelf gekeken. */
function jc_budget_op($marge = 0) { return jc_aanroepen_uur() >= JC_BUDGET_UUR + $marge || jc_gebruik() >= 75; }
/** Het verbruik dat Meta zelf meldt, in procenten van de limiet (het hoogste van de laatste antwoorden; 0 als Meta niets meldde). */
function jc_gebruik() { $n = $GLOBALS['JC_GEBRUIK_NU'] ?? []; return $n ? max($n) : 0; }
/** Is het druk? Dan gaan de privéberichten voor en volgt het publieke antwoord later. */
function jc_druk() {
  if (jc_gebruik() >= 50) return true;
  return (int)jc_db()->query("SELECT COUNT(*) FROM reacties WHERE dm_status='wacht'")->fetchColumn() > 10;
}
/** Tijdelijke storing (geen verbinding, Meta even stuk): later vanzelf opnieuw proberen. */
function jc_is_tijdelijk($r) { return $r['code'] === -2 || $r['status'] >= 500 || in_array($r['code'], [1, 2], true); }
function jc_lees_pauze() { jc_set('lees_pauze_tot', time() + JC_PAUZE_LIMIET); }
/** Limietfouten van Meta: dan even pauzeren en het later opnieuw proberen. */
function jc_is_limiet($r) { return in_array($r['code'], [4, 17, 32, 613, 80002, 80006], true) || $r['status'] === 429; }
function jc_pauze_tot() { return (int)jc_get('pauze_tot', 0); }

/** Het Instagram-account-ID: uit config.php, anders eenmalig opgezocht met het token en onthouden. */
function jc_ig_id() {
  global $JC_IG_ID;
  if ($JC_IG_ID !== '') return $JC_IG_ID;
  $v = jc_get('ig_id', ''); if ($v !== '') return $v;
  if ((int)jc_get('ig_id_gezocht', 0) > time() - 300) return '';
  jc_set('ig_id_gezocht', time());
  // Unified login: het token is van een systeemgebruiker; die ziet zijn Instagram-accounts via assigned_instagram_accounts.
  $r = jc_graph('GET', 'me/assigned_instagram_accounts', ['fields' => 'id,username']);
  foreach ($r['data']['data'] ?? [] as $acc) if (!empty($acc['id'])) { jc_set('ig_id', $acc['id']); jc_set('ig_username', $acc['username'] ?? ''); return $acc['id']; }
  $r = jc_graph('GET', 'me/accounts', ['fields' => 'id,name,instagram_business_account{id,username}']);
  foreach ($r['data']['data'] ?? [] as $p) if (!empty($p['instagram_business_account']['id'])) {
    jc_set('ig_id', $p['instagram_business_account']['id']); jc_set('ig_username', $p['instagram_business_account']['username'] ?? ''); jc_set('page_id', $p['id']);
    return $p['instagram_business_account']['id'];
  }
  jc_log('fout', jc_t('log.ig_niet_gevonden', ['fout' => $r['fout'] ?: '-']));
  return '';
}

// ── Automations ──────────────────────────────────────────────────────────────
function jc_automation_rij($r) {
  foreach (['id', 'verwijderd', 'aan', 'test', 'aan_sinds'] as $k) $r[$k] = (int)($r[$k] ?? 0);
  $r['trefwoorden'] = json_decode($r['trefwoorden'], true) ?: []; $r['antwoorden'] = json_decode($r['antwoorden'], true) ?: []; $r['testers'] = json_decode($r['testers'], true) ?: [];
  return $r;
}
function jc_automations() { return array_map('jc_automation_rij', jc_db()->query('SELECT * FROM automations WHERE verwijderd=0 ORDER BY id')->fetchAll()); }
function jc_automation($id) { $s = jc_db()->prepare('SELECT * FROM automations WHERE id=?'); $s->execute([$id]); $r = $s->fetch(); return $r ? jc_automation_rij($r) : null; }
function jc_automation_slug($slug) { $s = jc_db()->prepare('SELECT * FROM automations WHERE slug=?'); $s->execute([$slug]); $r = $s->fetch(); return $r ? jc_automation_rij($r) : null; }

/** Tekst gelijkmaken: kleine letters, zonder accenten. */
function jc_norm($t) {
  $t = mb_strtolower((string)$t, 'UTF-8');
  if (class_exists('Normalizer')) { $t = Normalizer::normalize($t, Normalizer::FORM_D); $t = preg_replace('/\p{Mn}+/u', '', $t); }
  else { $t = strtr($t, ['á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i', 'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u', 'ñ' => 'n', 'ç' => 'c']); }
  return $t;
}
/** Bevat de reactie het trefwoord als los woord? "GIDS", "gids!", "Gids graag" ja; "gidsen" nee. Een trefwoord van meer woorden mag ook. */
function jc_bevat($tekst, $woord) {
  $w = trim(jc_norm($woord)); if ($w === '') return false;
  return (bool)preg_match('/(?<![\p{L}\p{N}])' . preg_quote($w, '/') . '(?![\p{L}\p{N}])/u', jc_norm($tekst));
}
/** Welke automation hoort bij deze reactie? $alleenAan: alleen automations die aan staan (of in testmodus). */
function jc_zoek_automation($tekst, $mediaId, $alleenAan = true) {
  // Een automation voor een post gaat voor op een algemene met hetzelfde trefwoord.
  $alle = jc_automations(); usort($alle, fn($x, $y) => [$x['media_id'] === '', $x['id']] <=> [$y['media_id'] === '', $y['id']]);
  foreach ($alle as $a) {
    if ($alleenAan && !$a['aan']) continue;
    // "Probeer een reactie" (niet $alleenAan, geen post gekozen) kijkt naar alle automations, ook die aan een post hangen.
    if ($a['media_id'] !== '' && $a['media_id'] !== (string)$mediaId && ($alleenAan || (string)$mediaId !== '')) continue;
    foreach ($a['trefwoorden'] as $w) if (jc_bevat($tekst, $w)) return $a;
  }
  return null;
}
/**
 * "De volgende post of reel": een automation met media_id 'volgende' hecht zich aan de eerste post die na het aanzetten is geplaatst.
 * $mediaId = de post waar net een reactie op kwam; kennen we die nog niet, dan halen we eerst de nieuwste posts op.
 */
function jc_bind_volgende($mediaId = '') {
  $db = jc_db();
  $open = $db->query("SELECT id, naam, aan_sinds FROM automations WHERE aan=1 AND verwijderd=0 AND media_id='volgende'")->fetchAll(); if (!$open) return;
  if ($mediaId !== '' && $mediaId !== null) { $s = $db->prepare('SELECT 1 FROM media WHERE id=?'); $s->execute([$mediaId]); if (!$s->fetchColumn()) jc_media_ophalen(6); }
  foreach ($open as $a) {
    $s = $db->prepare('SELECT id FROM media WHERE ts >= ? ORDER BY ts ASC LIMIT 1'); $s->execute([(int)$a['aan_sinds']]); $m = $s->fetchColumn();
    if ($m) { $db->prepare('UPDATE automations SET media_id=?, gewijzigd=? WHERE id=?')->execute([$m, time(), $a['id']]); jc_log('wijziging', jc_t('log.volgende_gebonden', ['naam' => $a['naam'], 'post' => jc_media_kort($m)])); }
  }
}
/** De korte link voor een persoon. Zonder mooie links (geen .htaccess op de server) wordt het r/?s=...&c=... */
function jc_redirect_url($a, $code, $welke = 1) {
  if (jc_cfg('mooie_links', true) === false) return jc_korte_link_basis() . '/?s=' . $a['slug'] . '&c=' . $code . ($welke === 2 ? '&w=2' : '');
  return jc_korte_link_basis() . '/' . $a['slug'] . '/' . $code . ($welke === 2 ? '/2' : '');
}
/** De tekst van het privébericht zoals hij verstuurd wordt. */
function jc_dm_tekst($a, $code, $username = '') {
  $t = str_replace(['{link}', '{link2}', '{naam}'], [jc_redirect_url($a, $code, 1), jc_redirect_url($a, $code, 2), $username !== '' ? '@' . $username : ''], $a['dm_tekst']);
  return trim(preg_replace("/[ \t]+\n/", "\n", $t));
}
/** De knoppen van een automation in knopvorm: [['title' => ..., 'url' => ...], ...]. Leeg bij de tekstvorm. Hooguit twee. */
function jc_knoppen($a, $code) {
  if (($a['stijl'] ?? 'tekst') !== 'knop' || $a['link'] === '' || trim($a['knop'] ?? '') === '') return [];
  $k = [['type' => 'web_url', 'title' => trim($a['knop']), 'url' => jc_redirect_url($a, $code, 1)]];
  if ($a['link2'] !== '' && trim($a['knop2'] ?? '') !== '') $k[] = ['type' => 'web_url', 'title' => trim($a['knop2']), 'url' => jc_redirect_url($a, $code, 2)];
  return $k;
}
/** Dezelfde inhoud als gewone tekst, voor als Meta de knoppen niet toestaat: de links staan er dan onder. */
function jc_knop_als_tekst($tekst, $knoppen) {
  foreach ($knoppen as $k) $tekst .= "\n\n" . $k['title'] . ': ' . $k['url'];
  return $tekst;
}
function jc_code() {
  $abc = 'abcdefghjkmnpqrstuvwxyz23456789'; $c = '';
  for ($i = 0; $i < 7; $i++) $c .= $abc[random_int(0, strlen($abc) - 1)];
  return $c;
}

// ── Een reactie afhandelen ───────────────────────────────────────────────────
/**
 * $c = ['id', 'text', 'media_id', 'from_id', 'username', 'ts', 'parent_id']. $bron = webhook of controle.
 * Geeft het nieuwe reactie-ID terug als er iets te versturen is, anders null.
 */
function jc_nieuwe_reactie($c, $bron) {
  $db = jc_db(); $cid = (string)($c['id'] ?? ''); if ($cid === '') return null;
  // Alles wat het netwerk nodig heeft eerst, buiten de transactie.
  $ig = jc_ig_id(); jc_bind_volgende($c['media_id'] ?? '');
  // 'Gezien' en de reactie zelf in een transactie: gaat er halverwege iets mis, dan is de reactie niet 'gezien' en komt hij terug.
  $db->beginTransaction();
  try { $uit = jc_nieuwe_reactie_tx($db, $c, $cid, $ig, $bron); $db->commit(); return $uit; }
  catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
}
function jc_nieuwe_reactie_tx($db, $c, $cid, $ig, $bron) {
  // Een keer per reactie: de eerste weg die hem ziet, wint.
  $s = $db->prepare('INSERT OR IGNORE INTO gezien (comment_id, ts) VALUES (?, ?)'); $s->execute([$cid, time()]);
  if ($s->rowCount() === 0) return null;
  $eigen = strtolower((string)jc_get('ig_username', ''));
  if (($ig !== '' && (string)($c['from_id'] ?? '') === $ig) || ($eigen !== '' && strtolower((string)($c['username'] ?? '')) === $eigen)) return null; // eigen reacties en eigen antwoorden
  $a = jc_zoek_automation($c['text'] ?? '', $c['media_id'] ?? ''); if (!$a) return null;
  $ts = (int)($c['ts'] ?? time()); $user = (string)($c['username'] ?? '');
  // Reacties van voor het aanzetten laten we helemaal liggen (die zijn al met de hand of door een andere dienst beantwoord); ook niet bewaren.
  if ($ts < $a['aan_sinds'] - 60) return null;
  $status = 'wacht'; $fout = '';
  if ($a['test'] && !in_array(strtolower($user), array_map('strtolower', $a['testers']), true)) { $status = 'overgeslagen'; $fout = jc_t('reden.testmodus'); }
  elseif ($ts < time() - JC_PRIVATE_REPLY_DAGEN * 86400) { $status = 'overgeslagen'; $fout = jc_t('reden.te_oud'); }
  elseif (!$a['test'] && ($c['from_id'] ?? '') !== '' && !in_array(strtolower($user), array_map('strtolower', $a['testers']), true)) {
    // Accounts in de testlijst mogen altijd opnieuw testen; alle anderen hooguit één keer per etmaal.
    // Wie twee keer hetzelfde woord typt, krijgt niet twee keer hetzelfde bericht.
    $s = $db->prepare("SELECT 1 FROM reacties WHERE automation_id=? AND ig_user_id=? AND dm_status IN ('verstuurd','wacht','bezig') AND ts > ? LIMIT 1");
    $s->execute([$a['id'], (string)$c['from_id'], time() - JC_ZELFDE_PERSOON_UREN * 3600]);
    if ($s->fetchColumn()) { $status = 'overgeslagen'; $fout = jc_t('reden.al_gehad', ['uur' => JC_ZELFDE_PERSOON_UREN]); }
  }
  for ($i = 0; $i < 5; $i++) {
    try {
      $db->prepare('INSERT INTO reacties (comment_id, automation_id, media_id, ig_user_id, username, tekst, ts, ontvangen, bron, code, test, dm_status, dm_fout, antwoord_status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$cid, $a['id'], (string)($c['media_id'] ?? ''), (string)($c['from_id'] ?? ''), $user,
        mb_substr((string)($c['text'] ?? ''), 0, 500), $ts, time(), $bron, jc_code(), $a['test'], $status, $fout, $status === 'wacht' ? 'wacht' : 'overgeslagen']);
      $id = (int)$db->lastInsertId(); break;
    } catch (PDOException $e) { if (strpos($e->getMessage(), 'code') === false) throw $e; }
  }
  if (empty($id)) return null;
  jc_log('reactie', jc_t('log.reactie', ['wie' => $user !== '' ? "@$user" : jc_t('log.iemand'), 'naam' => $a['naam'], 'bron' => jc_t('bron.' . $bron)]) . ($status !== 'wacht' ? ': ' . jc_t('log.overgeslagen', ['reden' => $fout]) : ''));
  return $status === 'wacht' ? $id : null;
}

/** Verstuurt het privébericht en het publieke antwoord voor een reactie uit de wachtrij. */
function jc_verstuur($id) {
  $db = jc_db();
  if (jc_pauze_tot() > time()) return 'pauze';
  // Claimen: alleen wie de status van 'wacht' naar 'bezig' zet, verstuurt. Zo gaat er nooit twee keer een bericht uit.
  $s = $db->prepare("UPDATE reacties SET dm_status='bezig', bezig_sinds=? WHERE id=? AND dm_status='wacht'"); $s->execute([time(), $id]);
  if ($s->rowCount() !== 1) return 'al bezig';
  $r = $db->query('SELECT * FROM reacties WHERE id=' . (int)$id)->fetch();
  $a = jc_automation((int)$r['automation_id']); $ig = jc_ig_id();
  if (!$a || $a['verwijderd']) { $db->prepare("UPDATE reacties SET dm_status='fout', dm_fout=?, bezig_sinds=NULL WHERE id=?")->execute([jc_t('reden.auto_weg'), $id]); return 'fout'; }
  // Account nog onbekend (token ontbreekt of werkt even niet): laten wachten, niet afschrijven.
  if ($ig === '') { $db->prepare("UPDATE reacties SET dm_status='wacht', bezig_sinds=NULL WHERE id=?")->execute([$id]); return 'later'; }
  if ((int)$r['ts'] < time() - JC_PRIVATE_REPLY_DAGEN * 86400) { $db->prepare("UPDATE reacties SET dm_status='overgeslagen', dm_fout=?, antwoord_status='overgeslagen' WHERE id=?")->execute([jc_t('reden.te_oud'), $id]); return 'te oud'; }

  // 1. Het privébericht
  $tekst = jc_dm_tekst($a, $r['code'], $r['username']); $knoppen = jc_knoppen($a, $r['code']); $vorm = 'tekst'; $res = null;
  // Knopvorm: eerst als bericht met knoppen. Weigert Meta dat, dan gaat dezelfde inhoud als gewone tekst met de links eronder.
  // De weigering kost het ene privébericht niet. Na een weigering zes uur niet opnieuw proberen.
  if ($knoppen && (int)jc_get('knop_geweigerd', 0) < time() - 6 * 3600) {
    $res = jc_graph('POST', $ig . '/messages', [], ['recipient' => ['comment_id' => $r['comment_id']],
      'message' => ['attachment' => ['type' => 'template', 'payload' => ['template_type' => 'button', 'text' => $tekst, 'buttons' => $knoppen]]]]);
    if ($res['ok']) { $vorm = 'knop'; jc_set('knop_kan', 1); }
    elseif (!jc_is_limiet($res) && !jc_is_tijdelijk($res)) { jc_set('knop_geweigerd', time()); jc_set('knop_kan', 0); jc_log('fout', jc_t('log.knop_geweigerd', ['fout' => $res['fout']])); $res = null; }
  }
  if ($res === null) {
    if ($knoppen) $tekst = jc_knop_als_tekst($tekst, $knoppen);
    if (strlen($tekst) > JC_MAX_BYTES) { $db->prepare("UPDATE reacties SET dm_status='fout', dm_fout=?, bezig_sinds=NULL WHERE id=?")->execute([jc_t('reden.te_lang', ['n' => strlen($tekst)]), $id]); return 'fout'; }
    $res = jc_graph('POST', $ig . '/messages', [], ['recipient' => ['comment_id' => $r['comment_id']], 'message' => ['text' => $tekst]]);
  }
  if (!$res['ok'] && jc_is_limiet($res)) {
    jc_set('pauze_tot', time() + 180); jc_log('limiet', jc_t('log.limiet', ['fout' => $res['fout']]));
    $db->prepare("UPDATE reacties SET dm_status='wacht', bezig_sinds=NULL WHERE id=?")->execute([$id]); return 'pauze';
  }
  if (!$res['ok'] && jc_is_tijdelijk($res) && (int)$r['pogingen'] < 4) {
    // Storing: tot vijf keer vanzelf opnieuw. Kwam het bericht toch aan, dan weigert Meta de tweede (een per reactie), dus dubbel kan niet.
    $db->prepare("UPDATE reacties SET dm_status='wacht', bezig_sinds=NULL, pogingen=pogingen+1, dm_fout=? WHERE id=?")->execute([jc_t('reden.opnieuw', ['fout' => $res['fout']]), $id]); return 'later';
  }
  $dmOk = $res['ok'];
  $db->prepare('UPDATE reacties SET dm_status=?, dm_fout=?, dm_ts=?, vorm=?, bezig_sinds=NULL WHERE id=?')->execute([$dmOk ? 'verstuurd' : 'fout', $dmOk ? '' : $res['fout'], time(), $dmOk ? $vorm : '', $id]);
  if (!$dmOk) jc_log('fout', jc_t('log.dm_mislukt', ['wie' => '@' . $r['username'], 'fout' => $res['fout']]));

  // 2. Het publieke antwoord. Alleen als het bericht echt verstuurd is: anders staat er "kijk in je berichten" terwijl er niets kwam.
  //    Bij drukte gaat het privébericht voor: het antwoord blijft op 'wacht' en volgt zodra de rij leeg is (jc_antwoorden_inhalen).
  if ($dmOk && !jc_druk()) jc_plaats_antwoord($r, $a);
  return $dmOk ? 'verstuurd' : 'fout';
}
/** Plaatst het publieke antwoord onder een reactie (wisselend; nooit twee keer achter elkaar hetzelfde). */
function jc_plaats_antwoord($r, $a) {
  $db = jc_db(); $id = (int)$r['id'];
  $ant = array_values(array_filter($a['antwoorden'], fn($x) => trim($x) !== ''));
  if ($ant && $r['antwoord_status'] === 'wacht') {
    $vorige = (string)jc_get('vorig_antwoord_' . $a['id'], '');
    $keus = array_values(array_filter($ant, fn($x) => $x !== $vorige)) ?: $ant;
    $zin = $keus[random_int(0, count($keus) - 1)];
    $pr = jc_graph('POST', $r['comment_id'] . '/replies', ['message' => $zin]);
    if (!$pr['ok'] && jc_is_limiet($pr)) jc_set('pauze_tot', time() + JC_PAUZE_LIMIET);
    $db->prepare('UPDATE reacties SET antwoord_status=?, antwoord_tekst=?, antwoord_fout=? WHERE id=?')->execute([$pr['ok'] ? 'geplaatst' : 'fout', $zin, $pr['ok'] ? '' : $pr['fout'], $id]);
    if ($pr['ok']) { jc_set('vorig_antwoord_' . $a['id'], $zin); if (!empty($pr['data']['id'])) $db->prepare('INSERT OR IGNORE INTO gezien (comment_id, ts) VALUES (?, ?)')->execute([$pr['data']['id'], time()]); }
    else jc_log('fout', jc_t('log.antwoord_mislukt', ['wie' => '@' . $r['username'], 'fout' => $pr['fout']]));
    return $pr['ok'];
  } elseif (!$ant) $db->prepare("UPDATE reacties SET antwoord_status='geen' WHERE id=?")->execute([$id]);
  return true;
}
/** De wachtrij afwerken, met een pauze tussen berichten. Reacties die vastliepen terwijl ze 'bezig' waren, worden niet opnieuw verstuurd. */
function jc_wachtrij($max = 10, $totTijd = 0) {
  $db = jc_db(); $n = 0;
  $db->prepare("UPDATE reacties SET dm_status='fout', dm_fout=? WHERE dm_status='bezig' AND bezig_sinds < ?")->execute([jc_t('reden.onderbroken'), time() - 300]);
  while ($n < $max && jc_pauze_tot() <= time() && (!$totTijd || time() < $totTijd)) {
    $id = $db->query("SELECT id FROM reacties WHERE dm_status='wacht' ORDER BY ts LIMIT 1")->fetchColumn();
    if (!$id) break;
    $uit = jc_verstuur((int)$id); $n++;
    if ($uit === 'pauze' || $uit === 'later') break;
    usleep(JC_PAUZE_TUSSEN);
  }
  if ($n < $max) jc_antwoorden_inhalen($max - $n, $totTijd);
  return $n;
}
/** Publieke antwoorden die bij drukte zijn uitgesteld, alsnog plaatsen. Alleen als er geen privéberichten meer wachten. */
function jc_antwoorden_inhalen($max = 5, $totTijd = 0) {
  $db = jc_db(); $n = 0;
  if (jc_pauze_tot() > time() || jc_gebruik() >= 50 || (int)$db->query("SELECT COUNT(*) FROM reacties WHERE dm_status='wacht'")->fetchColumn() > 0) return 0;
  $s = $db->prepare("SELECT * FROM reacties WHERE dm_status='verstuurd' AND antwoord_status='wacht' AND dm_ts > ? ORDER BY dm_ts LIMIT " . (int)$max); $s->execute([time() - 6 * 3600]);
  foreach ($s->fetchAll() as $r) {
    if ($totTijd && time() >= $totTijd) break;
    $a = jc_automation((int)$r['automation_id']); if (!$a || $a['verwijderd']) { $db->prepare("UPDATE reacties SET antwoord_status='overgeslagen' WHERE id=?")->execute([$r['id']]); continue; }
    jc_plaats_antwoord($r, $a); $n++;
    if (jc_pauze_tot() > time()) break;
    usleep(JC_PAUZE_TUSSEN);
  }
  return $n;
}

// ── De eigen controle (de hoofdweg) ──────────────────────────────────────────
function jc_media_kort($mediaId, $max = 48) {
  if ($mediaId === '' || $mediaId === null) return '';
  $s = jc_db()->prepare('SELECT caption FROM media WHERE id=?'); $s->execute([$mediaId]); $c = (string)$s->fetchColumn();
  $c = trim(preg_replace('/\s+/', ' ', $c)); if ($c === '' || mb_strlen($c) <= $max) return $c;
  $k = mb_substr($c, 0, $max - 1); $sp = mb_strrpos($k, ' '); if ($sp > $max * .6) $k = mb_substr($k, 0, $sp);
  return rtrim($k, ' .,;:!?') . '…';
}
/** Haalt de nieuwste posts en reels op en bewaart ze. Geeft de lijst terug, of null bij een fout. */
function jc_media_ophalen($limit = 12, $licht = false) {
  $ig = jc_ig_id(); if ($ig === '') return [];
  // De snelle ronde vraagt alleen id, tijd en aantal reacties. Staat er een post bij die we nog niet kennen, dan volgt de volledige lijst.
  if ($licht) {
    $r = jc_graph('GET', $ig . '/media', ['fields' => 'id,timestamp,comments_count', 'limit' => $limit], null, ['timeout' => 8]);
    if ($r['ok']) {
      $bekend = array_flip(jc_db()->query('SELECT id FROM media')->fetchAll(PDO::FETCH_COLUMN)); $uit = []; $nieuw = false;
      foreach ($r['data']['data'] ?? [] as $m) { if (!isset($bekend[$m['id']])) { $nieuw = true; break; } $uit[] = ['id' => $m['id'], 'ts' => strtotime($m['timestamp'] ?? 'now'), 'count' => (int)($m['comments_count'] ?? -1)]; }
      if (!$nieuw) { jc_set('controle_fout', ''); jc_set('controle_mis_sinds', 0); return $uit; }
    }
  }
  $r = jc_graph('GET', $ig . '/media', ['fields' => 'id,caption,permalink,timestamp,comments_count,media_product_type,media_type,thumbnail_url,media_url', 'limit' => $limit]);
  if (!$r['ok']) {
    if (jc_is_limiet($r)) jc_lees_pauze(); jc_log('fout', jc_t('log.posts_mislukt', ['fout' => $r['fout']])); jc_set('controle_fout', $r['fout']);
    // Pas een extra melding als het een kwartier achter elkaar misgaat: een hapering is geen nieuws.
    if ((int)jc_get('controle_mis_sinds', 0) === 0) jc_set('controle_mis_sinds', time());
    elseif ((int)jc_get('controle_mis_sinds', 0) < time() - 900) jc_melding('controle', jc_t('log.controle_kwartier', ['fout' => mb_substr($r['fout'], 0, 160)]), 6 * 3600);
    return null;
  }
  jc_set('controle_mis_sinds', 0);
  jc_set('controle_fout', '');
  $db = jc_db(); $uit = [];
  foreach ($r['data']['data'] ?? [] as $m) {
    $thumb = $m['thumbnail_url'] ?? (($m['media_type'] ?? '') === 'IMAGE' ? ($m['media_url'] ?? '') : '');
    $soort = ($m['media_product_type'] ?? '') === 'REELS' ? 'reel' : 'post';
    $db->prepare('INSERT INTO media (id, caption, permalink, thumb, soort, ts, bijgewerkt) VALUES (?,?,?,?,?,?,?)
      ON CONFLICT(id) DO UPDATE SET caption=excluded.caption, permalink=excluded.permalink, thumb=excluded.thumb, soort=excluded.soort, ts=excluded.ts, bijgewerkt=excluded.bijgewerkt')
      ->execute([$m['id'], (string)($m['caption'] ?? ''), (string)($m['permalink'] ?? ''), $thumb, $soort, strtotime($m['timestamp'] ?? 'now'), time()]);
    $uit[] = ['id' => $m['id'], 'ts' => strtotime($m['timestamp'] ?? 'now'), 'count' => (int)($m['comments_count'] ?? -1)];
  }
  return $uit;
}
/**
 * Kijkt zelf naar nieuwe reacties. $alleenRecent: alleen posts van de laatste 48 uur (de snelle ronde).
 * Haalt de reacties van een post alleen op als het aantal reacties veranderde, zodat een stille ronde een aanroep kost.
 */
function jc_controle($alleenRecent = false) {
  // Een limiet op lezen pauzeert alleen het lezen; berichten uit de wachtrij gaan gewoon door.
  if (max(jc_pauze_tot(), (int)jc_get('lees_pauze_tot', 0)) > time() || jc_budget_op()) return 0;
  $actief = array_filter(jc_automations(), fn($a) => $a['aan']);
  if (!$actief) {
    // Niets aan: geen reacties lezen, maar wel eens per tien minuten de posts ophalen, zodat je ze in het dashboard kunt kiezen.
    if (!$alleenRecent && (int)jc_get('laatste_media', 0) < time() - 600) { jc_set('laatste_media', time()); jc_media_ophalen(12); }
    jc_set('laatste_controle', time()); return 0;
  }
  // Eens per uur alles lezen, ook als het aantal reacties gelijk bleef (een weg en een erbij geeft hetzelfde aantal).
  // Niet vaker: twaalf posts elke tien minuten kost 72 aanroepen per uur en eet het uurbudget van Meta op.
  $volledig = !$alleenRecent && (int)jc_get('laatste_volledig', 0) < time() - 3600;
  $media = jc_media_ophalen($alleenRecent ? 6 : 12, $alleenRecent || (int)jc_get('laatste_zwaar', 0) > time() - 600); if ($media === null) return 0;
  if (!$alleenRecent && (int)jc_get('laatste_zwaar', 0) <= time() - 600) jc_set('laatste_zwaar', time());
  jc_bind_volgende(); $actief = array_filter(jc_automations(), fn($a) => $a['aan']);
  $db = jc_db(); $nieuw = 0; $grens = time() - JC_PRIVATE_REPLY_DAGEN * 86400;
  $nodig = []; foreach ($actief as $a) $nodig[$a['media_id']] = true;
  foreach ($media as $m) {
    if ($alleenRecent && $m['ts'] < time() - 48 * 3600) continue;
    if (empty($nodig['']) && empty($nodig[$m['id']])) continue;
    $s = $db->prepare('SELECT comments_count FROM media WHERE id=?'); $s->execute([$m['id']]); $oud = (int)$s->fetchColumn();
    if (!$volledig && $m['count'] >= 0 && $m['count'] === $oud) continue;
    $pad = $m['id'] . '/comments'; $params = ['fields' => 'id,text,timestamp,username,from,replies{id,text,timestamp,username,from}', 'limit' => 50]; $ok = true;
    for ($pagina = 0; $pagina < 4; $pagina++) {
      $r = jc_graph('GET', $pad, $params);
      if (!$r['ok']) { $ok = false; if (jc_is_limiet($r)) jc_lees_pauze(); jc_log('fout', jc_t('log.reacties_mislukt', ['fout' => $r['fout']])); break; }
      $oudste = PHP_INT_MAX;
      foreach ($r['data']['data'] ?? [] as $c) {
        $ts = strtotime($c['timestamp'] ?? 'now'); $oudste = min($oudste, $ts);
        // De reactie zelf en de antwoorden eronder (een trefwoord kan ook in een antwoord staan).
        foreach (array_merge([$c], $c['replies']['data'] ?? []) as $x) {
          $xts = strtotime($x['timestamp'] ?? 'now'); if ($xts < $grens) continue;
          try {
            if (jc_nieuwe_reactie(['id' => $x['id'], 'text' => $x['text'] ?? '', 'media_id' => $m['id'], 'from_id' => $x['from']['id'] ?? '',
              'username' => $x['username'] ?? ($x['from']['username'] ?? ''), 'ts' => $xts], 'controle')) $nieuw++;
          } catch (Throwable $e) { $ok = false; jc_log_veilig('fout', jc_t('log.vastleggen_mislukt', ['fout' => $e->getMessage()])); }
        }
      }
      $volgende = $r['data']['paging']['cursors']['after'] ?? ''; if ($volgende === '' || empty($r['data']['paging']['next']) || $oudste < $grens) break;
      $params['after'] = $volgende;
    }
    // Het nieuwe aantal pas onthouden als alles gelezen is; anders probeert de volgende ronde het opnieuw.
    if ($ok) $db->prepare('UPDATE media SET comments_count=? WHERE id=?')->execute([$m['count'], $m['id']]);
  }
  jc_set('laatste_controle', time()); if ($volledig) jc_set('laatste_volledig', time());
  jc_set('gebruik', (string)jc_gebruik()); jc_set('gebruik_soorten', json_encode($GLOBALS['JC_GEBRUIK_NU'] ?? []));
  return $nieuw;
}

// ── Webhook ──────────────────────────────────────────────────────────────────
/** Haalt de reacties uit een melding van Meta. Kent beide vormen: met 'changes' (Unified en Facebook-login) en zonder (Instagram-login). */
function jc_webhook_reacties($payload) {
  $uit = [];
  foreach ($payload['entry'] ?? [] as $e) {
    $wijz = $e['changes'] ?? [];
    if (!$wijz && isset($e['field'])) $wijz = [['field' => $e['field'], 'value' => $e['value'] ?? []]];
    foreach ($wijz as $w) {
      if (($w['field'] ?? '') !== 'comments') continue; $v = $w['value'] ?? [];
      $uit[] = ['id' => (string)($v['id'] ?? ($v['comment_id'] ?? '')), 'text' => (string)($v['text'] ?? ''), 'media_id' => (string)($v['media']['id'] ?? ''),
        'from_id' => (string)($v['from']['id'] ?? ''), 'username' => (string)($v['from']['username'] ?? ''), 'ts' => jc_sec($e['time'] ?? time()), 'parent_id' => (string)($v['parent_id'] ?? '')];
    }
  }
  return $uit;
}
/** Meta stuurt tijden soms in milliseconden. */
function jc_sec($t) { $t = (int)$t; return $t > 100000000000 ? intdiv($t, 1000) : $t; }

// ── Opruimen (90 dagen) ──────────────────────────────────────────────────────
function jc_opruimen() {
  $db = jc_db(); $grens = time() - JC_BEWAAR_DAGEN * 86400;
  // Ook het reactie-ID gaat weg: daarmee is de persoon bij Meta terug te vinden.
  $s = $db->prepare("UPDATE reacties SET ig_user_id='', username='', tekst='', antwoord_tekst='', dm_fout='', antwoord_fout='', comment_id='gewist-' || id, gewist=1 WHERE gewist=0 AND ts < ?"); $s->execute([$grens]);
  $db->prepare('DELETE FROM gezien WHERE ts < ?')->execute([time() - 30 * 86400]);
  $db->exec("DELETE FROM labels WHERE persoon NOT IN (SELECT lower(username) FROM reacties WHERE gewist=0 AND username<>'')");
  // Logregels noemen soms een gebruikersnaam: ook die na 90 dagen weg. Klikken bevatten geen persoonsgegevens.
  $db->prepare('DELETE FROM logboek WHERE ts < ?')->execute([$grens]);
  // Tellers van aanroepen per uur ouder dan twee dagen zijn niet meer nodig.
  $db->prepare("DELETE FROM instellingen WHERE k LIKE 'aanroepen_%' AND k < ?")->execute(['aanroepen_' . date('YmdH', time() - 2 * 86400)]);
  return $s->rowCount();
}
/** Alles van een persoon verwijderen (verzoek om verwijdering), op Instagram-naam. */
function jc_verwijder_persoon($username) {
  $u = ltrim(trim($username), '@'); if ($u === '') return 0;
  $db = jc_db();
  $db->prepare('DELETE FROM labels WHERE persoon=lower(?)')->execute([$u]);
  $s = $db->prepare("UPDATE reacties SET ig_user_id='', username='', tekst='', antwoord_tekst='', dm_fout='', antwoord_fout='', comment_id='gewist-' || id, gewist=1 WHERE gewist=0 AND lower(username)=lower(?)"); $s->execute([$u]);
  // Logregels met precies deze naam (niet @jan bij @jansen)
  $weg = $db->prepare('DELETE FROM logboek WHERE id=?');
  foreach ($db->query('SELECT id, tekst FROM logboek')->fetchAll() as $l) if (preg_match('/@' . preg_quote($u, '/') . '(?![A-Za-z0-9._])/i', $l['tekst'])) $weg->execute([$l['id']]);
  $n = $s->rowCount();
  // Ook uit het tijdelijke schrijfbestand van SQLite (WAL), zodat de naam niet in een bestand achterblijft.
  try { $db->exec('PRAGMA wal_checkpoint(TRUNCATE)'); } catch (Throwable $e) {}
  return $n;
}

// ── Gezondheid ───────────────────────────────────────────────────────────────
/** Controleert eens per uur (of op verzoek) of het token werkt, en onthoudt de uitkomst. */
function jc_token_check($nu = false) {
  global $JC_TOKEN, $JC_APP_ID, $JC_APP_SECRET;
  if (!$nu && (int)jc_get('token_check_ts', 0) > time() - 3600) return;
  jc_set('token_check_ts', time());
  if ($JC_TOKEN === '') { jc_set('token_ok', 0); jc_set('token_fout', jc_t('fout.geen_token')); return; }
  $ig = jc_ig_id();
  $r = $ig !== '' ? jc_graph('GET', $ig, ['fields' => 'id,username,followers_count']) : ['ok' => false, 'fout' => jc_t('fout.ig_niet_gevonden')];
  // Een storing in de verbinding zegt niets over het token: de vorige uitkomst laten staan.
  if (!$r['ok'] && isset($r['code']) && jc_is_tijdelijk($r)) return;
  jc_set('token_ok', $r['ok'] ? 1 : 0); jc_set('token_fout', $r['ok'] ? '' : $r['fout']);
  if ($r['ok']) { jc_set('ig_username', $r['data']['username'] ?? ''); jc_set('volgers', (string)($r['data']['followers_count'] ?? '')); }
  if ($JC_APP_ID !== '' && $JC_APP_SECRET !== '') {
    // debug_token wil een app-token (app-ID|app-geheim), niet het token van de systeemgebruiker.
    $d = jc_graph('GET', 'debug_token', ['input_token' => $JC_TOKEN, 'access_token' => $JC_APP_ID . '|' . $JC_APP_SECRET]);
    if ($d['ok']) { jc_set('token_verloopt', (string)($d['data']['data']['expires_at'] ?? 0)); jc_set('token_rechten', implode(',', $d['data']['data']['scopes'] ?? [])); }
  }
  if (!$r['ok'] && (int)jc_get('token_was_ok', 1)) jc_log('fout', jc_t('log.token_stuk', ['fout' => mb_substr((string)$r['fout'], 0, 160)]));
  jc_set('token_was_ok', $r['ok'] ? 1 : 0);
}
/** De regel voor de cronjob, zoals je hem bij je host invult. */
function jc_cron_regel() { return '/usr/bin/php ' . (realpath(__DIR__ . '/cron.php') ?: __DIR__ . '/cron.php'); }
function jc_gezondheid() {
  global $JC_TOKEN, $JC_APP_SECRET, $JC_VERIFY, $JC_ADRES, $JC_CONFIG_PAD;
  $db = jc_db(); $inWeb = jc_data_in_webroot();
  return [
    'versie' => JC_VERSIE,
    'token' => $JC_TOKEN !== '', 'geheim' => $JC_APP_SECRET !== '', 'verify' => $JC_VERIFY !== '',
    'app_id' => $GLOBALS['JC_APP_ID'] !== '', 'ig_ingevuld' => $GLOBALS['JC_IG_ID'] !== '',
    'config_schrijfbaar' => $JC_CONFIG_PAD !== '' && is_writable($JC_CONFIG_PAD) && is_writable(dirname($JC_CONFIG_PAD)),
    'token_ok' => (int)jc_get('token_ok', 0), 'token_fout' => (string)jc_get('token_fout', ''), 'token_check_ts' => (int)jc_get('token_check_ts', 0),
    'token_verloopt' => (int)jc_get('token_verloopt', 0), 'token_rechten' => (string)jc_get('token_rechten', ''),
    'ig_id' => jc_ig_id_bekend(), 'ig_username' => (string)jc_get('ig_username', ''), 'volgers' => (string)jc_get('volgers', ''),
    'laatste_webhook' => (int)jc_get('laatste_webhook', 0), 'laatste_controle' => (int)jc_get('laatste_controle', 0), 'laatste_cron' => (int)jc_get('laatste_cron', 0),
    'controle_fout' => (string)jc_get('controle_fout', ''), 'webhook_aangemeld' => (string)jc_get('webhook_aangemeld', ''),
    'webhook_adres' => $JC_ADRES . '/webhook.php', 'cron_regel' => jc_cron_regel(),
    'aanroepen_uur' => jc_aanroepen_uur(), 'budget_uur' => JC_BUDGET_UUR,
    'cron_botsingen' => (int)jc_get('cron_botsingen', 0), 'cron_botsing' => (int)jc_get('cron_botsing', 0),
    'knop_kan' => jc_get('knop_kan', null) === null ? null : (int)jc_get('knop_kan'), 'pauze_tot' => jc_pauze_tot(), 'lees_pauze_tot' => (int)jc_get('lees_pauze_tot', 0),
    'gebruik' => (int)jc_get('gebruik', 0), 'gebruik_soorten' => json_decode((string)jc_get('gebruik_soorten', '{}'), true) ?: [],
    'wachtrij' => (int)$db->query("SELECT COUNT(*) FROM reacties WHERE dm_status IN ('wacht','bezig')")->fetchColumn(),
    'fouten_24u' => (int)$db->query("SELECT COUNT(*) FROM logboek WHERE soort IN ('fout','limiet') AND ts > " . (time() - 86400))->fetchColumn(),
    'logboek' => $db->query('SELECT ts, soort, tekst FROM logboek ORDER BY id DESC LIMIT 12')->fetchAll(),
    // Waar staan de gegevens? Binnen de webmap kan de database op sommige servers via internet op te vragen zijn.
    'data_in_webroot' => $inWeb, 'data_proef' => $inWeb && jc_dir() === __DIR__ . '/data' ? 'data/jensiechat.db' : '',
    'config_in_map' => $JC_CONFIG_PAD === __DIR__ . '/config.php',
    'nu' => time(),
  ];
}
function jc_ig_id_bekend() { global $JC_IG_ID; return $JC_IG_ID !== '' ? $JC_IG_ID : (string)jc_get('ig_id', ''); }

// ── Mensen en labels ─────────────────────────────────────────────────────────
/**
 * Iedereen die met een trefwoord reageerde (en nog niet gewist is), met per persoon wat er gebeurde en de labels.
 * Automatische labels worden afgeleid, niet opgeslagen: het trefwoord en 'geklikt'. Eigen labels staan in de tabel labels.
 */
function jc_mensen() {
  $db = jc_db(); $woord = [];
  foreach ($db->query('SELECT id, naam, trefwoorden FROM automations') as $a) { $w = json_decode($a['trefwoorden'], true) ?: []; $woord[(int)$a['id']] = mb_strtoupper($w[0] ?? $a['naam']); }
  $eigen = []; foreach ($db->query('SELECT persoon, label FROM labels ORDER BY ts') as $l) $eigen[$l['persoon']][] = $l['label'];
  $uit = [];
  foreach ($db->query("SELECT id, username, automation_id, media_id, ts, dm_status, kliks, test FROM reacties WHERE gewist=0 AND username<>'' ORDER BY ts DESC LIMIT 5000") as $r) {
    $k = mb_strtolower($r['username']);
    if (!isset($uit[$k])) $uit[$k] = ['persoon' => $k, 'username' => $r['username'], 'laatst' => (int)$r['ts'], 'eerst' => (int)$r['ts'], 'reacties' => 0, 'berichten' => 0, 'kliks' => 0, 'test' => 1, 'auto' => [], 'eigen' => $eigen[$k] ?? []];
    $p = &$uit[$k]; $p['reacties']++; $p['eerst'] = min($p['eerst'], (int)$r['ts']); if ($r['dm_status'] === 'verstuurd') $p['berichten']++; $p['kliks'] += (int)$r['kliks']; if (!(int)$r['test']) $p['test'] = 0;
    $p['auto'][$woord[(int)$r['automation_id']] ?? 'TREFWOORD'] = true; if ((int)$r['kliks'] > 0) $p['auto']['geklikt'] = true;
    unset($p);
  }
  foreach ($uit as &$p) { $p['auto'] = array_keys($p['auto']); if ($p['test']) $p['auto'][] = 'test'; } unset($p);
  return array_values($uit);
}
/** Zet of verwijdert een eigen label bij iemand. Labels zijn kort, zonder komma. */
function jc_label_zet($username, $label, $aan) {
  $k = mb_strtolower(ltrim(trim($username), '@')); $l = trim(preg_replace('/[\s,]+/u', ' ', $label)); $l = mb_substr($l, 0, 30);
  if ($k === '' || $l === '') return false;
  if ($aan) jc_db()->prepare('INSERT OR IGNORE INTO labels (persoon, label, ts) VALUES (?,?,?)')->execute([$k, $l, time()]);
  else jc_db()->prepare('DELETE FROM labels WHERE persoon=? AND lower(label)=lower(?)')->execute([$k, $l]);
  return true;
}

// ── Antwoord terug aan de bezoeker meteen afsluiten, daarna verder werken ────
function jc_klaar_met_antwoord() {
  if (function_exists('litespeed_finish_request')) litespeed_finish_request();
  elseif (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
  else { @ob_end_flush(); @flush(); }
  ignore_user_abort(true); @set_time_limit(60);
}

// ── Klopt de instelling? Anders hier stoppen, met een duidelijke melding. ────
// Nog helemaal niets ingesteld en het invulscherm staat er: het dashboard stuurt daarheen (sinds 1.2).
/** Staat er al een database van Jensiechat (op een van de twee standaardplekken)? Dan is dit geen nieuwe installatie. */
function jc_db_bestaat() { foreach ([jc_webroot() !== '' ? dirname(jc_webroot()) . '/jensiechat-data' : '', __DIR__ . '/data'] as $m) if ($m !== '' && @is_file($m . '/jensiechat.db')) return true; return false; }
if (JC_APP === 'html' && $JC_CONFIG_PAD === '' && !jc_db_bestaat() && is_file(__DIR__ . '/instellen.php')) { header('Location: instellen.php', true, 302); exit; }
// Is alles ingesteld, dan mag een achtergebleven instellen.aan weg: anders blijft het invulscherm openzetbaar.
if (JC_APP === 'html' && $JC_CONFIG_PAD !== '' && is_file(__DIR__ . '/instellen.aan')) @unlink(__DIR__ . '/instellen.aan');
if ($JC_CONFIG_FOUT !== '' && JC_APP !== 'thema' && JC_APP !== 'instellen') jc_stop($JC_CONFIG_FOUT);
