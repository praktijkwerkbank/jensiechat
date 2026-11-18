# Installatiepad Jensiechat (basisversie): het draaiboek voor Claude

Dit is wat Claude per fase zegt en vraagt, wat de gebruiker doet, waar het misgaat en wanneer een fase klaar is. De struikelblokken bij Meta zijn echt gebeurd bij de eerste installatie door de maker (november 2026).

Vaste regels: een vraag tegelijk; nooit sleutels in de chat; de gebruiker logt zelf in; niets live zonder een duidelijk ja; vraag om een schermafbeelding als een scherm er anders uitziet, met de zin "Zorg dat er geen token, geheim of wachtwoord op staat."

Elke fase eindigt met drie regels: **Wat er nu werkt**, **Zo zie je het zelf**, **Volgende stap**. Daarna `voortgang.md` bijwerken.

---

## Fase 0: welkom en benodigdheden

**Claude zegt (kort, in eigen woorden):**
> Hiermee maak je je eigen automatische antwoord voor Instagram: iemand reageert met een woord onder je post en krijgt de link in zijn berichten, met een kort antwoord onder de reactie. Het draait op je eigen webhosting, onder je eigen naam, zonder abonnement.
> Eerlijk vooraf: je hebt webhosting met PHP nodig, je maakt zelf een app aan bij Meta (dat is het langste stuk), en jij bent verantwoordelijk voor de gegevens van mensen die reageren. Het kost twee tot drie uur, en dat mag in stukken. Zullen we eerst kijken of je alles hebt?

Daarna `benodigdheden.md`, punt voor punt, een vraag per bericht. Ontbreekt iets: eerst daarmee helpen.

- **Wat er nu werkt:** nog niets geïnstalleerd; je weet dat je alles hebt.
- **Zo zie je het zelf:** elk punt in `voortgang.md` staat afgevinkt.
- **Volgende stap:** keuzes maken (fase 1).

## Fase 1: keuzes

Een voor een: de naam van het Instagram-account; het adres van de website; waar Jensiechat komt (een map `jouwpraktijk.nl/chat` of een subdomein `chat.jouwpraktijk.nl`); de taal (`nl` of `en`); een naam voor het dashboard (geen "Insta", "IG", "Facebook" of "Meta" erin) en een accentkleur (de hoofdkleur van de site werkt meestal).

- **Wat er nu werkt:** de keuzes liggen vast.
- **Zo zie je het zelf:** ze staan in `voortgang.md`.
- **Volgende stap:** hosting klaarzetten.

## Fase 2: hosting keuren en uploaden

1. **Map of subdomein.** De gebruiker maakt in het paneel van de host een map in de webmap (vaak `public_html/chat`) of een subdomein. Bij een subdomein: in het paneel ook het (gratis) certificaat aanzetten; dat kan tot een uur duren.
2. **Uploaden.** De inhoud van `code/jensiechat/` in die map zetten. Het makkelijkst: de map als zip uploaden in de bestandsbeheerder en daar uitpakken. Controle: in de map staan `index.php`, `lib.php`, `keuring.php` en de mappen `r`, `taal` en `data`. Verborgen bestanden (`.htaccess`) moeten mee; in sommige bestandsbeheerders staat "verborgen bestanden tonen" uit.
3. **Keuring.** Open `https://<adres>/keuring.php`. Elk punt is groen, oranje of rood, met wat je doet. Rood eerst oplossen. Belangrijkst: "Afscherming met .htaccess" en "Map voor de gegevens"; de database mag nooit via internet op te halen zijn.
4. **config.php.** In de bestandsbeheerder `config.voorbeeld.php` kopiëren. Veiligst: de kopie `jensiechat-config.php` noemen en een map boven de webmap zetten (naast `public_html`, niet erin). Anders: `config.php` in de map van Jensiechat. Nu alleen invullen: `adres`, `dashboard_wachtwoord` (minstens 12 tekens, uit de wachtwoordmanager), `taal`, `naam`, `kleur`. De Meta-velden blijven leeg.
5. **Dashboard openen** op `https://<adres>/` en inloggen met het wachtwoord.

