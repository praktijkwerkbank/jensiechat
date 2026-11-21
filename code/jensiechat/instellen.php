<?php
// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// Het invulscherm (sinds 1.2): maakt het instellingenbestand zonder dat je config.php hoeft te bewerken.
// Veiligheid: werkt alleen zolang er nog GEEN instellingen zijn, en alleen in de eerste 24 uur na het uploaden
// (of zolang er een leeg bestand instellen.aan in deze map staat). Hier komen geen sleutels van Meta: die gaan
// in het dashboard, achter het wachtwoord. Na het opslaan gaat dit scherm vanzelf dicht.
define('JC_APP', 'instellen');
require __DIR__ . '/lib.php';
header('Content-Type: text/html; charset=utf-8'); header('X-Robots-Tag: noindex, nofollow'); header('Cache-Control: no-store');
header('X-Frame-Options: DENY'); header('Referrer-Policy: no-referrer'); header('X-Content-Type-Options: nosniff');

const IN_UREN = 24;
$talen = ['nl' => 'Nederlands', 'en' => 'English', 'es' => 'Español'];
$taal = is_string($_POST['taal'] ?? null) ? $_POST['taal'] : (is_string($_GET['lang'] ?? null) ? $_GET['lang'] : '');
if (!isset($talen[$taal])) {
  $taal = 'en';
  foreach (explode(',', strtolower((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''))) as $al) { $al = substr(trim($al), 0, 2); if (isset($talen[$al])) { $taal = $al; break; } }
}
$t = fn($k, $v = []) => htmlspecialchars(jc_t($k, $v, $taal), ENT_QUOTES, 'UTF-8');
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$aanBestand = __DIR__ . '/instellen.aan';
// Ingesteld = er is een instellingenbestand, of er staat al een database (dan is het instellingenbestand alleen even onvindbaar,
// bijvoorbeeld na een verhuizing; dan mag een bezoeker er niet een nieuw neerzetten).
$dbAl = jc_db_bestaat();
$ingesteld = $JC_CONFIG_PAD !== '' || $dbAl;
if ($ingesteld && is_file($aanBestand)) @unlink($aanBestand);
$vers = fn($f) => is_file($f) && (time() - (int)@filectime($f)) < IN_UREN * 3600;
$open = !$ingesteld && ($vers($aanBestand) || $vers(__FILE__));

// Het adres van deze map, zoals de bezoeker het ziet.
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
$pad = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
$adresNu = ($https ? 'https://' : 'http://') . $host . $pad;
$lokaal = fn($a) => (bool)preg_match('#^http://(localhost|127\.0\.0\.1)(:\d+)?(/|$)#', $a);

$kleuren = ['#2e6be6', '#0f8a6c', '#7a4fd6', '#d9480f', '#c2255c', '#11141c'];
$w = ['naam' => '', 'kleur' => $kleuren[0], 'adres' => $adresNu, 'tijdzone' => ''];
$fouten = []; $klaar = false; $downloadNodig = false;

