<?php
// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// De eigen controle. Een geplande taak (cronjob) bij je host start dit bestand elke minuut:
//   /usr/bin/php /pad/naar/je/map/cron.php
// (Het dashboard toont onder Gezondheid de precieze regel voor jouw server.)
// Een run loopt tot seconde 57 van de minuut en kijkt in die tijd elke 15 tot 60 seconden naar nieuwe reacties,
// afhankelijk van het verbruik dat Meta meldt. Daarna de wachtrij met berichten, en een keer per uur of dag het onderhoud.
// Loopt de vorige run nog, dan wacht de nieuwe tot tien seconden op het slot en stopt anders.
// Via het web alleen met het dashboardwachtwoord in de kop X-Sleutel (noodoplossing voor hosts zonder cronjob per minuut).
define('JC_APP', 'cron');
require __DIR__ . '/lib.php';
if (PHP_SAPI !== 'cli') {
  header('Content-Type: application/json'); header('X-Robots-Tag: noindex, nofollow');
  // Dezelfde rem tegen raden als het dashboard: zonder die rem zou dit adres een vrije weg zijn om het wachtwoord te proberen.
  if (jc_dashboard_fout() !== '' || jc_sleutel_check() !== 'ok') { http_response_code(403); echo '{"ok":false}'; exit; }
}
$slot = @fopen(jc_dir() . '/cron.lock', 'c'); $heb = false;
for ($i = 0; $slot && $i < 50 && !($heb = flock($slot, LOCK_EX | LOCK_NB)); $i++) usleep(200000);
if (!$heb) { jc_set('cron_botsing', time()); jc_set('cron_botsingen', (int)jc_get('cron_botsingen', 0) + 1); echo "al bezig\n"; exit; }
@set_time_limit(90);
$start = time(); $sec = (int)(getenv('JC_CRON_SEC') ?: ($_GET['sec'] ?? 0)); $sec = max(0, min($sec, 55));
$eind = $sec ? $start + $sec : $start - ($start % 60) + 57; if (!$sec && $eind - $start < 5) $eind += 60; // te laat begonnen: de volgende minuut erbij
jc_set('laatste_cron', $start);

$rondes = 0; $nieuw = 0; $volgendeVol = 0; $bijwerk = false;
while (time() < $eind) {
  $t = time();
  try {
    // Zonder App Review stuurt Meta meldingen over reacties alleen voor accounts met een rol op de app. Voor gewone volgers is
    // zelf kijken dus de hoofdweg: elke 15 seconden, een lichte aanroep. Loopt het verbruik dat Meta meldt op, dan langzamer.
    if ($t >= $volgendeVol) { $nieuw += jc_controle(false); $g = jc_gebruik(); $volgendeVol = $t + ($g >= 50 ? 60 : ($g >= 25 ? 30 : 15)); }
    jc_wachtrij(8, $eind);
    // Het onderhoud komt na de eerste ronde, zodat een nieuwe reactie daar niet op hoeft te wachten.
    if (!$bijwerk) { $bijwerk = true;
      jc_token_check();
      if ((int)jc_get('opgeruimd', 0) < $start - 86400) { $n = jc_opruimen(); jc_set('opgeruimd', $start); if ($n) jc_log('opruimen', jc_t('log.opgeruimd', ['n' => $n, 'dagen' => JC_BEWAAR_DAGEN])); }
    }
  } catch (Throwable $e) { jc_log_veilig('fout', jc_t('log.controle_onderbroken', ['fout' => $e->getMessage()])); }
  $rondes++;
  if (time() + 2 >= $eind) break;
  sleep(2);
}
flock($slot, LOCK_UN);
echo json_encode(['ok' => true, 'rondes' => $rondes, 'nieuw' => $nieuw]) . "\n";
