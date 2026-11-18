<?php
// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// Het dashboard. De gegevens komen via api.php, achter het dashboardwachtwoord.
define('JC_APP', 'html');
require __DIR__ . '/lib.php';
if (($f = jc_dashboard_fout()) !== '') jc_stop($f);
header('Content-Type: text/html; charset=utf-8'); header('X-Robots-Tag: noindex, nofollow'); header('Cache-Control: no-store');
$M = jc_merk(); $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); $v = $h(JC_VERSIE);
$logo = $M['logo'] !== ''
  ? '<img class="merk-logo" src="' . $h($M['logo']) . '" alt="" width="44" height="44">'
  : '<span class="merk-logo merk-tekst" aria-hidden="true">' . $h(mb_strtoupper(mb_substr($M['naam'], 0, 1))) . '</span>';
$voet = '<footer class="voet">' . $h(jc_t('h.voet_gemaakt')) . ' <a href="https://praktijkwerkbank.nl" target="_blank" rel="noopener">Praktijkwerkbank</a>. ' . $h(jc_t('h.voet_geen')) . '</footer>';
$t = fn($k) => $h(jc_t($k));
?><!doctype html>
<html lang="<?= $h(jc_taal()) ?>" data-versie="<?= $v ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex,nofollow">
<title><?= $h($M['naam']) ?></title>
<link rel="icon" type="image/svg+xml" href="thema.php?icoon=1&amp;v=<?= $v ?>">
<link rel="stylesheet" href="jensiechat.css?v=<?= $v ?>">
<link rel="stylesheet" href="thema.php?v=<?= $v ?>">
</head>
<body>
<div class="veld" aria-hidden="true"><i></i><i></i><i></i><i></i></div>

<!-- Slot: het dashboardwachtwoord -->
<main class="slot" id="slot" hidden>
  <form class="slot-kaart" id="slot-form">
    <?= $logo ?>
    <h1><?= $h($M['naam']) ?></h1>
    <p><?= $t('h.slot_uitleg') ?></p>
    <label class="veldje"><span><?= $t('h.wachtwoord') ?></span><input id="slot-sleutel" type="password" autocomplete="current-password" required></label>
    <p class="fout" id="slot-fout" role="alert"></p>
    <button class="knop knop-hoofd" type="submit"><span><?= $t('h.openen') ?></span><i class="pijl" aria-hidden="true"></i></button>
  </form>
  <?= $voet ?>
</main>