// Tegen formulieren van andere sites: een eenmalige code in een cookie en in het formulier.
$nonce = is_string($_COOKIE['jc_in'] ?? null) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE['jc_in']) ? $_COOKIE['jc_in'] : bin2hex(random_bytes(16));
if ($open && !headers_sent()) setcookie('jc_in', $nonce, ['expires' => time() + 7200, 'path' => $pad === '' ? '/' : $pad, 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);

if ($open && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $s = fn($k) => is_string($_POST[$k] ?? null) ? trim($_POST[$k]) : '';
  // Het adres: een geplakt .../instellen.php of .../index.php eraf, geen ?vraag of #stuk.
  $adres = preg_replace('#/(instellen|index)\.php$#', '', rtrim(preg_replace('/[?#].*$/', '', $s('adres')), '/'));
  $w = ['naam' => mb_substr($s('naam'), 0, 40), 'kleur' => strtolower($s('kleur')), 'adres' => rtrim($adres, '/'), 'tijdzone' => $s('tijdzone')];
  $ww = is_string($_POST['wachtwoord'] ?? null) ? trim($_POST['wachtwoord']) : ''; $ww2 = is_string($_POST['wachtwoord2'] ?? null) ? trim($_POST['wachtwoord2']) : '';
  if (!hash_equals($nonce, $s('nonce')) || !isset($_COOKIE['jc_in'])) $fouten['algemeen'] = 'in.fout_verlopen';
  if ($w['naam'] === '') $fouten['naam'] = 'in.fout_naam';
  if (!preg_match('/^#[0-9a-f]{6}$/', $w['kleur'])) $fouten['kleur'] = 'in.fout_kleur';
  if ((!preg_match('#^https://[^/\s]+#', $w['adres']) && !$lokaal($w['adres'])) || !filter_var($w['adres'], FILTER_VALIDATE_URL) || preg_match('/[\s"\'<>]/', $w['adres'])) $fouten['adres'] = 'in.fout_adres';
  // Alleen gewone tekens: de browser stuurt het wachtwoord in een kop mee, en daar gaan é, € en emoji mis.
  if (strlen($ww) < 12) $fouten['wachtwoord'] = 'in.fout_kort';
  elseif (!preg_match('/^[\x21-\x7E]+$/', $ww)) $fouten['wachtwoord'] = 'in.fout_tekens';
  elseif ($ww !== $ww2) $fouten['wachtwoord2'] = 'in.fout_anders';
  if (!$fouten) {
    $c = (static function ($p) { $x = @include $p; return is_array($x) ? $x : []; })(__DIR__ . '/config.voorbeeld.php');
    $c['adres'] = $w['adres'];
    $c['dashboard_wachtwoord'] = $ww;
    $c['webhook_controlewoord'] = bin2hex(random_bytes(16));
    $c['taal'] = $taal; $c['naam'] = $w['naam']; $c['kleur'] = $w['kleur'];
    if (in_array($w['tijdzone'], timezone_identifiers_list(), true)) $c['tijdzone'] = $w['tijdzone'];
    foreach (['meta_app_id', 'meta_app_geheim', 'meta_token', 'instagram_id'] as $k) $c[$k] = '';
    // Nog een keer kijken: is er intussen (door een tweede bezoek) al een instellingenbestand?
    if (jc_config_pad() !== '') { $ingesteld = true; $open = false; }
    elseif (($f = jc_config_schrijf($c, jc_config_doel(), true)) === '') {
      $klaar = true; @unlink($aanBestand);
    } elseif ($f === 'in.al_bestand') { $ingesteld = true; $open = false;
    } elseif (isset($_POST['download'])) {
      header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="config.php"');
      echo "<?php\n// Jensiechat " . JC_VERSIE . ", instellingen. Zet dit bestand als config.php in de map van Jensiechat.\nreturn " . var_export($c, true) . ";\n";
      exit;
    } else { $fouten['algemeen'] = $f; $downloadNodig = true; }
  }
}
$fout = fn($k) => isset($fouten[$k]) ? '<p class="in-fout" id="fout-' . $k . '">' . $t($fouten[$k]) . '</p>' : '';
$aria = fn($k) => isset($fouten[$k]) ? ' aria-invalid="true" aria-describedby="fout-' . $k . '"' : '';
$kleurNu = preg_match('/^#[0-9a-f]{6}$/', $w['kleur']) ? $w['kleur'] : $kleuren[0];
?><!doctype html>
<html lang="<?= $taal ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?= $t('in.titel') ?></title>
<link rel="stylesheet" href="jensiechat.css?v=<?= $h(JC_VERSIE) ?>">
<link rel="stylesheet" id="in-kleur" href="thema.php?v=<?= $h(JC_VERSIE) ?>&amp;k=<?= substr($kleurNu, 1) ?>">
</head>
<body class="in-pagina">
<main class="in">
  <header class="in-kop">
    <span class="in-merk" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 12.5c0-4.4 3.6-8 8-8s8 3.6 8 8-3.6 8-8 8c-1.3 0-2.6-.3-3.7-.9L4 20.5l1-4.1c-.6-1.2-1-2.5-1-3.9Z"/></svg></span>
    <nav class="in-talen" aria-label="<?= $t('in.taal') ?>"><?php foreach ($talen as $code => $naamTaal): ?><a href="?lang=<?= $code ?>" lang="<?= $code ?>"<?= $code === $taal ? ' aria-current="true"' : '' ?>><?= $naamTaal ?></a><?php endforeach; ?></nav>
  </header>

<?php if ($klaar): ?>
  <section class="in-kaart in-klaar" aria-live="polite">
    <span class="in-vink" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></span>
    <h1><?= $t('in.klaar_kop') ?></h1>
    <p><?= $t('in.klaar_p') ?></p>
    <a class="knop knop-hoofd" href="./"><span><?= $t('in.klaar_knop') ?></span><i class="pijl" aria-hidden="true"></i></a>
  </section>
<?php elseif ($ingesteld && $JC_CONFIG_PAD === ''): ?>
  <section class="in-kaart">
    <h1><?= $t('in.kwijt_kop') ?></h1>
    <p><?= $t('in.kwijt_p') ?></p>
  </section>
<?php elseif ($ingesteld): ?>
  <section class="in-kaart">
    <h1><?= $t('in.al_kop') ?></h1>
    <p><?= $t('in.al_p') ?></p>
    <a class="knop knop-hoofd" href="./"><span><?= $t('in.al_knop') ?></span><i class="pijl" aria-hidden="true"></i></a>
  </section>
<?php elseif (!$open): ?>
  <section class="in-kaart">
    <h1><?= $t('in.dicht_kop') ?></h1>
    <p><?= $t('in.dicht_p') ?></p>
  </section>
<?php else: ?>
  <div class="in-intro">
    <h1><?= $t('in.kop') ?></h1>
    <p><?= $t('in.intro') ?></p>
    <?php if (!$https && !$lokaal($adresNu)): ?><p class="in-let"><?= $t('in.geen_https') ?></p><?php endif; ?>
  </div>
  <form class="in-kaart in-form" method="post" action="instellen.php" novalidate autocomplete="off">
    <input type="hidden" name="nonce" value="<?= $h($nonce) ?>">
    <input type="hidden" name="taal" value="<?= $taal ?>">
    <input type="hidden" name="tijdzone" id="in-tz" value="<?= $h($w['tijdzone']) ?>">
    <?php if (isset($fouten['algemeen'])): ?><div class="in-melding" role="alert"><p><?= $t($fouten['algemeen']) ?></p>
      <?php if ($downloadNodig): ?><p class="in-klein"><?= $t('in.wachtwoord_uitleg') ?></p><?php endif; ?></div><?php endif; ?>

    <div class="in-veld">
      <label for="in-naam"><?= $t('in.naam') ?></label>
      <input id="in-naam" name="naam" type="text" maxlength="40" required value="<?= $h($w['naam']) ?>" placeholder="<?= $t('in.naam_vb') ?>"<?= $aria('naam') ?>>
      <small><?= $t('in.naam_uitleg') ?></small><?= $fout('naam') ?>
    </div>

    <fieldset class="in-veld">
      <legend><?= $t('in.kleur') ?></legend>
      <div class="in-kleuren">
        <?php foreach ($kleuren as $i => $k): ?><label class="in-staal in-staal-<?= $i + 1 ?>"><input type="radio" name="kleur" value="<?= $k ?>"<?= $k === $kleurNu ? ' checked' : '' ?>><span class="sr"><?= $k ?></span></label><?php endforeach; ?>
        <label class="in-staal in-eigen"><input type="radio" name="kleur" id="in-eigen-keuze" value="<?= in_array($kleurNu, $kleuren, true) ? '#e8a317' : $kleurNu ?>"<?= in_array($kleurNu, $kleuren, true) ? '' : ' checked' ?>><input type="color" id="in-eigen" value="<?= in_array($kleurNu, $kleuren, true) ? '#e8a317' : $kleurNu ?>" aria-label="<?= $t('in.kleur_eigen') ?>"><span><?= $t('in.kleur_eigen') ?></span></label>
      </div>
      <small><?= $t('in.kleur_uitleg') ?></small><?= $fout('kleur') ?>
    </fieldset>

    <div class="in-veld">
      <label for="in-adres"><?= $t('in.adres') ?></label>
      <input id="in-adres" name="adres" type="url" inputmode="url" spellcheck="false" required value="<?= $h($w['adres']) ?>"<?= $aria('adres') ?>>
      <small><?= $t('in.adres_uitleg') ?></small><?= $fout('adres') ?>
    </div>

    <div class="in-veld">
      <div class="in-label-rij"><label for="in-ww"><?= $t('in.wachtwoord') ?></label><button type="button" class="knop-tekst" id="in-maak"><?= $t('in.maak') ?></button></div>
      <div class="in-ww"><input id="in-ww" name="wachtwoord" type="password" minlength="12" required autocomplete="new-password" spellcheck="false"<?= $aria('wachtwoord') ?>><button type="button" class="in-toon" data-toon="<?= $t('in.toon') ?>" data-verberg="<?= $t('in.verberg') ?>" aria-controls="in-ww in-ww2"><?= $t('in.toon') ?></button></div>
      <small><?= $t('in.wachtwoord_uitleg') ?></small><?= $fout('wachtwoord') ?>
    </div>
    <div class="in-veld">
      <label for="in-ww2"><?= $t('in.herhaal') ?></label>
      <input id="in-ww2" name="wachtwoord2" type="password" minlength="12" required autocomplete="new-password" spellcheck="false"<?= $aria('wachtwoord2') ?>>
      <?= $fout('wachtwoord2') ?>
    </div>

    <div class="in-acties">
      <?php if ($downloadNodig): ?><button class="knop knop-tweede" type="submit" name="download" value="1"><?= $t('in.download') ?></button><?php endif; ?>
      <button class="knop knop-hoofd" type="submit"><span><?= $t('in.bewaar') ?></span><i class="pijl" aria-hidden="true"></i></button>
    </div>
    <p class="in-klein"><?= $t('in.voet') ?> <a href="keuring.php?lang=<?= $taal ?>"><?= $t('in.keuring') ?></a></p>
  </form>
<?php endif; ?>
  <footer class="voet"><?= $t('h.voet_gemaakt') ?> <a href="https://praktijkwerkbank.nl" target="_blank" rel="noopener">Praktijkwerkbank</a>.</footer>
</main>
<?php if ($open && !$klaar): ?><script src="instellen.js?v=<?= $h(JC_VERSIE) ?>" defer></script><?php endif; ?>
</body>
</html>
