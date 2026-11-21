# Jensiechat (basisversie)

[English: README.md](README.md), [Español: README.es.md](README.es.md)

Je eigen automatische antwoord voor Instagram, op je eigen webhosting, zonder abonnement. Iemand reageert onder je post of reel met een woord dat jij kiest (bijvoorbeeld GIDS), en Jensiechat stuurt die persoon een privébericht met een knop naar je link, zet een kort antwoord onder de reactie en telt de kliks. In een dashboard zie je wat er gebeurde en of alles werkt.

Gemaakt door Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). Gratis, MIT-licentie. Geen product van ManyChat of Meta: het gebruikt de officiële Instagram API van Meta, voor je eigen account.

Deze map is ook een Claude-skill: Claude loodst je stap voor stap door de installatie. Bron: github.com/praktijkwerkbank/jensiechat.

## Wat het doet

- Automations op een trefwoord, voor een post of reel, voor alle posts, of voor "de volgende post die ik plaats".
- Een privébericht met tekst en hooguit twee knoppen. Weigert Meta de knoppen, dan gaat dezelfde tekst met de links eronder.
- Een publiek antwoord onder de reactie, wisselend uit een paar zinnen.
- Korte links die kliks tellen, zonder IP-adres of cookie; voorbeeldweergaven van robots tellen niet mee.
- Een eigen controle via een cronjob (de hoofdweg, want zonder App Review stuurt Meta meldingen over reacties alleen voor accounts met een rol op de app), plus de webhook van Meta voor als die wel komt.
- Remt af op het verbruik dat Meta meldt, pauzeert bij limieten, werkt met een wachtrij en probeert opnieuw, een bericht per persoon per automation per etmaal, anonimiseert na 90 dagen.
- Dashboard in het Nederlands of Engels: Overzicht, Automations (editor met voorbeeld), Activiteit, Mensen (met labels), Gezondheid (stand met volgende stap, logboek, gegevens van iemand wissen).

Niet in deze basisversie: e-mailadressen ophalen, een gesprek in de chat. Een uitgebreide versie komt later; volg @praktijkwerkbank op Instagram om het te horen.

## Wat je nodig hebt voor je begint

| Wat je nodig hebt | Zo kijk je | Heb je het niet |
|---|---|---|
| Een Instagram-account dat zakelijk of maker is, en openbaar | Instagram, Instellingen: een kopje voor professionele accounts; "Privéaccount" staat uit | Gratis omzetten: Instellingen, Accounttype en tools |
| Een Facebook-pagina die aan dat Instagram-account gekoppeld is | Instagram, Profiel bewerken, "Pagina" | Pagina maken en koppelen via Profiel bewerken of het Accountcentrum van Meta |
| Beheerrechten op die pagina en op een bedrijfsportfolio (business.facebook.com) met de pagina en het Instagram-account erin, met tweestapsverificatie | Bedrijfsinstellingen, Accounts en Gebruikers | Portfolio maken (gratis), pagina en account toevoegen, tweestapsverificatie aanzetten |
| Webhosting met PHP 8.1+, pdo_sqlite, curl, mbstring, HTTPS en een cronjob per minuut; liefst Apache of LiteSpeed | `keuring.php` uploaden en openen; in het paneel van je host zoeken naar "Cronjobs" | Een klein hostingpakket en een subdomein zoals `chat.jouwpraktijk.nl` |
| Toegang tot de bestandsbeheerder of FTP van je host | Inloggen bij je host | Vragen aan je host of je websitebouwer |
| Een eigen website met een privacypagina | Kun je je privacyverklaring zelf aanpassen? | Een privacypagina maken; er zit een sjabloon bij |
| Een tweede Instagram-account van jezelf, om te testen | | Een account toevoegen in Instagram |
| Een wachtwoordmanager | | Die van je telefoon of browser is genoeg |
| Twee tot drie uur, mag in stukken | | |
| Claude (de Claude-app of Claude Code) met deze skill | | Zie "Installatie" |

Volledige lijst: [referentie/nl/benodigdheden.md](referentie/nl/benodigdheden.md).

## Installatie

