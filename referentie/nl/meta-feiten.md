# Wat Meta toestaat (stand november 2026)

Bron: de documentatie van Meta voor het Instagram-platform (Private replies, Webhooks setup, Messaging API), opgehaald 16 en 17 november 2026, en de eerste installatie van de maker. Meta kan dit veranderen. Wat niet zeker is, staat erbij.

## Zeker, uit de documentatie
- **Een privébericht per reactie**, tot 7 dagen na de reactie (`POST /<instagram-id>/messages` met `recipient.comment_id`).
- **Een publiek antwoord** onder een reactie kan altijd (`POST /<reactie-id>/replies`).
- **Lengtes:** een bericht zonder knoppen hooguit 1000 bytes; de tekst boven knoppen hooguit 640 tekens; een knoptekst hooguit 20 tekens.
- **Het account moet professioneel en openbaar zijn.** De eigen reacties van het account komen ook binnen; Jensiechat slaat ze over.
- **Een token van een systeemgebruiker verloopt niet.** Een token uit het app-dashboard verloopt na 60 dagen.
- **Zonder App Review heeft een app ongeveer 200 aanroepen per uur.** Jensiechat leest het verbruik dat Meta in elk antwoord meldt en kijkt minder vaak als het oploopt.

## Gezien bij de eerste installatie, niet beloofd door Meta
- Het privébericht met knop, het publieke antwoord en het tellen van klikken werkten zonder App Review, ook voor een account zonder rol op de app, terwijl de app niet gepubliceerd was.
- Meldingen over reacties (webhook) kwamen zonder App Review alleen binnen voor accounts met een rol op de app. De documentatie zegt dat Advanced Access nodig is voor deze meldingen. Daarom kijkt Jensiechat zelf, elke 15 tot 60 seconden.
- Het dashboard van Meta meldt dat live gegevens App Review vragen. In de praktijk werkte het voor het eigen account. Als Meta dit afdwingt, is App Review ook voor de basisversie nodig.

## Wat nooit kan
- Iemand een bericht sturen die niet reageerde.
- Meer dan een privébericht per reactie. Beantwoordt een andere dienst (zoals ManyChat) dezelfde reactie al, dan weigert Meta het bericht van Jensiechat.
- Zien of iemand je volgt.
