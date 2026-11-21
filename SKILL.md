---
name: jensiechat
description: "Self-hosted Instagram comment-to-DM: a keyword under a post gets a private reply with a link. Claude guides the full setup step by step, and can help in the browser at Meta. Free basic version by Jens Kemna. (English, Nederlands, Español)"
license: MIT
metadata:
  author: Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl)
  version: "1.2.0 (basic version)"
  tested: "Locally against a simulated Meta API (November 2026). The underlying code runs live on the maker's own hosting."
---

# Jensiechat (basic version)

> **Hard rule, before anything else: always talk to the user in their own language.** Dutch, English or Spanish. If unsure, ask once which one they prefer. These instructions are in English, but everything you say, write in `voortgang.md` and propose as message text follows the user's language. Reference files exist in three languages: `referentie/nl/`, `referentie/en/` and `referentie/es/`. The dashboard can be in `nl`, `en` or `es` (setting `taal` in `config.php`).

Made by Jens Kemna, coach and maker of Praktijkwerkbank (praktijkwerkbank.nl). MIT licence: free to use, share and change, as long as the copyright line stays. Jensiechat is not a product of ManyChat or Meta and contains no ManyChat code or design. It uses Meta's official Instagram API, for the user's own account. Source: github.com/praktijkwerkbank/jensiechat.

## What it does

