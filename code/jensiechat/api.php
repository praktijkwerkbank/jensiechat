<?php
// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// Het eindpunt van het dashboard. Alleen met het dashboardwachtwoord (kop X-Sleutel). POST met JSON {a: actie, ...}.
define('JC_APP', 'api');
require __DIR__ . '/lib.php';
header('Content-Type: application/json; charset=utf-8'); header('X-Robots-Tag: noindex, nofollow'); header('Cache-Control: no-store');
function uit($x, $code = 200) { http_response_code($code); echo json_encode($x, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
if (($f = jc_dashboard_fout()) !== '') uit(['ok' => false, 'fout' => $f, 'instellen' => true], 503);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') uit(['ok' => false], 405);
// Tegen raden: na 20 foute pogingen in een kwartier een kwartier niets meer aannemen (jc_sleutel_check in lib.php).
$sleutel = jc_sleutel_check();
if ($sleutel === 'te_vaak') uit(['ok' => false, 'fout' => jc_t('api.te_vaak')], 429);
if ($sleutel !== 'ok') uit(['ok' => false, 'fout' => 'sleutel'], 403);
$in = json_decode(file_get_contents('php://input'), true) ?: [];
$a = (string)($in['a'] ?? '');
$db = jc_db();

switch ($a) {
  case 'staat': uit(['ok' => true] + staat($in));

  case 'opslaan':
    $x = $in['automation'] ?? []; $id = (int)($x['id'] ?? 0);
    $naam = mb_substr(trim((string)($x['naam'] ?? '')), 0, 60);
    $woorden = array_values(array_unique(array_filter(array_map(fn($w) => mb_substr(trim(mb_strtolower((string)$w)), 0, 40), (array)($x['trefwoorden'] ?? [])), fn($w) => $w !== '')));
    $slug = strtolower(trim((string)($x['slug'] ?? '')));
    $dm = rtrim((string)($x['dm_tekst'] ?? ''));
    $link = trim((string)($x['link'] ?? '')); $link2 = trim((string)($x['link2'] ?? ''));
    $ant = array_values(array_filter(array_map(fn($w) => mb_substr(trim((string)$w), 0, 200), (array)($x['antwoorden'] ?? [])), fn($w) => $w !== ''));
    $testers = array_values(array_filter(array_map(fn($w) => ltrim(trim(mb_strtolower((string)$w)), '@'), (array)($x['testers'] ?? [])), fn($w) => $w !== ''));
    $stijl = ($x['stijl'] ?? 'tekst') === 'knop' ? 'knop' : 'tekst'; $knop = trim((string)($x['knop'] ?? '')); $knop2 = trim((string)($x['knop2'] ?? ''));
    $mediaId = (string)($x['media_id'] ?? ''); if ($mediaId !== '' && $mediaId !== 'volgende' && !preg_match('/^\d{1,40}$/', $mediaId)) $mediaId = '';
    $fouten = [];
    if ($stijl === 'knop') {
      if ($link === '') $fouten['link'] = jc_t('val.knop_link');
      if ($knop === '') $fouten['knop'] = jc_t('val.knop_tekst');
      elseif (mb_strlen($knop) > JC_MAX_KNOP) $fouten['knop'] = jc_t('val.knop_lang', ['max' => JC_MAX_KNOP, 'n' => mb_strlen($knop)]);
      if (mb_strlen($knop2) > JC_MAX_KNOP) $fouten['knop2'] = jc_t('val.knop_lang', ['max' => JC_MAX_KNOP, 'n' => mb_strlen($knop2)]);
      if ($link2 !== '' && $knop2 === '') $fouten['knop2'] = jc_t('val.knop2_tekst');
      if ($knop2 !== '' && $link2 === '') $fouten['link2'] = jc_t('val.knop2_link');
      if (strpos($dm, '{link}') !== false || strpos($dm, '{link2}') !== false) $fouten['dm_tekst'] = jc_t('val.knop_geen_link_in_tekst');
      elseif (mb_strlen($dm) + 30 > JC_MAX_KNOPTEKST) $fouten['dm_tekst'] = jc_t('val.knop_bericht_lang', ['max' => JC_MAX_KNOPTEKST - 30, 'n' => mb_strlen($dm)]);
    }
    if ($naam === '') $fouten['naam'] = jc_t('val.naam');
    if (!$woorden) $fouten['trefwoorden'] = jc_t('val.trefwoord');
    if (!preg_match('/^[a-z0-9-]{2,40}$/', $slug)) $fouten['slug'] = jc_t('val.slug');
    if ($dm === '') $fouten['dm_tekst'] = jc_t('val.bericht');
    if ($link !== '' && !preg_match('#^https://\S+$#', $link)) $fouten['link'] = jc_t('val.https');
    if ($link2 !== '' && !preg_match('#^https://\S+$#', $link2)) $fouten['link2'] = jc_t('val.https');
    if (strpos($dm, '{link}') !== false && $link === '') $fouten['link'] = jc_t('val.link_nodig');
    if (strpos($dm, '{link2}') !== false && $link2 === '') $fouten['link2'] = jc_t('val.link2_nodig');
    $s = $db->prepare('SELECT id FROM automations WHERE slug=? AND id<>?'); $s->execute([$slug, $id]); if ($s->fetchColumn()) $fouten['slug'] = jc_t('val.slug_bestaat');
    if (!isset($fouten['trefwoorden'])) foreach (jc_automations() as $o) if ($o['id'] !== $id && $o['media_id'] === $mediaId) {
      $dubbel = array_intersect($woorden, $o['trefwoorden']); if ($dubbel) { $fouten['trefwoorden'] = jc_t('val.trefwoord_dubbel', ['woord' => reset($dubbel), 'naam' => $o['naam']]); break; }
    }
    $proef = ['dm_tekst' => $dm, 'link' => $link, 'link2' => $link2, 'slug' => $slug ?: 'x', 'stijl' => $stijl, 'knop' => $knop, 'knop2' => $knop2];
    // Ook de knopvorm moet als gewone tekst passen: dat is de uitwijk als Meta de knoppen weigert.
    $bytes = strlen(jc_knop_als_tekst(jc_dm_tekst($proef, 'abcdefg', str_repeat('n', 30)), jc_knoppen($proef, 'abcdefg'))); // ruim gerekend: de langste Instagram-naam is 30 tekens
    if ($bytes > JC_MAX_BYTES && !isset($fouten['dm_tekst'])) $fouten['dm_tekst'] = jc_t('val.te_lang', ['n' => $bytes]);
    if ($fouten) uit(['ok' => false, 'fouten' => $fouten], 422);
    $aan = !empty($x['aan']) ? 1 : 0; $test = !empty($x['test']) ? 1 : 0; $nu = time();
    $rij = [$naam, json_encode($woorden, JSON_UNESCAPED_UNICODE), $mediaId, $dm, $link, $link2, $slug, json_encode($ant, JSON_UNESCAPED_UNICODE), $aan, $test, json_encode($testers), $stijl, $knop, $knop2];
    if ($id) {
      $oud = jc_automation($id); if (!$oud) uit(['ok' => false, 'fout' => jc_t('api.bestaat_niet')], 404);
      $sinds = $aan && !$oud['aan'] ? $nu : $oud['aan_sinds'];
      $db->prepare('UPDATE automations SET naam=?, trefwoorden=?, media_id=?, dm_tekst=?, link=?, link2=?, slug=?, antwoorden=?, aan=?, test=?, testers=?, stijl=?, knop=?, knop2=?, aan_sinds=?, gewijzigd=? WHERE id=?')
        ->execute(array_merge($rij, [$sinds, $nu, $id]));
    } else {
      $db->prepare('INSERT INTO automations (naam, trefwoorden, media_id, dm_tekst, link, link2, slug, antwoorden, aan, test, testers, stijl, knop, knop2, aan_sinds, gemaakt, gewijzigd) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute(array_merge($rij, [$aan ? $nu : 0, $nu, $nu])); $id = (int)$db->lastInsertId();
    }
    jc_log('wijziging', jc_t('log.opgeslagen', ['naam' => $naam, 'stand' => jc_t($aan ? ($test ? 'log.stand_test' : 'log.stand_aan') : 'log.stand_uit')]));
    uit(['ok' => true, 'id' => $id] + staat($in));

  case 'wissel':
    $o = jc_automation((int)($in['id'] ?? 0)); if (!$o) uit(['ok' => false], 404);
    $aan = !empty($in['aan']) ? 1 : 0;
    $db->prepare('UPDATE automations SET aan=?, aan_sinds=?, gewijzigd=? WHERE id=?')->execute([$aan, $aan && !$o['aan'] ? time() : $o['aan_sinds'], time(), $o['id']]);
    jc_log('wijziging', jc_t($aan ? 'log.aangezet' : 'log.uitgezet', ['naam' => $o['naam']]));
    uit(['ok' => true] + staat($in));

  case 'verwijder':
    $o = jc_automation((int)($in['id'] ?? 0)); if (!$o) uit(['ok' => false], 404);
    // Niet echt weg: links in al verstuurde berichten blijven werken en de cijfers blijven bij deze automation.
    $db->prepare('UPDATE automations SET verwijderd=1, aan=0, gewijzigd=? WHERE id=?')->execute([time(), $o['id']]);
    jc_log('wijziging', jc_t('log.verwijderd', ['naam' => $o['naam']]));
    uit(['ok' => true] + staat($in));

  case 'probeer': // Wat zou er gebeuren bij deze reactie? Verstuurt niets.
    $tekst = (string)($in['tekst'] ?? ''); $m = (string)($in['media_id'] ?? '');
    $o = jc_zoek_automation($tekst, $m, false);
    if (!$o) uit(['ok' => true, 'match' => null]);
    $dm = jc_dm_tekst($o, 'voorbeld', 'jouwnaam'); $kn = jc_knoppen($o, 'voorbeld');
    uit(['ok' => true, 'match' => ['knoppen' => array_map(fn($k) => ['title' => $k['title'], 'url' => $k['url']], $kn), 'id' => $o['id'], 'naam' => $o['naam'], 'aan' => $o['aan'], 'test' => $o['test'], 'dm' => $dm, 'bytes' => strlen($dm), 'antwoorden' => $o['antwoorden']]]);

  case 'controle_nu':
    jc_token_check(true); $n = jc_controle(false); $v = jc_wachtrij(5, time() + 20);
    uit(['ok' => true, 'nieuw' => $n, 'verstuurd' => $v] + staat($in));

  case 'webhook_aanmelden': // Zet de meldingen voor reacties aan op het Instagram-account (naast de instelling in het Meta-dashboard).
    $ig = jc_ig_id(); if ($ig === '') uit(['ok' => false, 'fout' => jc_t('api.ig_onbekend')] + staat($in));
    $r = jc_graph('POST', $ig . '/subscribed_apps', ['subscribed_fields' => 'comments']);
    $pid = (string)jc_get('page_id', '');
    if (!$r['ok'] && $pid !== '') $r = jc_graph('POST', $pid . '/subscribed_apps', ['subscribed_fields' => 'feed']);
    jc_set('webhook_aangemeld', $r['ok'] ? date('c') : jc_t('api.mislukt', ['fout' => $r['fout']]));
    jc_log($r['ok'] ? 'webhook' : 'fout', $r['ok'] ? jc_t('log.webhook_aan') : jc_t('log.webhook_aan_mislukt', ['fout' => $r['fout']]));
    uit(['ok' => $r['ok'], 'fout' => $r['fout']] + staat($in));

  case 'opnieuw': // Een mislukt bericht nog eens proberen (Meta weigerde het; er is dus nog niets aangekomen).
    $id = (int)($in['id'] ?? 0);
    $db->prepare("UPDATE reacties SET dm_status='wacht', dm_fout='', antwoord_status=CASE WHEN antwoord_status='fout' THEN 'wacht' ELSE antwoord_status END WHERE id=? AND dm_status='fout' AND gewist=0")->execute([$id]);
    $uitkomst = jc_verstuur($id);
    uit(['ok' => true, 'uitkomst' => $uitkomst] + staat($in));

  case 'antwoord_opnieuw': // Het bericht kwam aan, het publieke antwoord niet: alleen dat antwoord nog eens plaatsen.
    $id = (int)($in['id'] ?? 0);
    $s = $db->prepare("UPDATE reacties SET antwoord_status='wacht', antwoord_fout='' WHERE id=? AND dm_status='verstuurd' AND antwoord_status='fout' AND gewist=0"); $s->execute([$id]);
    $gelukt = false;
    if ($s->rowCount() === 1) { $r = $db->query('SELECT * FROM reacties WHERE id=' . $id)->fetch(); $au = jc_automation((int)$r['automation_id']); if ($au) $gelukt = jc_plaats_antwoord($r, $au); }
    uit(['ok' => true, 'gelukt' => $gelukt] + staat($in));

  case 'label': // Een eigen label bij iemand zetten of weghalen
    $ok = jc_label_zet((string)($in['username'] ?? ''), (string)($in['label'] ?? ''), !empty($in['aan']));
    uit(['ok' => $ok, 'fout' => $ok ? '' : jc_t('api.label_leeg')] + staat($in));

  case 'verwijder_persoon':
    $n = jc_verwijder_persoon((string)($in['username'] ?? ''));
    jc_log('privacy', jc_t('log.gewist', ['n' => $n]));
    uit(['ok' => true, 'aantal' => $n] + staat($in));

  default: uit(['ok' => false, 'fout' => jc_t('api.onbekend')], 400);
}

/** Alles wat het dashboard laat zien. */
function staat($in) {
  $db = jc_db(); $tz = new DateTimeZone(JC_TZ);
  $autos = jc_automations();
  $media = $db->query('SELECT id, caption, permalink, thumb, soort, ts FROM media ORDER BY ts DESC LIMIT 30')->fetchAll();
  $mediaNaam = []; foreach ($media as $m) $mediaNaam[$m['id']] = jc_media_kort($m['id'], 70) ?: jc_t($m['soort'] === 'reel' ? 'api.reel_van' : 'api.post_van', ['datum' => (new DateTime('@' . $m['ts']))->setTimezone($tz)->format('j-n')]);

  // Cijfers per automation en per post (testreacties tellen niet mee)
  $per = function ($kolom) use ($db) {
    $q = $db->query("SELECT $kolom AS k, COUNT(*) AS reacties, SUM(dm_status='verstuurd') AS verstuurd, SUM(dm_status='fout') AS fout,
      SUM(dm_status='verstuurd' AND kliks>0) AS geklikt, SUM(kliks) AS kliks FROM reacties WHERE test=0 AND dm_status<>'overgeslagen' GROUP BY $kolom");
    $uit = []; foreach ($q as $r) { $v = (int)$r['verstuurd']; $uit[(string)$r['k']] = ['reacties' => (int)$r['reacties'], 'verstuurd' => $v, 'fout' => (int)$r['fout'], 'geklikt' => (int)$r['geklikt'], 'kliks' => (int)$r['kliks'], 'ratio' => $v ? round(100 * $r['geklikt'] / $v) : null]; }
    return $uit;
  };
  $perAuto = $per('automation_id'); $perMedia = $per('media_id');
  $losseKliks = []; foreach ($db->query('SELECT automation_id AS k, COUNT(*) AS n FROM klikken WHERE reactie_id IS NULL GROUP BY automation_id') as $r) $losseKliks[(string)$r['k']] = (int)$r['n'];

  // Lijn per dag, laatste 30 dagen
  $dagen = []; $d = (new DateTime('now', $tz))->setTime(0, 0)->modify('-29 days');
  for ($i = 0; $i < 30; $i++) { $dagen[$d->format('Y-m-d')] = ['dag' => $d->format('Y-m-d'), 'reacties' => 0, 'verstuurd' => 0, 'kliks' => 0]; $d->modify('+1 day'); }
  $van = (new DateTime('now', $tz))->setTime(0, 0)->modify('-29 days')->getTimestamp();
  $aid = (int)($in['auto'] ?? 0); $w = $aid ? ' AND automation_id=' . $aid : '';
  foreach ($db->query("SELECT ts, dm_status FROM reacties WHERE test=0 AND dm_status<>'overgeslagen' AND ts>=$van$w") as $r) {
    $k = (new DateTime('@' . $r['ts']))->setTimezone($tz)->format('Y-m-d'); if (!isset($dagen[$k])) continue;
    $dagen[$k]['reacties']++; if ($r['dm_status'] === 'verstuurd') $dagen[$k]['verstuurd']++;
  }
  foreach ($db->query("SELECT ts FROM klikken WHERE ts>=$van$w") as $r) { $k = (new DateTime('@' . $r['ts']))->setTimezone($tz)->format('Y-m-d'); if (isset($dagen[$k])) $dagen[$k]['kliks']++; }

  // Activiteit
  $f = (string)($in['filter'] ?? 'alles'); $where = '1';
  if ($f === 'verstuurd') $where = "dm_status='verstuurd'"; elseif ($f === 'fout') $where = "(dm_status='fout' OR antwoord_status='fout')";
  elseif ($f === 'geklikt') $where = 'kliks>0'; elseif ($f === 'wacht') $where = "dm_status IN ('wacht','bezig')"; elseif ($f === 'test') $where = 'test=1';
  $act = $db->query("SELECT id, automation_id, media_id, username, tekst, ts, bron, test, vorm, dm_status, dm_fout, dm_ts, antwoord_status, antwoord_tekst, antwoord_fout, kliks, klik_ts, gewist
    FROM reacties WHERE $where ORDER BY ts DESC LIMIT 150")->fetchAll();
  foreach ($act as &$r) foreach (['id', 'automation_id', 'ts', 'test', 'dm_ts', 'kliks', 'klik_ts', 'gewist'] as $k) $r[$k] = $r[$k] === null ? null : (int)$r[$k];
  unset($r);

  $tot = $db->query("SELECT COUNT(*) AS reacties, SUM(dm_status='verstuurd') AS verstuurd, SUM(dm_status='verstuurd' AND kliks>0) AS geklikt FROM reacties WHERE test=0 AND dm_status<>'overgeslagen'")->fetch();
  $week = $db->query("SELECT COUNT(*) AS reacties, SUM(dm_status='verstuurd') AS verstuurd, SUM(dm_status='verstuurd' AND kliks>0) AS geklikt FROM reacties WHERE test=0 AND dm_status<>'overgeslagen' AND ts>" . (time() - 7 * 86400))->fetch();
  $num = fn($r) => array_map(fn($v) => (int)$v, $r);

  return [
    'automations' => array_map(fn($x) => $x + ['cijfers' => ($perAuto[(string)$x['id']] ?? ['reacties' => 0, 'verstuurd' => 0, 'fout' => 0, 'geklikt' => 0, 'kliks' => 0, 'ratio' => null]) + ['losse_kliks' => $losseKliks[(string)$x['id']] ?? 0],
      'voorbeeld_link' => jc_redirect_url($x, 'abcdefg'), 'bytes' => strlen(jc_dm_tekst($x, 'abcdefg', 'gebruikersnaam'))], $autos),
    'media' => array_map(fn($m) => $m + ['naam' => $mediaNaam[$m['id']], 'cijfers' => $perMedia[$m['id']] ?? null], $media),
    'media_naam' => $mediaNaam,
    'dagen' => array_values($dagen),
    'activiteit' => $act,
    'totaal' => $num($tot), 'week' => $num($week),
    'mensen' => jc_mensen(),
    'gezondheid' => jc_gezondheid(),
    'korte_link' => jc_korte_link_basis(), 'mooie_links' => jc_cfg('mooie_links', true) !== false,
  ];
}