**Waar het misgaat:**
- Een witte pagina of "500": vaak een te oude PHP-versie. Kijk wat de keuring zegt.
- "Er zit een tikfout in config.php rond regel ...": een aanhalingsteken of komma vergeten. Elke regel ziet eruit als `'naam' => 'tekst',`.
- "Het adres moet met https:// beginnen": in `adres` staat `http://` of niets.

- **Wat er nu werkt:** het dashboard staat op je eigen site, achter je wachtwoord.
- **Zo zie je het zelf:** onder Gezondheid staat "Token: nog geen token ingevuld" (rood, dat klopt nu) en "Plek van je gegevens" groen of oranje.
- **Volgende stap:** de app bij Meta (fase 3, het langste stuk).

## Fase 3: de app bij Meta

**Claude zegt vooraf:** "Dit is het langste stuk, reken op drie kwartier tot anderhalf uur. Je doet alles in je eigen browser, waar je al bij Facebook bent ingelogd. Ik zeg per scherm wat je zoekt. Meta vraagt soms om je wachtwoord of een code; dat is normaal. Ziet een scherm er anders uit, stuur dan een schermafbeelding, zonder sleutels erop."

De route heet bij Meta "Instagram API met Facebook-login" (ook Unified login). De route "Instagram-login" werd voor nieuwe apps niet meer aangeboden toen de maker installeerde, ook al stond hij nog in oudere uitleg.

### 3.1 Voorwaarden
Zoek op business.facebook.com, Bedrijfsinstellingen, onder Accounts: staan de pagina en het Instagram-account in je portfolio? Ben je beheerder? Staat tweestapsverificatie aan? Zo niet: eerst `benodigdheden.md` punt 2 en 3.

### 3.2 Ontwikkelaarsaccount
Ga naar developers.facebook.com en log in. Is dit de eerste keer, dan registreert Meta je als ontwikkelaar (een paar schermen, soms een telefooncode). Je zoekt een scherm met "Mijn apps".

**Mis:** Meta eist een identiteitscontrole en blijft hangen. Dat gebeurt in een onbekende browser of in een ingebouwd venster. Gebruik je eigen, vertrouwde browser.

### 3.3 De app maken
"App maken". Meta stelt vragen over waarvoor de app is; je zoekt de keuze die met Instagram of met bedrijven te maken heeft. Het soort app is **Bedrijf** (Business). Naam: je dashboardnaam. Koppel de app aan je bedrijfsportfolio.

**Mis:** het portfolio staat niet in de lijst of blijft grijs. Kijk of je beheerder bent van het portfolio. Lukt het niet in het scherm van de app, probeer het andersom: Bedrijfsinstellingen, Accounts, Apps, app toevoegen met het app-ID. Bij de maker lukte de koppeling alleen in zijn eigen browser.

### 3.4 Het product Instagram
In het menu van de app zoek je "Producten toevoegen" of een lijst met use cases. Kies Instagram, en daarbinnen de instelling **met Facebook-login** (niet "met Instagram-login"). Zet de rechten `instagram_basic`, `instagram_manage_comments` en `instagram_manage_messages` aan.

**Mis:** het sjabloon zet extra rechten aan (zoals `instagram_content_publish`, `ads_read`, `ads_management`). Jensiechat gebruikt die niet. Laten staan mag zolang de app in ontwikkeling is.

### 3.5 Je Instagram-account als tester
In de app: App-rollen, Rollen, mensen toevoegen, rol Instagram-tester, je praktijkaccount invullen. Daarna accepteren in Instagram: op de **website** instagram.com, Instellingen, Apps en websites, Testeruitnodigingen. Die staat niet altijd in de app op de telefoon.

**Mis:** de uitnodiging blijft "in afwachting": je bent op instagram.com met een ander account ingelogd, of je zoekt in de telefoon-app.

