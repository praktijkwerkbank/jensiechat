# Als je dit ziet, doe dan dit

Bekende problemen bij installatie en gebruik. Vraag bij twijfel om een schermafbeelding, zonder token, geheim of wachtwoord erop.

## Hosting en dashboard

| Wat je ziet | Wat het is | Wat je doet |
|---|---|---|
| Witte pagina of "500 Internal Server Error" | Meestal een te oude PHP-versie | Open `keuring.php`. Staat PHP op rood: in het paneel van de host PHP 8.1 of hoger kiezen. |
| "Nog niet klaar: er is geen config.php" | Het instellingenbestand ontbreekt | `config.voorbeeld.php` kopiëren naar `config.php` (of naar `jensiechat-config.php` een map boven de webmap) en invullen. |
| "Er zit een tikfout in ... rond regel ..." | Een aanhalingsteken of komma te veel of te weinig | Die regel nakijken. Elke regel heeft de vorm `'naam' => 'tekst',` |
| "Het adres moet met https:// beginnen" | `adres` in config.php staat op http of is leeg | Het volledige adres met https:// invullen, zonder / aan het eind. |
| "Het dashboard_wachtwoord ... is te kort" | Minder dan 12 tekens | Een langer wachtwoord kiezen in config.php. |
| "Dit wachtwoord klopt niet" | Ander wachtwoord dan in config.php | Kopieer het uit je wachtwoordmanager. Let op spaties. |
| "Te vaak een verkeerd wachtwoord" | 20 foute pogingen in een kwartier | Een kwartier wachten. |
| "De PHP-uitbreiding pdo_sqlite staat uit" | SQLite staat uit bij de host | In het paneel bij PHP-uitbreidingen aanvinken, of de host vragen. |
| "Jensiechat kan nergens gegevens opslaan" | Geen schrijfrechten | Een map `jensiechat-data` naast de webmap maken, of bij `datamap` een pad invullen waar de server mag schrijven. |
| Gezondheid: "Plek van je gegevens" rood | De database is via internet op te halen | Niets aanzetten. Zet `datamap` in config.php op een map buiten de webmap, of vraag de host waarom `.htaccess` niet werkt. |
| Gezondheid: "Plek van je gegevens" oranje | De database staat binnen de webmap, wel afgeschermd | Werkt, maar beter: een map `jensiechat-data` naast `public_html`. |
| Een korte link geeft "Not Found" | De server leest `.htaccess` niet (vaak nginx) | In config.php `mooie_links` op `false` zetten. Nieuwe berichten krijgen dan links met `?s=`. |

## Meta

| Wat je ziet | Wat het is | Wat je doet |
|---|---|---|
| Meta vraagt om een identiteitscontrole en blijft hangen | Onbekende browser of ingebouwd venster | Je eigen, vertrouwde browser gebruiken waarin je al bij Facebook bent ingelogd. |
| Bij "App maken" staat je portfolio er niet bij, of is het grijs | Geen beheerder van het portfolio, of de koppeling lukt niet vanuit dit scherm | Kijk in Bedrijfsinstellingen of je beheerder bent. Probeer het andersom: Bedrijfsinstellingen, Accounts, Apps, app toevoegen met het app-ID. |
| Alleen "Instagram-login" te kiezen, geen Facebook-login | Ander scherm of andere volgorde | Zoek in de use case of instellingen naar "API-instelling met Facebook-login". Stuur een schermafbeelding. |
| De testeruitnodiging blijft "in afwachting" | Geaccepteerd op een ander account, of gezocht in de telefoon-app | Op instagram.com inloggen als je praktijkaccount: Instellingen, Apps en websites, Testeruitnodigingen. |
| Het token werkt niet (Gezondheid rood, code 190) | Verkeerd geplakt, verlopen of ingetrokken | Opnieuw kopiëren uit je wachtwoordmanager zonder spaties; anders een nieuw token maken bij de systeemgebruiker. |
| Token groen, maar "Instagram-account niet gevonden" | De systeemgebruiker heeft geen toegang tot het Instagram-account | In Bedrijfsinstellingen het Instagram-account toewijzen aan de systeemgebruiker en daarna een nieuw token maken. |
| Webhook-melding met een onjuiste handtekening geweigerd | Het app-geheim in config.php klopt niet | App-geheim opnieuw kopiëren (App-instellingen, Basis, Tonen). |
| Meta zegt dat het webhook-adres niet te verifiëren is | Controlewoord verkeerd, of geen geldig certificaat | Het woord opnieuw kopiëren, zonder spatie. Het adres in de browser openen: je moet "nee" zien, geen foutpagina. |
| "Nog geen melding ontvangen" bij de webhook | Normaal zonder App Review | Niets doen. De eigen controle vangt alle reacties op. |
| Fout over rechten bij versturen (code 10, 200 of 3) | Token mist een recht, of de schakelaar voor berichten staat uit | De drie rechten bij het token nalopen; in Instagram "Toegang tot berichten toestaan" aanzetten. |
| "(#4) Application request limit reached" of "Limiet van Meta" | Het uurbudget van de app is op | Wachten. Berichten staan in de wachtrij en gaan vanzelf. |

## Testen en gebruik

| Wat je ziet | Wat het is | Wat je doet |
|---|---|---|
| Reactie geplaatst, er gebeurt niets | Trefwoord komt niet overeen, automation staat uit, of je reageert met je praktijkaccount | "Probeer een reactie" in het dashboard gebruiken; reageren met je tweede account. |
| Activiteit: "Overgeslagen, testmodus" | Het account staat niet in de testlijst | Je tweede account bij Testaccounts zetten, met de juiste schrijfwijze. |
| Activiteit: "Kreeg dit bericht al in de laatste 24 uur" | Zelfde persoon, zelfde automation | Zo bedoeld. Test met een andere automation of wacht een dag. |
| Bericht komt na ongeveer een minuut | De eigen controle pakte het op | Normaal zonder App Review. |
| Bericht komt als tekst met de link eronder, zonder knop | Meta weigerde de knopvorm | Werkt. Gezondheid zegt bij "Knoppen" wat er gebeurde. |
| Activiteit: "Meta staat dit bericht niet toe" | Reactie ouder dan 7 dagen, of al beantwoord door een andere dienst | Met de hand beantwoorden. Een andere dienst op dezelfde post eerst uitzetten. |
| Gezondheid: "Eigen controle" rood | De cronjob draait niet | De cronjob in het paneel van de host nakijken; de opdracht staat in Gezondheid. Bij sommige hosts staat de uitvoer van de taak in het paneel. |
| Gezondheid: "Wachtrij" loopt op | Limiet of storing bij Meta | Wachten; kijk in het logboek. Reacties jonger dan 7 dagen gaan alsnog. |
