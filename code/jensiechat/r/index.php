<?php
// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// Telt een klik op een link uit een Instagram-bericht en stuurt door naar de echte pagina.
//   r/<slug>            klik zonder persoon (bijvoorbeeld een gedeelde link)
//   r/<slug>/<code>     klik van de persoon die het bericht kreeg
//   r/<slug>/<code>/2   de tweede link uit hetzelfde bericht
//   r/?s=<slug>&c=<code>&w=2   dezelfde links zonder .htaccess (mooie_links => false in config.php)
// Bewaart geen IP-adres en zet geen cookie. Voorbeeldweergaven van Instagram, WhatsApp en andere robots tellen niet mee.
define('JC_APP', 'link');
require __DIR__ . '/../lib.php';
header('X-Robots-Tag: noindex, nofollow'); header('Cache-Control: no-store'); header('Referrer-Policy: no-referrer-when-downgrade');
$slug = strtolower((string)($_GET['s'] ?? '')); $code = strtolower((string)($_GET['c'] ?? '')); $welke = ($_GET['w'] ?? '') === '2' ? 2 : 1;
$a = null; $doel = '';
try {
  $a = preg_match('/^[a-z0-9-]{1,40}$/', $slug) ? jc_automation_slug($slug) : null;
  // De korte link is later hernoemd: dan vinden we de automation via de persoonlijke code.
  if (!$a && preg_match('/^[a-z0-9]{4,12}$/', $code)) {
    $s = jc_db()->prepare('SELECT automation_id FROM reacties WHERE code=?'); $s->execute([$code]); $aid = $s->fetchColumn(); if ($aid) $a = jc_automation((int)$aid);
  }
  $doel = $a ? ($welke === 2 && $a['link2'] !== '' ? $a['link2'] : $a['link']) : '';
} catch (Throwable $e) { $a = null; } // de database even niet bereikbaar: dan naar de homepage, nooit een foutpagina
if (!$a || $doel === '' || !preg_match('#^https://#', $doel)) { header('Location: ' . jc_homepage(), true, 302); exit; }
$ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
$robot = $ua === '' || preg_match('/facebookexternalhit|facebot|meta-external|externalhit|bot\b|bot\/|crawler|spider|preview|whatsapp|telegram|slack|discord|twitter|linkedin|skype|curl|wget|python|go-http|headless|okhttp/i', $ua);
header('Location: ' . $doel, true, 302);
jc_klaar_met_antwoord();
if ($robot) exit;
try {
  $db = jc_db(); $rid = null;
  if ($code !== '' && preg_match('/^[a-z0-9]{4,12}$/', $code)) {
    $s = $db->prepare('SELECT id FROM reacties WHERE code=? AND automation_id=?'); $s->execute([$code, $a['id']]); $rid = $s->fetchColumn() ?: null;
    if ($rid) $db->prepare('UPDATE reacties SET kliks=kliks+1, klik_ts=COALESCE(klik_ts, ?) WHERE id=?')->execute([time(), $rid]);
  }
  $db->prepare('INSERT INTO klikken (automation_id, reactie_id, welke, ts) VALUES (?,?,?,?)')->execute([$a['id'], $rid, $welke, time()]);
} catch (Throwable $e) { /* een klik tellen mag het doorsturen nooit blokkeren */ }
