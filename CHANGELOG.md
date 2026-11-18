# Changelog

All notable changes to Jensiechat are listed here. The version number lives in one place in the code (`JC_VERSIE` in `code/jensiechat/lib.php`) and in the metadata of `SKILL.md`.

## 1.0.0 (2026-11-18)

First public release, the basic version.

- A keyword in a comment under an Instagram post or reel gets a private reply (with up to two buttons, or as plain text if Meta refuses buttons) and a public reply under the comment.
- Own check through a cron job as the main route, Meta's webhook as an extra.
- Short links that count clicks without IP address or cookie.
- Dashboard in Dutch and English: Overview, Automations, People, Activity, Health.
- Data is anonymised after 90 days; "Erase someone's data" for requests.
- Claude skill that guides the installation step by step, in Dutch and English.

Tested by the maker on his own hosting and locally against a simulated Meta API. Other hosts have not been tested yet.
