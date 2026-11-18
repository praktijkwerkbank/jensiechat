# Wat je nodig hebt voor je begint

Loop deze lijst na voordat je iets installeert. Per punt: wat het is, hoe je kijkt of je het hebt, en wat je doet als je het niet hebt. Claude vraagt ze een voor een met je na.

## 1. Een Instagram-account dat zakelijk of maker is, en openbaar

**Wat het is.** Instagram kent persoonlijke, zakelijke en makersaccounts. Alleen bij zakelijke en makersaccounts mag een app reacties lezen en berichten sturen. Het account moet openbaar zijn; bij een privéaccount stuurt Meta niets door.

**Zo kijk je.** In Instagram: je profiel, het menu rechtsboven, Instellingen. Staat er een kopje voor professionele accounts (de naam wisselt per versie, bijvoorbeeld "Accounttype en tools"), dan heb je een professioneel account. Bij Accountprivacy moet "Privéaccount" uit staan.

**Heb je het niet.** Omzetten is gratis en gaat in Instagram zelf: Instellingen, Accounttype en tools, Overstappen naar professioneel account. Je volgers en posts blijven.

## 2. Een Facebook-pagina die aan dat Instagram-account gekoppeld is

**Wat het is.** Jensiechat gebruikt de Instagram-koppeling van Meta via Facebook (Meta noemt dit Unified login). Daarvoor moet je Instagram-account aan een Facebook-pagina van je praktijk hangen.

**Zo kijk je.** In Instagram: Profiel bewerken. Staat daar een pagina bij "Pagina", dan is hij gekoppeld. Of op Facebook, op je pagina: Instellingen, Gekoppelde accounts.

**Heb je het niet.** Maak op Facebook een pagina voor je praktijk (gratis). Koppel daarna je Instagram-account aan die pagina, via Profiel bewerken in Instagram of via het Accountcentrum van Meta.

## 3. Een Facebook-account met beheerrechten, in een bedrijfsportfolio

**Wat het is.** Je persoonlijke Facebook-account moet beheerder zijn van de pagina en van een bedrijfsportfolio op business.facebook.com (vroeger Bedrijfsmanager). In dat portfolio staan de pagina en het Instagram-account. Daar maak je later een "systeemgebruiker" aan: een technisch account waarvan het token niet verloopt. Tweestapsverificatie moet aan staan; Meta vraagt dat voor deze schermen.

**Zo kijk je.** Ga naar business.facebook.com en open Bedrijfsinstellingen. Zie je onder Accounts je pagina en je Instagram-account staan, en ben je bij Gebruikers, Personen zelf beheerder, dan zit het goed.

**Heb je het niet.** Maak op business.facebook.com een portfolio aan (gratis) en voeg daar je pagina en je Instagram-account aan toe. Zet tweestapsverificatie aan in de instellingen van je Facebook-account (Accountcentrum, Wachtwoord en beveiliging).

## 4. Webhosting met PHP, HTTPS en een geplande taak per minuut

**Wat het is.** Jensiechat is een klein programma in PHP dat dag en nacht op een server moet staan: Meta moet het kunnen bereiken en het moet elke minuut kunnen kijken. Precies nodig:
- PHP 8.1 of hoger, met de uitbreidingen pdo_sqlite (de database), curl (praten met Meta) en mbstring (tekst);
- HTTPS met een geldig certificaat op het adres waar Jensiechat komt;
- een geplande taak (bij hosts heet dat een cronjob) die elke minuut een PHP-bestand kan starten;
- bij voorkeur een Apache- of LiteSpeed-server, die `.htaccess` leest (voor de afscherming en de korte links). Bij de meeste gewone webhosting is dat zo.

Geen MySQL, geen Node, geen SSH nodig.

**Zo kijk je.** Heb je een WordPress-site op eigen hosting, dan heb je dit bijna altijd al. Zeker weten doe je het met `keuring.php` uit het pakket: upload hem en open hem in je browser, hij zegt per punt groen of rood. De cronjob kan de keuring niet zien: kijk in het paneel van je host of er "Cronjobs" of "Geplande taken" is en of je "elke minuut" kunt kiezen.

**Heb je het niet.** Staat je site bij een websitebouwer zonder eigen hosting (zoals Wix, Squarespace of Jimdo), dan kan Jensiechat daar niet draaien. Neem dan een klein hostingpakket erbij en laat een subdomein (bijvoorbeeld `chat.jouwpraktijk.nl`) daarheen wijzen; je site blijft waar hij is. Kies zelf de host; Claude kan uitleggen waar je op let, maar sluit niets voor je af. Kan je host geen taak per minuut draaien, vraag dan of dat wel kan, of kies een ander pakket.

## 5. Toegang tot de bestandsbeheerder of FTP van je host

**Wat het is.** Je moet bestanden kunnen uploaden, een zip uitpakken, een map of subdomein maken en een bestand (`config.php`) kunnen bewerken.

**Zo kijk je.** Log in bij je host. Zie je iets als "Bestandsbeheer", "File Manager" of FTP-gegevens, dan zit het goed.

**Heb je het niet.** Vraag je host om de inloggegevens van het paneel, of om FTP-gegevens. Heeft iemand anders je site gebouwd, vraag hem of haar om toegang voor jezelf.

## 6. Een eigen website met een privacypagina

**Wat het is.** Jij bent verantwoordelijk voor de gegevens van mensen die reageren (de AVG). Op je site moet staan wat je bewaart, waarom, hoe lang en hoe iemand het kan laten wissen. Daarvoor zit een sjabloon in het pakket.

**Zo kijk je.** Staat er op je site een privacyverklaring, en kun je die zelf aanpassen?

**Heb je het niet.** Maak een pagina "Privacy" op je site. Claude helpt met de tekst over Jensiechat; voor de rest van je privacyverklaring is een sjabloon van je beroepsvereniging of een jurist verstandig.

## 7. Een tweede Instagram-account van jezelf

**Wat het is.** Om te testen reageer je met het trefwoord vanaf een ander account dan je praktijkaccount. Jensiechat slaat reacties van je eigen praktijkaccount over, en je wilt niet testen op volgers.

**Zo kijk je.** Heb je een privéaccount, of kun je er een maken met een ander e-mailadres?

**Heb je het niet.** Maak in Instagram een extra account aan (Profiel, je naam bovenin, Account toevoegen).

## 8. Een wachtwoordmanager of een andere veilige plek

**Wat het is.** Je krijgt een token en een app-geheim van Meta, en je kiest een wachtwoord voor het dashboard. Die horen niet in een notitie, mail of chat.

**Zo kijk je.** Gebruik je al een wachtwoordmanager (bijvoorbeeld die van je browser of telefoon, of een losse app)?

**Heb je het niet.** De wachtwoordmanager van je telefoon of browser is genoeg.

## 9. Twee tot drie uur tijd, mag in stukken

**Wat het is.** Een schatting voor iemand die de accounts al heeft. Moet je nog een portfolio of hosting regelen, dan duurt het langer. Je kunt na elke fase stoppen; Claude houdt in `voortgang.md` bij waar je bent.

## 10. Deze skill in Claude

**Wat het is.** Claude loodst je erdoorheen: in de Claude-app of in Claude Code. Jij logt zelf in en klikt zelf, in je eigen browser waar je al bij Facebook bent ingelogd. Claude zegt per scherm wat je zoekt.

**Zo kijk je.** Als je dit leest via Claude, zit het goed.

---

**Klaar met de lijst?** Dan begint de installatie bij fase 1 van `installatiepad.md`.