### 3.6 Systeemgebruiker en token
Een token van een systeemgebruiker verloopt niet. Een token uit het app-dashboard verloopt na 60 dagen; die willen we niet.
1. business.facebook.com, Bedrijfsinstellingen, Gebruikers, Systeemgebruikers: een systeemgebruiker toevoegen, rol beheerder.
2. Bij die systeemgebruiker "Middelen toewijzen" (of iets dergelijks): de app, de pagina en het Instagram-account, met volledige controle.
3. "Token genereren": kies de app, vink `instagram_basic`, `instagram_manage_comments` en `instagram_manage_messages` aan. Staan `pages_show_list` of `business_management` erbij en twijfel je, vink ze dan ook aan; ze geven alleen leesrechten op je eigen pagina en portfolio.
4. Het token direct in je wachtwoordmanager zetten. **Niet in de chat plakken.** Meta laat het maar een keer zien.

**Mis:**
- Het token is gemaakt voordat de systeemgebruiker toegang had tot het Instagram-account: dan ziet Jensiechat geen account. Opnieuw genereren na het toewijzen.
- Token weg: geen probleem, maak een nieuw.

### 3.7 App-ID en app-geheim
In de app: App-instellingen, Basis. Het **app-ID** (alleen cijfers) staat bovenaan. Het **app-geheim** staat erachter met een knop "Tonen" (Meta vraagt je wachtwoord). Beide in je wachtwoordmanager.

**Mis:** token en app-geheim door elkaar halen. Het token komt van de systeemgebruiker en is lang; het app-geheim komt uit de app en is 32 tekens.

### 3.8 Toegang tot berichten in Instagram
In de Instagram-app op je telefoon, ingelogd als je praktijkaccount: Instellingen, Berichten en antwoorden op verhalen, Berichtbeheer of Verbonden tools: "Toegang tot berichten toestaan" aan. De naam en plek verschillen per versie; je zoekt de schakelaar die apps van buiten toegang geeft tot je berichten. Staat hij uit, dan geeft Meta geen duidelijke fout; er komt gewoon niets aan.

### Als een sleutel lekt
Plakt iemand een token, app-geheim of wachtwoord in de chat of op een schermafbeelding, dan geldt die als gelekt:
- **token:** maak bij de systeemgebruiker een nieuw token en trek het oude in (bij de systeemgebruiker, tokens beheren of intrekken);
- **app-geheim:** App-instellingen, Basis, het geheim opnieuw instellen ("Reset");
- **dashboardwachtwoord:** kies een nieuw in `config.php`.
Zet het nieuwe in `config.php`.

- **Wat er nu werkt:** de app bestaat en je hebt een token, app-ID en app-geheim, zonder dat ze in de chat stonden.
- **Zo zie je het zelf:** bij Systeemgebruikers zie je je systeemgebruiker met de app, de pagina en het Instagram-account eronder; in je wachtwoordmanager staan de drie waarden.
- **Volgende stap:** de sleutels in `config.php` en de cronjob (fase 4).

## Fase 4: sleutels in config.php en de cronjob

1. In de bestandsbeheerder `config.php` openen en invullen: `meta_app_id`, `meta_app_geheim`, `meta_token`, en een zelfbedacht `webhook_controlewoord` (bijvoorbeeld twintig willekeurige letters en cijfers uit de wachtwoordmanager). Opslaan.
2. Dashboard, Gezondheid, **Nu controleren**. "Token" en "Instagram-account" worden groen, met de naam van je account.
3. Gezondheid laat bij "Eigen controle" de opdracht voor de cronjob zien. In het paneel van de host een cronjob maken die **elke minuut** draait, met die opdracht. Heet PHP bij de host anders (bijvoorbeeld een pad met een versienummer), dan het pad van de host gebruiken.
4. Na twee minuten: Gezondheid opnieuw laden.

**Mis:** zie `als-je-dit-ziet.md`.

- **Wat er nu werkt:** Jensiechat praat met Meta en kijkt elke minuut.
- **Zo zie je het zelf:** onder Gezondheid zijn Token, Instagram-account en Eigen controle groen.
- **Volgende stap:** naar keuze de webhook (fase 5), anders de eerste automation (fase 6).

