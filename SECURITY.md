# Security

Jensiechat runs on your own hosting and holds keys for your Instagram account. A security problem in the code can affect everyone who installed it, so please report it privately first.

## Reporting a problem

Use GitHub's private vulnerability reporting for this repository: open the **Security** tab at github.com/praktijkwerkbank/jensiechat and choose **Report a vulnerability**. Do not open a public issue for a security problem.

Please include: what you found, how to reproduce it, and which version you use (the number is `JC_VERSIE` in `code/jensiechat/lib.php`). Never include a real token, app secret or password.

This is a free project by one maker, without a helpdesk. Reports are read and answered as soon as possible, without a guaranteed response time.

## Supported versions

Only the latest release gets security fixes.

## If one of your own keys leaked

That is not a problem in the code, and you can solve it yourself right away: create a new token at Meta and revoke the old one, reset the app secret, or choose a new dashboard password in `config.php`. The steps are in `referentie/en/installation-path.md` (phase 3, "If a key leaks") and `referentie/nl/installatiepad.md`.

## Nederlands

Een beveiligingsprobleem gevonden? Meld het besloten via het tabblad **Security** van deze repository op GitHub (**Report a vulnerability**), niet in een openbare issue. Zet er nooit een echt token, app-geheim of wachtwoord bij.