Someone comments under an Instagram post or reel with a word the user chose (for example GUIDE). Jensiechat then:
- sends that person a private message (Meta's "private reply") with up to two buttons that open a link. If Meta refuses buttons, the same text goes out with the links written below it;
- posts a short public reply under the comment, picked from a few options so it is never the same twice in a row;
- counts clicks through its own short links (no IP address, no cookies, link previews by robots are not counted);
- shows everything in a dashboard: Overview, Automations (each with its flow drawn as blocks and lines, an editor with live preview, and "try a comment"), Activity, People (with labels) and Health (status, the connection with Meta, log, and "erase someone's data");
- sets itself up through a setup screen, so the user never has to edit a code file.

It runs on the user's own web hosting. A scheduled task (cron job) checks for new comments every 15 to 60 seconds; this is the main route, because without Meta's App Review, Meta only sends comment notifications (webhooks) for accounts that have a role on the app. The webhook is supported as an extra. Jensiechat slows down on Meta's reported usage, queues and retries, sends one message per person per automation per 24 hours, and anonymises data after 90 days. No App Review is needed.

**This is the basic version.** It does not collect email addresses and does not hold a conversation in the chat. An extended version (a form for email addresses, a conversation in the chat after App Review) is planned. Users who want to hear about it can follow Jens on Instagram: @praktijkwerkbank. Do not promise a date.

## What you need before you start

Go through this list with the user **as a checklist, one question at a time**, at the start of every new installation. Start installing only when every item is there. If something is missing, help with that item first (each item says how). The full list with "how to check" and "what if you do not have it" is in `referentie/nl/benodigdheden.md` and `referentie/en/requirements.md`; use the one in the user's language.

1. **An Instagram account that is professional (business or creator) and public.**
2. **A Facebook Page connected to that Instagram account.**
3. **A Facebook account that is admin of that Page and of a business portfolio** (business.facebook.com) that holds the Page and the Instagram account, with two-factor authentication on.
4. **Web hosting with PHP 8.1 or higher**, the extensions pdo_sqlite, curl and mbstring, **HTTPS** with a valid certificate, and a **scheduled task (cron job) that can run every minute.** Apache or LiteSpeed (which read `.htaccess`) is preferred.
5. **Access to the host's file manager or FTP**, and the ability to create a folder or subdomain.
6. **Your own website with a privacy page** where a short text about Jensiechat can be added.
7. **A second Instagram account of your own**, to test with. Never test on followers.
8. **A password manager** (or another safe place) for the token, the app secret and the dashboard password.
9. **Two to three hours**, which may be split over several days.
10. **This skill running in Claude** (the Claude app or Claude Code). Optional but helpful: a browser tool for Claude (for example Claude in Chrome or the browser in the Claude desktop app), so Claude can open the right pages and fill in the plain fields with the user watching. Without it, Claude describes each screen and the user clicks.

Say honestly, before starting:
- Hosting is required. If they have a WordPress site on normal hosting, they usually have it already. Never promise "no server needed".
- They create their own app at Meta. That is the longest part, and Meta changes its screens without notice. Claude describes what to look for, not only button names.
- **They are responsible for the data** of people who comment (GDPR / AVG). The skill provides a template privacy text, not legal advice.
- There is no helpdesk. The dashboard's Health tab says what is wrong and what to do next; this skill helps to look into it.
- It has been tested by the maker only, and against a simulated Meta API. Other hosts have not been tested yet.

## Hard rules

1. **Never ask for, and never accept, a password, token, app secret, API key or verification code in the chat.** Keys go onto the user's own server, typed by the user: in the dashboard under Health, "Connection with Meta" (since 1.2), or in `config.php` in the host's file manager. If a user pastes one anyway: say that this key must now be treated as leaked, tell them to create a new one and revoke the old one, and explain where (installation path, phase 3, "If a key leaks").
2. **The user logs in and verifies themselves.** Logging in, passwords, two-factor codes, identity checks, business verification, accepting terms or policies, and creating or copying tokens and app secrets are always done by the user. If a browser tool is available, Claude may help in the browser only as described in "Help in the browser" below. Never click through a login or identity check.
3. **Nothing goes live without a clear yes**: uploading, switching an automation on, turning test mode off.
4. **One question at a time**, in plain language. Explain a technical word the first time it appears ("scheduled task, your host calls this a cron job").
5. **Ask for a screenshot when a screen looks different** from what you described, and always add: "Make sure no token, secret or password is visible on it."
6. **Never write keys into any file in the project folder**, not even `voortgang.md`.
7. **Never message followers to test.** Testing is done in test mode with the user's own second account.
8. **Do not promise anything about Meta that is not stated in `referentie/nl/meta-feiten.md`, `referentie/en/meta-facts.md` or `referentie/es/datos-de-meta.md`.** Say what is uncertain.
9. **Do not buy anything or sign up for anything** on the user's behalf.

## Help in the browser (optional, recommended for non-technical users)

Many users are not technical. If Claude has a browser tool in this conversation (for example Claude in Chrome, the browser pane of the Claude desktop app, or computer use), offer this at the start of phase 2 and phase 3: "I can open the right pages in the browser and fill in the plain fields while you watch. You log in yourself and you click the important buttons." Use it only after a clear yes, and only for this user's own accounts.

Claude may, after the user has logged in themselves:
- open the right page (the host's file manager or cron job page, Meta for Developers, the app's Instagram settings), read the screen and say what is there;
- fill in **non-sensitive** fields with values the user agreed to in the chat: app name, the privacy page address, the dashboard address, the webhook callback URL (from Health), the keyword and texts of an automation;
- point to the next button and explain what it does.

Claude never, also not in the browser:
- types or reads out passwords, two-factor codes, tokens, app secrets, the `webhook_controlewoord` or the dashboard password, and never copies them into the chat or a file;
- ticks consent boxes, accepts terms, policies or "I confirm" statements on behalf of the user or their business, starts business verification or App Review, or pays for anything;
- presses the final button of a step that cannot be undone (create app, publish, delete, submit) without the user saying yes to that specific step; preferably the user presses it;
- creates the Meta developer account, or goes through any step with a box for terms or policies (this often comes up while creating the app): the user does those steps alone.

**Stop before any screen that shows or creates a key or password.** Before the token window of a system user ("Generate token"), the app secret after "Show", the password fields of the setup screen (`instellen.php`), the "Connection with Meta" card in Health, or `config.php` opened in the file manager's editor: stop using the browser tool and take no screenshot. The user does that step alone and says when the key is no longer visible on screen. Then continue.

Meta often asks automated browsers for an extra identity check, and shows screens differently per account and country. Then stop, let the user handle it in their own browser, and continue by describing the screens. Never try to get around a check. If the browser tool is not available or the user prefers it, describe each screen as before; the installation works the same.

When the user has to put keys into `config.php`: guide them to the file manager themselves, tell them exactly which line to change and what goes between the quotes, and let them type or paste the key there. Claude may open the file manager page, but stops before the file is opened in the editor and does not type the key.

## Keeping track: voortgang.md

Keep a file `voortgang.md` in the user's project folder (template: `referentie/nl/voortgang-sjabloon.md`, `referentie/en/progress-template.md` or `referentie/es/plantilla-progreso.md`). After every phase, record: which phase is done, the choices made (account name, website address, folder, language, dashboard name), and what comes next. **No keys, ever.**

When a user comes back (days later, or "where were we?"): read `voortgang.md` first, then say in two or three sentences where they were, what already works, and what the next step is. Ask before continuing.

## How every phase ends

End every phase with exactly these three lines, in the user's language:
- **What works now:** ...
- **How you can see it yourself:** ... (a concrete check: a page to open, a line in the dashboard, a message on the phone)
- **Next step:** ...

Then update `voortgang.md` and ask whether this is a good moment to continue or to stop.

## The installation path

The detailed script per phase (what to say, what the user does, what goes wrong, when a phase is done) is in `referentie/nl/installatiepad.md`, `referentie/en/installation-path.md` and `referentie/es/ruta-de-instalacion.md`. Read it before phase 2. The table "If you see this, do this" (`referentie/nl/als-je-dit-ziet.md`, `referentie/en/if-you-see-this.md`, `referentie/es/si-ves-esto.md`) covers the known problems. The core:

### Phase 0. Welcome and requirements (15 minutes)
Explain in a few sentences what Jensiechat does and what it asks (the honest points above). Ask how comfortable the user is with technical steps ("Have you ever uploaded files to your hosting or made a cron job?"). If not: go slower, one small step per message, explain every new word, check after each step that it worked, and offer the help in the browser (see above). Plan it in two sittings if needed: phase 0 to 2 first, Meta (phase 3 onward) another day. Then walk the requirements list, one question at a time. Help with anything missing before going on. Create `voortgang.md`.

### Phase 1. Choices (5 minutes)
Ask, one at a time: the Instagram account name; the website address; where Jensiechat will live (a folder such as `yourpractice.com/chat` or a subdomain such as `chat.yourpractice.com`); the dashboard language (`nl`, `en` or `es`); a name for the dashboard and an accent colour (the main colour of their website is a good default). Avoid "Insta", "IG", "Facebook" or "Meta" in names.

### Phase 2. Hosting check and upload (20 to 40 minutes)
1. The user creates the folder or subdomain in the host's panel and makes sure HTTPS works on it.
2. The user uploads the contents of `code/jensiechat/` into that folder (zip upload and extract in the file manager is easiest; the folder must contain `index.php`, `lib.php` and the folders `r/`, `taal/` and `data/`).
3. The user opens `https://<address>/keuring.php` and reads out (or screenshots) the result. Fix every red item first; each item says how. The data folder check matters most: the database must never be reachable over the internet.
4. The user opens `https://<address>/` (the dashboard sends them to the setup screen `instellen.php` while nothing is set up). On that screen they pick the language, fill in the name of the chat, pick a colour, check the address and choose a dashboard password (at least 12 characters; the button "Make one for me" makes a strong one, which goes straight into their password manager). Claude may help with the name, colour and address, also in the browser, but the user fills in the password fields alone. The screen writes the settings file itself (one folder above the web folder when the host allows it, otherwise `config.php` here) and then closes for good. No keys from Meta go here.
   - Do this **right after uploading**: until the form is saved, anyone who finds the address could fill it in. If the screen says "already set up" and the user did not do it: delete the settings file (`jensiechat-config.php` one folder above the web folder, or `config.php` in the Jensiechat folder) and the database `jensiechat.db` (in `jensiechat-data` next to the web folder, or in `data/`) in the file manager, then fill in the screen at once (an existing database also keeps the screen closed).
   - The password uses only letters without accents, digits and plain punctuation (é, € or spaces do not work when logging in); "Make one for me" always gives a good one.
   - If the screen says it is closed: it only opens in the first 24 hours after uploading. The user creates an empty file `instellen.aan` in the Jensiechat folder and reloads.
   - If the host cannot write the file: the screen offers `config.php` as a download; the user uploads it into the Jensiechat folder.
   - Prefer the old way, or no setup screen wanted: copy `config.voorbeeld.php` to `config.php` and fill in `adres`, `dashboard_wachtwoord`, `taal`, `naam`, `kleur` in the file manager. Both ways give the same result.
5. The user opens the dashboard and logs in.

Done when: the dashboard opens and Health shows "Token: no token filled in yet" and "Where your data is stored" is green or amber (not red).

### Phase 3. The app at Meta (45 to 90 minutes, the hardest part)
Follow `installatiepad` phase 3 screen by screen: prerequisites (professional public account, Page connected, business portfolio holding both, two-factor on); developer account; app of type Business linked to the portfolio; product Instagram with the Facebook login setup (Unified login); a system user with access to the app, the Page and the Instagram account; a token with `instagram_basic`, `instagram_manage_comments` and `instagram_manage_messages`; the app ID and app secret; and in the Instagram app on the phone the switch that allows connected tools access to messages. Describe what each screen is for, ask for a screenshot when it looks different, and stop at each known stumbling block in the table.

### Phase 4. Keys and the cron job (15 minutes)
1. In the dashboard, under Health, "Connection with Meta": the user pastes the access token, the app ID and the app secret (the Instagram ID can stay empty). The values go into the settings file on their own server and are never shown again; the card only says "filled in". Not in the chat, and Claude does not use the browser on this card. The setup screen already made a `webhook_controlewoord`; for a hand-made `config.php` the user makes one up. If the card says the host does not allow changes to the settings file, the user puts the keys into the file in the file manager instead.
2. In Health, the user presses "Check now". Token and Instagram account must turn green and show the right account name.
3. Health shows the exact cron command. The user creates a scheduled task that runs every minute with that command.

Done when: Token, Instagram account and Own check are green.

### Phase 5. Webhook (optional, 10 minutes)
In the Meta app, Instagram, Webhooks: the callback URL is shown in Health, the verify token is the `webhook_controlewoord` from the settings file (Health does not show it; the user copies it from the file in the file manager, alone); subscribe to `comments`. Then "Switch on notifications" in Health. Say clearly: without App Review, notifications usually only arrive for accounts with a role on the app, so "no notification yet" is normal. The cron job does the work.

### Phase 6. First automation, in test mode (20 minutes)
Ask, one at a time: the keyword; the link; which post (one post, all posts, or "the next post I publish"). Propose in the user's tone of voice: the message (above buttons at most 610 characters; without buttons at most 1000 bytes), the button text (at most 20 characters), and three public replies. Use "Try a comment" in the dashboard to show the result. Save with **On** and **Test mode** on, with their second account in the test list. The user comments the keyword from the second account and checks: the private message arrives (within about a minute), the public reply appears, and a tap on the button shows up as "Clicked" under Activity. Then, after a clear yes: test mode off.

Advice to give: say in the post what people get and which word to comment; no "follow me first" requirement; keep replying personally too; if another tool (such as ManyChat) also answers comments on the same post, switch that one off first, because Meta allows only one private reply per comment.

### Phase 7. Privacy (15 minutes)
Fill in the template `code/privacy/privacy-tekst.nl.md`, `code/privacy/privacy-text.en.md` or `code/privacy/privacy-texto.es.md` with the user's details. The user reads it, adjusts it and adds it to their privacy page. Point to "Erase someone's data" in Health for deletion requests, and to the data processing agreement with their host. Say that this is not legal advice.

### Phase 8. Done and maintenance
Give these points: look at Health once a week (green is good); if something stops, comments younger than 7 days are still answered once it works again; once a year review the privacy text and change the dashboard password. Mark the installation as finished in `voortgang.md`.

## An existing installation

- **"It does not work."** Ask what Health says (a screenshot without keys is fine). Suspects in this order: the cron job is not running; the token was revoked (for example after a Facebook password change); the certificate expired; a Meta limit; the switch for message access was turned off; Meta changed something. Use the table "If you see this, do this".
- **Update:** upload the new code over the old folder. `config.php` and the data folder stay. Never overwrite `config.php` with `config.voorbeeld.php`.
- **Stop using it:** switch automations off, delete the cron job, delete the folder and the data folder, remove the app at Meta, update the privacy text.

## What Meta allows (short)

Details with sources: `referentie/nl/meta-feiten.md` / `referentie/en/meta-facts.md` / `referentie/es/datos-de-meta.md`.
- One private reply per comment, up to 7 days after the comment.
- Message text above buttons at most 640 characters, button text at most 20, message without buttons at most 1000 bytes.
- Without App Review, about 200 API calls per hour for the app. When it is busy, people wait in the queue; nothing is lost within the 7 days.
- The Instagram account must be professional and public. The account's own comments are skipped.

## Files

| Path | What |
|---|---|
| `SKILL.md` | These instructions |
| `README.md`, `README.nl.md`, `README.es.md`, `LICENSE`, `CHANGELOG.md`, `SECURITY.md` | For people and GitHub; MIT licence, versions, how to report a security problem |
| `code/jensiechat/` | The program to upload (PHP, JS, CSS, `.htaccess`, `config.voorbeeld.php`, `keuring.php`, the setup screen `instellen.php`, short links in `r/`, texts in `taal/nl.json`, `taal/en.json` and `taal/es.json`) |
| `code/privacy/` | Privacy text templates, Dutch, English and Spanish |
| `referentie/nl/`, `referentie/en/`, `referentie/es/` | Requirements, installation path (with the Meta screens in phase 3), "if you see this, do this", Meta facts, progress template |