## Fase 5: webhook (naar keuze)

In de app bij Meta: Instagram, Webhooks (soms onder de use case). De callback-URL staat in Gezondheid bij "Meldingen van Meta (webhook)". Het controlewoord (bij Meta: Verify token) is het `webhook_controlewoord` uit `config.php`; Gezondheid laat het niet zien. Abonneren op `comments`. Daarna in Gezondheid **Meldingen aanzetten**.

Eerlijk zeggen: zonder App Review stuurt Meta meldingen over reacties meestal alleen voor accounts met een rol op de app. "Nog geen melding ontvangen" is dus normaal. De cronjob doet het werk; met de webhook komt een reactie van een tester soms sneller binnen.

**Mis:** Meta zegt dat het adres niet te verifiëren is. Controlewoord met een spatie erachter gekopieerd, geen geldig certificaat, of het adres is afgeschermd. Het adres zelf openen in de browser moet "nee" geven, geen foutpagina van de host.

- **Wat er nu werkt:** Meta kent je webhook-adres.
- **Zo zie je het zelf:** in het logboek staat "Meta heeft het webhook-adres gecontroleerd".
- **Volgende stap:** de eerste automation.

## Fase 6: de eerste automation, in testmodus

Een voor een vragen: het trefwoord (kort, een woord); de link; voor welke post (een post, alle posts, of "de volgende post die ik plaats").

Claude schrijft een voorstel in de toon van de praktijk: het bericht (boven knoppen hooguit 610 tekens; zonder knoppen hooguit 1000 bytes), de knoptekst (hooguit 20 tekens), drie publieke antwoorden. Controleren met "Probeer een reactie".

Bewaren met **Aan** en **Testmodus** aan, met het tweede account in de testlijst. Dan reageert de gebruiker vanaf het tweede account met het trefwoord onder de post.

Controle: binnen ongeveer een minuut komt het privébericht binnen op het tweede account, staat het publieke antwoord onder de reactie, en na een tik op de knop staat bij Activiteit "Geklikt". Daarna, na een duidelijk ja: testmodus uit.

Advies: zeg in je post wat mensen krijgen en welk woord ze typen; geen volg-eis; blijf zelf ook persoonlijk reageren; staat een andere dienst (zoals ManyChat) op dezelfde post aan, zet die eerst uit, want Meta staat maar een privébericht per reactie toe.

- **Wat er nu werkt:** iedereen die het trefwoord typt, krijgt het bericht.
- **Zo zie je het zelf:** onder Activiteit staat je testreactie met "Bericht met knop verstuurd", "Publiek antwoord" en "Geklikt".
- **Volgende stap:** de privacytekst (fase 7).

## Fase 7: privacy

Claude vult `code/privacy/privacy-tekst.nl.md` in met de gegevens van de praktijk. De gebruiker leest het, past het aan en zet het op de privacypagina. Wijs op "Gegevens van iemand wissen" onder Gezondheid voor verzoeken, en op de verwerkersovereenkomst met de host (vaak een vinkje of download in het account). Zeg dat dit geen juridisch advies is.

- **Wat er nu werkt:** je vertelt bezoekers wat er met hun gegevens gebeurt.
- **Zo zie je het zelf:** de tekst staat op je privacypagina.
- **Volgende stap:** afronden (fase 8).

## Fase 8: klaar en onderhoud

Meegeven: eens per week naar Gezondheid kijken; als iets stilvalt, worden reacties jonger dan 7 dagen alsnog beantwoord zodra het weer werkt; eens per jaar de privacytekst nalopen en het dashboardwachtwoord vervangen. In `voortgang.md` noteren dat de installatie klaar is.

Bijwerken: nieuwe code over de oude map heen uploaden. `config.php` en de datamap blijven staan. Nooit `config.php` overschrijven met `config.voorbeeld.php`.

Stoppen: automations uit, cronjob weg, de map en de datamap wissen, de app bij Meta verwijderen, privacytekst aanpassen.
