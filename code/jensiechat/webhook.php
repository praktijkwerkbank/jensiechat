<?php
// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// Het adres waar Meta meldingen over nieuwe reacties naartoe stuurt.
//   Callback-URL in het Meta-dashboard: <adres uit config.php>/webhook.php (het dashboard toont hem onder Gezondheid)
//   Verificatietoken: het webhook_controlewoord uit config.php
// GET = Meta controleert het adres. POST = een melding, ondertekend met het app-geheim (X-Hub-Signature-256).
// Zonder App Review stuurt Meta deze meldingen alleen voor accounts met een rol op de app. De eigen controle (cron.php) vangt de rest op.
define('JC_APP', 'webhook');
require __DIR__ . '/lib.php';
header('X-Robots-Tag: noindex, nofollow');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if ($JC_VERIFY !== '' && ($_GET['hub_mode'] ?? '') === 'subscribe' && hash_equals($JC_VERIFY, (string)($_GET['hub_verify_token'] ?? ''))) {
    header('Content-Type: text/plain'); echo (string)($_GET['hub_challenge'] ?? ''); jc_log('webhook', jc_t('log.webhook_gecontroleerd')); exit;
  }
  http_response_code(403); echo 'nee'; exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
if ($JC_APP_SECRET === '') { http_response_code(503); echo 'niet ingesteld'; exit; }

$ruw = file_get_contents('php://input');
$sig = (string)($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
if (!hash_equals('sha256=' . hash_hmac('sha256', $ruw, $JC_APP_SECRET), $sig)) {
  // Niet van Meta (of met een ander geheim ondertekend): weigeren, en eens per uur noteren.
  if ((int)jc_get('sig_fout_log', 0) < time() - 3600) { jc_set('sig_fout_log', time()); jc_log('fout', jc_t('log.handtekening')); }
  http_response_code(403); echo 'nee'; exit;
}
$payload = json_decode($ruw, true) ?: [];
jc_set('laatste_webhook', time());

// Eerst alles vastleggen, dan Meta meteen antwoorden (Meta wil snel een 200), daarna pas versturen.
$ids = []; $mis = false;
foreach (jc_webhook_reacties($payload) as $c) {
  try { $id = jc_nieuwe_reactie($c, 'webhook'); if ($id) $ids[] = $id; }
  catch (Throwable $e) { $mis = true; jc_log_veilig('fout', jc_t('log.melding_mislukt', ['fout' => $e->getMessage()])); }
}
// Lukte het vastleggen niet, dan een 500: Meta stuurt de melding opnieuw (wat al vastligt, wordt niet dubbel gedaan).
http_response_code($mis ? 500 : 200); header('Content-Type: text/plain'); echo 'ok';
jc_klaar_met_antwoord();
foreach ($ids as $i => $id) { if ($i) usleep(JC_PAUZE_TUSSEN); if (jc_verstuur($id) === 'pauze') break; }
