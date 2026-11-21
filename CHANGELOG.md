# Changelog

All notable changes to Jensiechat are listed here. The version number lives in one place in the code (`JC_VERSIE` in `code/jensiechat/lib.php`) and in the metadata of `SKILL.md`.

## 1.1.0 (2026-11-21)

- Spanish: the dashboard, the hosting check, the privacy text template, the README and the installation guides are now also in Spanish (set `taal` to `es` in `config.php`). Missing Spanish texts fall back to English. The Spanish was made with AI help and has not yet been reviewed by a native speaker.
- New look: on a laptop the dashboard has a sidebar with icons; on a phone the bottom bar shows icons, and the open section shows its name, so long words fit in every language. Everything still follows your own accent colour.
- Placeholder text in fields has more contrast. Explanations on the hosting check have a comfortable line length.
- Keywords match with or without accents (GUIA and GUÍA) also on hosting without the PHP intl extension.
- The description in the `SKILL.md` frontmatter is now quoted; it contained a colon that some skill loaders could not read.
- Installation for non-technical users: the skill asks how comfortable you are with technical steps and adapts its pace, and can help in the browser (open the right pages and fill in plain fields while you watch). Logging in, codes, keys, verification and accepting terms always stay with you.

## 1.0.2 (2026-11-21)

- Fixed occasional "database is locked" errors. A database statement that read one row stayed open; when the second cron job or the webhook wrote in the meantime, the next write failed immediately. Statements now close right after reading one row or value. Found and fixed in the maker's own installation on 21 November 2026.

## 1.0.1 (2026-11-18)

- Accounts on the test list of an automation are no longer held to the rule "the same person gets the same message at most once per 24 hours", so you can test your own keyword again right after an earlier test. The rule still applies to everyone else.

## 1.0.0 (2026-11-18)

First public release, the basic version.

- A keyword in a comment under an Instagram post or reel gets a private reply (with up to two buttons, or as plain text if Meta refuses buttons) and a public reply under the comment.
- Own check through a cron job as the main route, Meta's webhook as an extra.
- Short links that count clicks without IP address or cookie.
- Dashboard in Dutch and English: Overview, Automations, People, Activity, Health.
- Data is anonymised after 90 days; "Erase someone's data" for requests.
- Claude skill that guides the installation step by step, in Dutch and English.

Tested by the maker on his own hosting and locally against a simulated Meta API. Other hosts have not been tested yet.
