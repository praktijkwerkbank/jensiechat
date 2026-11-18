<?php
// Jensiechat: de instellingen. Dit is een voorbeeld; Jensiechat leest het niet.
//
// Zo gebruik je het:
// 1. Kopieer dit bestand in de bestandsbeheerder van je host en noem de kopie config.php (in deze map).
//    Veiliger: noem de kopie jensiechat-config.php en zet hem een map boven je webmap
//    (naast public_html, niet erin). Jensiechat kijkt daar eerst.
// 2. Vul de waarden tussen de aanhalingstekens in. Laat de aanhalingstekens en de komma aan het eind van elke regel staan.
// 3. Sla op en open het dashboard. Klopt er iets niet, dan zegt het dashboard wat.
//
// Zet hier nooit iets in een chat, mail of schermafbeelding. Wie dit bestand heeft, kan berichten sturen namens je account.
return [

  // Het webadres van de map waar Jensiechat staat, zonder / aan het eind. Moet met https:// beginnen.
  // Bijvoorbeeld 'https://jouwpraktijk.nl/chat' of 'https://chat.jouwpraktijk.nl'
  'adres' => '',

  // Een wachtwoord voor het dashboard, minstens 12 tekens. Bedenk een lange zin of laat je wachtwoordmanager er een maken.
  'dashboard_wachtwoord' => '',

  // ── Meta (zie fase 3 in referentie/nl/installatiepad.md of referentie/en/installation-path.md in de skill) ──
  // Het app-ID: in je app bij Meta onder App-instellingen, Basis. Alleen cijfers.
  'meta_app_id' => '',

  // Het app-geheim: op dezelfde plek, achter "Tonen". 32 tekens. Dit is iets anders dan het token.
  'meta_app_geheim' => '',

  // Het token van de systeemgebruiker (Bedrijfsinstellingen, Systeemgebruikers, Token genereren). Een lange reeks tekens.
  'meta_token' => '',

  // Het nummer van je Instagram-account. Mag leeg: Jensiechat zoekt het zelf op met het token.
  'instagram_id' => '',

  // Een zelfbedacht controlewoord voor de webhook. Vul hetzelfde woord in bij Meta (Webhooks, Verify token).
  // Alleen letters en cijfers, bijvoorbeeld 20 willekeurige tekens.
  'webhook_controlewoord' => '',

  // ── Hoe het dashboard eruitziet ──
  // De taal van het dashboard en de standaardteksten: 'nl' (Nederlands) of 'en' (English).
  'taal' => 'nl',

  // De naam bovenin het dashboard. Bijvoorbeeld 'Annachat'.
  'naam' => 'Jensiechat',

  // De accentkleur van het dashboard, als #rrggbb. Bijvoorbeeld de hoofdkleur van je website.
  'kleur' => '#2e6be6',

  // Je logo (optioneel): zet een vierkant plaatje in deze map en vul hier de bestandsnaam in, bijvoorbeeld 'logo.png'.
  // Leeg = een rondje met de eerste letter van de naam.
  'logo' => '',

  // ── Alleen invullen als het nodig is ──
  // De homepage van je website, voor een korte link die niet (meer) bestaat. Leeg = het domein van 'adres'.
  'website' => '',

  // Tijdzone voor de grafieken en tijden.
  'tijdzone' => 'Europe/Amsterdam',

  // Waar de database komt. Leeg = een map jensiechat-data naast je webmap (aanbevolen).
  // Alleen invullen als je host dat niet toestaat, met het volledige pad, bijvoorbeeld '/home/jouwnaam/jensiechat-data'.
  'datamap' => '',

  // Werken de korte links niet (je server leest geen .htaccess)? Zet dit dan op false (zonder aanhalingstekens).
  'mooie_links' => true,

  // Het begin van de korte links. Leeg = adres + '/r'. Alleen nodig als je de map r/ ergens anders neerzet.
  'korte_link_adres' => '',

  // Niet wijzigen. Alleen bedoeld om te testen.
  'graph_adres' => 'https://graph.facebook.com/v27.0',

];