<!-- Het dashboard -->
<div class="app" id="app" hidden>
  <header class="kop">
    <div class="kop-merk">
      <?= $logo ?>
      <div><strong><?= $h($M['naam']) ?></strong><span id="kop-account"></span></div>
    </div>
    <div class="kop-rechts">
      <button class="status" id="kop-status" type="button" data-naar="gezondheid"><i></i><span><?= $t('h.even_kijken') ?></span></button>
      <button class="knop-tekst" id="afmelden" type="button"><?= $t('h.afmelden') ?></button>
    </div>
  </header>

  <nav class="tabs" aria-label="<?= $t('h.onderdelen') ?>">
    <button type="button" data-tab="overzicht" aria-current="page"><?= $t('h.tab_overzicht') ?></button>
    <button type="button" data-tab="automations"><?= $t('h.tab_automations') ?></button>
    <button type="button" data-tab="mensen"><?= $t('h.tab_mensen') ?></button>
    <button type="button" data-tab="activiteit"><?= $t('h.tab_activiteit') ?></button>
    <button type="button" data-tab="gezondheid"><?= $t('h.tab_gezondheid') ?></button>
  </nav>

  <!-- Overzicht -->
  <section class="paneel" id="p-overzicht">
    <div class="zin" id="ov-zin"></div>
    <div class="glas cijferstrook" id="ov-cijfers"></div>
    <div class="glas grafiek-kaart">
      <div class="kaart-kop">
        <h2><?= $t('h.per_dag') ?></h2>
        <div class="legenda"><span class="l-reacties"><?= $t('h.l_reacties') ?></span><span class="l-verstuurd"><?= $t('h.l_berichten') ?></span><span class="l-kliks"><?= $t('h.l_kliks') ?></span></div>
      </div>
      <div class="grafiek" id="ov-grafiek"></div>
    </div>
    <div class="twee">
      <div class="glas">
        <div class="kaart-kop"><h2><?= $t('h.per_automation') ?></h2></div>
        <div id="ov-autos"></div>
      </div>
      <div class="glas">
        <div class="kaart-kop"><h2><?= $t('h.per_post') ?></h2></div>
        <div id="ov-reels"></div>
      </div>
    </div>
  </section>

  <!-- Automations -->
  <section class="paneel" id="p-automations" hidden>
    <div class="paneel-kop">
      <div><h2><?= $t('h.tab_automations') ?></h2><p><?= $t('h.au_uitleg') ?></p></div>
      <button class="knop knop-hoofd" type="button" id="nieuw"><span><?= $t('h.nieuwe_automation') ?></span><i class="pijl" aria-hidden="true"></i></button>
    </div>
    <div id="auto-lijst" class="auto-lijst"></div>
    <div class="glas probeer">
      <div class="kaart-kop"><h2><?= $t('h.probeer') ?></h2></div>
      <p class="uitleg"><?= $t('h.probeer_uitleg') ?></p>
      <label class="veldje"><span><?= $t('h.reactie') ?></span><input id="probeer-tekst" type="text" placeholder="<?= $t('h.probeer_vb') ?>" autocomplete="off"></label>
      <div id="probeer-uit" class="probeer-uit" aria-live="polite"></div>
    </div>
  </section>

  <!-- Mensen -->
  <section class="paneel" id="p-mensen" hidden>
    <div class="paneel-kop">
      <div><h2><?= $t('h.tab_mensen') ?></h2><p><?= $t('h.me_uitleg') ?></p></div>
    </div>
    <div class="mensen-balk">
      <label class="veldje zoek"><span class="alleen-lezer"><?= $t('h.zoeken') ?></span><input id="mens-zoek" type="search" placeholder="<?= $t('h.zoek_vb') ?>" autocomplete="off"></label>
      <div class="filters" id="mens-labels" role="group" aria-label="<?= $t('h.filter_label') ?>"></div>
    </div>
    <p class="telling" id="mens-telling" aria-live="polite"></p>
    <ul class="glas mensen" id="mens-lijst"></ul>
  </section>

  <!-- Activiteit -->
  <section class="paneel" id="p-activiteit" hidden>
    <div class="paneel-kop">
      <div><h2><?= $t('h.tab_activiteit') ?></h2><p><?= $t('h.ac_uitleg') ?></p></div>
    </div>
    <div class="filters" id="filters" role="group" aria-label="<?= $t('h.filter') ?>">
      <button type="button" data-filter="alles" aria-pressed="true"><?= $t('h.f_alles') ?></button>
      <button type="button" data-filter="verstuurd" aria-pressed="false"><?= $t('h.f_verstuurd') ?></button>
      <button type="button" data-filter="geklikt" aria-pressed="false"><?= $t('h.f_geklikt') ?></button>
      <button type="button" data-filter="fout" aria-pressed="false"><?= $t('h.f_mislukt') ?></button>
      <button type="button" data-filter="wacht" aria-pressed="false"><?= $t('h.f_wachtrij') ?></button>
      <button type="button" data-filter="test" aria-pressed="false"><?= $t('h.f_test') ?></button>
    </div>
    <ol class="glas activiteit" id="act-lijst"></ol>
  </section>

  <!-- Gezondheid -->
  <section class="paneel" id="p-gezondheid" hidden>
    <div class="paneel-kop">
      <div><h2><?= $t('h.tab_gezondheid') ?></h2><p><?= $t('h.ge_uitleg') ?></p></div>
      <button class="knop knop-tweede" type="button" id="nu-controleren"><?= $t('h.nu_controleren') ?></button>
    </div>
    <ul class="glas checks" id="checks"></ul>
    <div class="twee">
      <div class="glas">
        <div class="kaart-kop"><h2><?= $t('h.logboek') ?></h2></div>
        <ol class="logboek" id="logboek"></ol>
      </div>
      <div class="glas">
        <div class="kaart-kop"><h2><?= $t('h.wissen') ?></h2></div>
        <p class="uitleg"><?= $t('h.wissen_uitleg') ?></p>
        <form id="wis-form" class="wis-form">
          <label class="veldje"><span><?= $t('h.ig_naam') ?></span><input id="wis-naam" type="text" placeholder="<?= $t('h.ig_naam_vb') ?>" autocomplete="off" required></label>
          <button class="knop knop-tweede" type="submit"><?= $t('h.wis_knop') ?></button>
        </form>
        <p class="melding" id="wis-uit" role="status"></p>
      </div>
    </div>
  </section>
  <?= $voet ?>