**Met Claude (aanbevolen).** Voeg deze map toe als skill (in Claude Code: zet hem in `~/.claude/skills/jensiechat`; in de Claude-app: upload de zip bij skills in de instellingen, als je abonnement skills ondersteunt). Vraag Claude daarna: "Installeer Jensiechat". Claude loopt eerst de benodigdheden met je na en gaat dan fase voor fase verder. Jij logt zelf in en klikt zelf; je sleutels komen nooit in de chat.

**Met de hand.** De stappen kort (uitgebreid in [referentie/nl/installatiepad.md](referentie/nl/installatiepad.md)):
1. Maak een map (bijvoorbeeld `public_html/chat`) of een subdomein met HTTPS.
2. Upload de inhoud van `code/jensiechat/`, ook de verborgen `.htaccess`-bestanden.
3. Open `https://<adres>/keuring.php` en los alles op wat rood is.
4. Kopieer `config.voorbeeld.php` naar `jensiechat-config.php` een map boven je webmap (of naar `config.php` in de map) en vul `adres`, `dashboard_wachtwoord` (12+ tekens), `taal` (`nl` of `en`), `naam` en `kleur` in.
5. Maak de app bij Meta (type Bedrijf, Instagram met Facebook-login), een systeemgebruiker met toegang tot de app, de pagina en het Instagram-account, en een token met `instagram_basic`, `instagram_manage_comments` en `instagram_manage_messages`. Zet in de Instagram-app "Toegang tot berichten toestaan" aan.
6. Zet `meta_app_id`, `meta_app_geheim`, `meta_token` en een `webhook_controlewoord` in het instellingenbestand. Open het dashboard, Gezondheid, Nu controleren.
7. Maak een cronjob die elke minuut draait, met de opdracht uit Gezondheid.
8. Naar keuze: de webhook bij Meta met het adres uit Gezondheid en het `webhook_controlewoord` uit je instellingenbestand, geabonneerd op `comments`.
9. Maak je eerste automation in testmodus, test hem vanaf je tweede account, en zet de testmodus daarna uit.
10. Zet de privacytekst uit `code/privacy/` op je privacypagina.

## Bijwerken

Upload de nieuwe code over de oude map heen. Je instellingenbestand en datamap blijven staan. Overschrijf je `config.php` nooit met `config.voorbeeld.php`.

## Verwijderen

Zet de automations uit, verwijder de cronjob, wis de map van Jensiechat en de datamap (`jensiechat-data` naast je webmap), verwijder de app bij Meta en pas je privacytekst aan.

## Privacy

Jensiechat bewaart de Instagram-gebruikersnaam en -ID, de reactie, de post, het tijdstip, of het bericht verstuurd is en of er op de link getikt is. Geen IP-adressen, geen cookies, geen profielfoto's. Na 90 dagen worden naam, ID en tekst gewist; de tellingen blijven. Het dashboard heeft "Gegevens van iemand wissen" voor verzoeken. De gegevens blijven op je eigen hosting, standaard buiten de webmap. Jij bent verantwoordelijk voor deze gegevens (AVG); in `code/privacy/` staat een sjabloontekst. Geen juridisch advies.

## Eerlijke grenzen

- Meta bepaalt de regels en verandert schermen. Getest in november 2026 door de maker, en tegen een nagebootste Meta API; andere hosts zijn nog niet getest.
- Zonder App Review komt een bericht binnen ongeveer een minuut, en heeft de app ongeveer 200 aanroepen per uur. Bij drukte wachten mensen in de rij.
- Er is geen helpdesk. Het tabblad Gezondheid zegt wat er mis is en wat de volgende stap is.

## Licentie en maker

MIT, zie [LICENSE](LICENSE). Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). Laat de regel "Gemaakt met Jensiechat van Jens Kemna, Praktijkwerkbank" in de voet van het dashboard staan.

Jensiechat is geen product van ManyChat of Meta en wordt niet door hen ondersteund. Instagram en Facebook zijn merken van Meta Platforms, Inc. ManyChat is een merk van de eigenaar.

Versies: [CHANGELOG.md](CHANGELOG.md). Een beveiligingsprobleem gevonden? Zie [SECURITY.md](SECURITY.md).