</div>

<!-- Bewerken van een automation -->
<dialog class="blad" id="blad" aria-labelledby="blad-titel">
  <form id="blad-form" method="dialog" novalidate>
    <div class="blad-kop">
      <h2 id="blad-titel"><?= $t('h.nieuwe_automation') ?></h2>
      <button class="sluit" type="button" id="blad-sluit" aria-label="<?= $t('h.sluiten') ?>"><svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    </div>
    <div class="blad-romp">
      <div class="blad-velden">
        <label class="veldje"><span><?= $t('h.naam') ?></span><input name="naam" type="text" maxlength="60" placeholder="<?= $t('h.naam_vb') ?>" required><em class="veld-fout" data-voor="naam"></em></label>

        <div class="veldje">
          <span id="lbl-woorden"><?= $t('h.trefwoorden') ?></span>
          <div class="chips-invoer" id="woorden" role="group" aria-labelledby="lbl-woorden"><input type="text" id="woord-in" placeholder="<?= $t('h.typ_enter') ?>" autocomplete="off" aria-labelledby="lbl-woorden"></div>
          <small><?= $t('h.trefwoorden_uitleg') ?></small>
          <em class="veld-fout" data-voor="trefwoorden"></em>
        </div>

        <label class="veldje"><span><?= $t('h.welke_post') ?></span>
          <select name="media_id" id="media-keus"><option value=""><?= $t('js.media_alle') ?></option></select>
          <small><?= $t('h.welke_post_uitleg') ?></small>
        </label>

        <fieldset class="veldje keuze">
          <legend><?= $t('h.hoe_link') ?></legend>
          <label><input type="radio" name="stijl" value="knop"><span><b><?= $t('h.als_knop') ?></b><small><?= $t('h.als_knop_uitleg') ?></small></span></label>
          <label><input type="radio" name="stijl" value="tekst"><span><b><?= $t('h.in_tekst') ?></b><small><?= $t('h.in_tekst_uitleg') ?></small></span></label>
        </fieldset>

        <div class="veldje">
          <label for="dm-tekst"><?= $t('h.bericht') ?></label>
          <textarea name="dm_tekst" id="dm-tekst" rows="9" required></textarea>
          <div class="tekst-hulp">
            <div class="invoegen"><span><?= $t('h.invoegen') ?></span><button type="button" data-zet="{link}"><?= $t('h.ins_link') ?></button><button type="button" data-zet="{link2}"><?= $t('h.ins_link2') ?></button><button type="button" data-zet="{naam}"><?= $t('h.ins_naam') ?></button></div>
            <span class="teller" id="bytes">0 / 1000</span>
          </div>
          <em class="veld-fout" data-voor="dm_tekst"></em>
        </div>

        <label class="veldje"><span><?= $t('h.link') ?></span><input name="link" type="url" placeholder="<?= $t('h.link_vb') ?>" inputmode="url"><small><?= $t('h.link_uitleg') ?></small><em class="veld-fout" data-voor="link"></em></label>
        <label class="veldje alleen-knop"><span><?= $t('h.knoptekst') ?></span><input name="knop" type="text" maxlength="20" placeholder="<?= $t('h.knoptekst_vb') ?>"><small><?= $t('h.max20') ?></small><em class="veld-fout" data-voor="knop"></em></label>
        <details class="meer-opties" id="meer-opties"><summary><?= $t('h.tweede_link_erbij') ?> <i><?= $t('h.hoeft_niet') ?></i></summary>
        <label class="veldje"><span><?= $t('h.tweede_link') ?> <i><?= $t('h.mag_leeg') ?></i></span><input name="link2" type="url" placeholder="https://" inputmode="url"><em class="veld-fout" data-voor="link2"></em></label>
        <label class="veldje alleen-knop"><span><?= $t('h.knoptekst2') ?> <i><?= $t('h.mag_leeg') ?></i></span><input name="knop2" type="text" maxlength="20" placeholder="<?= $t('h.knoptekst2_vb') ?>"><em class="veld-fout" data-voor="knop2"></em></label>
        </details>
        <label class="veldje"><span><?= $t('h.korte_link') ?></span><div class="voorvoegsel"><b id="slug-voor">r/</b><input name="slug" type="text" maxlength="40" pattern="[a-z0-9-]+" autocapitalize="off" spellcheck="false" required></div><em class="veld-fout" data-voor="slug"></em></label>

        <div class="veldje">
          <span><?= $t('h.publiek') ?></span>
          <small><?= $t('h.publiek_uitleg') ?></small>
          <ol class="antwoorden" id="antwoorden"></ol>
          <button class="knop-tekst" type="button" id="antwoord-erbij"><?= $t('h.antwoord_erbij') ?></button>
        </div>

        <div class="schakels">
          <label class="schakel"><input type="checkbox" name="aan"><span class="schuif" aria-hidden="true"></span><span><b><?= $t('h.aan') ?></b><small><?= $t('h.aan_uitleg') ?></small></span></label>
          <label class="schakel"><input type="checkbox" name="test" id="test-aan"><span class="schuif" aria-hidden="true"></span><span><b><?= $t('h.testmodus') ?></b><small><?= $t('h.testmodus_uitleg') ?></small></span></label>
          <label class="veldje" id="testers-veld"><span><?= $t('h.testaccounts') ?></span><input name="testers" type="text" placeholder="<?= $t('h.testaccounts_vb') ?>" autocomplete="off"></label>
        </div>
      </div>

      <aside class="voorbeeld" aria-label="<?= $t('h.voorbeeld') ?>">
        <p class="voorbeeld-kop"><?= $t('h.zo_ziet') ?></p>
        <div class="telefoon">
          <div class="reactie"><b>@<?= $t('bl.iemand') ?></b> <span id="vb-reactie"></span></div>
          <div class="reactie antwoord"><b class="vb-account">@jouwaccount</b> <span id="vb-antwoord"></span></div>
          <div class="dm-kop"><?= $t('h.bericht_van') ?> <span class="vb-account">@jouwaccount</span></div>
          <div class="bel" id="vb-dm"></div>
          <div class="vb-knoppen" id="vb-knoppen"></div>
        </div>
      </aside>
    </div>
    <div class="blad-voet">
      <button class="knop-tekst gevaar" type="button" id="blad-weg"><?= $t('h.verwijderen') ?></button>
      <span class="voet-ruimte"></span>
      <button class="knop knop-tweede" type="button" id="blad-annuleer"><?= $t('h.annuleren') ?></button>
      <button class="knop knop-hoofd" type="submit" id="blad-bewaar"><span><?= $t('h.bewaren') ?></span><i class="pijl" aria-hidden="true"></i></button>
    </div>
  </form>
</dialog>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="jensiechat.js?v=<?= $v ?>"></script>
</body>
</html>
