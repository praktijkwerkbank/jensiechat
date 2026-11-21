# Jensiechat (basic version)

[Nederlands: README.nl.md](README.nl.md), [Español: README.es.md](README.es.md)

Your own automatic reply for Instagram, on your own web hosting, without a subscription. Someone comments a word you chose (for example GUIDE) under your post or reel, and Jensiechat sends them a private message with a button to your link, posts a short reply under the comment, and counts the clicks. A dashboard shows what happened and whether everything works.

Made by Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). Free, MIT licence. Not a product of ManyChat or Meta: it uses Meta's official Instagram API, for your own account.

This repository is also a Claude skill: Claude guides you through the installation, one step at a time. Source: github.com/praktijkwerkbank/jensiechat.

## What it does

- Keyword automations for one post or reel, for all posts, or for "the next post I publish".
- A private reply with text and up to two buttons. If Meta refuses buttons, the same text goes out with the links below it.
- A public reply under the comment, alternating between a few options.
- Short links that count clicks, without IP addresses or cookies; link previews by robots are not counted.
- Its own check through a cron job (the main route, because without Meta's App Review comment notifications only arrive for accounts with a role on the app), plus Meta's webhook for when it does arrive.
- Slows down on the usage Meta reports, pauses on limits, queues and retries, one message per person per automation per 24 hours, anonymises after 90 days.
- Dashboard in Dutch or English: Overview, Automations (editor with preview), Activity, People (with labels), Health (status with next steps, log, erase someone's data).

Not in this basic version: collecting email addresses, a conversation in the chat. An extended version is planned; follow @praktijkwerkbank on Instagram to hear about it.

## What you need before you start

| You need | How to check | If you do not have it |
|---|---|---|
| An Instagram account that is business or creator, and public | Instagram, Settings: a section for professional accounts; "Private account" is off | Switch for free: Settings, Account type and tools |
| A Facebook Page connected to that Instagram account | Instagram, Edit profile, "Page" | Create a Page and connect it via Edit profile or Meta's Accounts Center |
| Admin rights on that Page and on a business portfolio (business.facebook.com) holding the Page and the Instagram account, with two-factor authentication on | Business settings, Accounts and Users | Create a portfolio (free), add Page and account, switch on two-factor authentication |
| Web hosting with PHP 8.1+, pdo_sqlite, curl, mbstring, HTTPS, and a cron job every minute; Apache or LiteSpeed preferred | Upload and open `keuring.php`; look for "Cron jobs" in your host's panel | Take a small hosting plan and a subdomain such as `chat.yourpractice.com` |
| Access to your host's file manager or FTP | Log in at your host | Ask your host or your web builder |
| Your own website with a privacy page | Can you edit your privacy statement? | Create a privacy page; a template is included |
| A second Instagram account of your own, for testing | | Add an account in Instagram |
| A password manager | | The one in your phone or browser is fine |
| Two to three hours, may be split up | | |
| Claude (the Claude app or Claude Code) with this skill | | See "Installation" |

Full list: [referentie/en/requirements.md](referentie/en/requirements.md).

## Installation

**With Claude (recommended).** Add this folder as a skill (in Claude Code: put it in `~/.claude/skills/jensiechat`; in the Claude app: upload the zip under skills in the settings, if your plan supports skills). Then ask Claude: "Install Jensiechat". Claude checks the requirements with you first and then walks through the phases. You log in and click yourself; your keys never go into the chat.

**By hand.** The steps, in short (details in [referentie/en/installation-path.md](referentie/en/installation-path.md)):
1. Create a folder (for example `public_html/chat`) or a subdomain with HTTPS.
2. Upload the contents of `code/jensiechat/`, including the hidden `.htaccess` files.
3. Open `https://<address>/keuring.php` and fix everything that is red.
4. Open the address of the folder. The setup screen asks for the language, a name, a colour and a dashboard password, and writes the settings file for you (it is open for 24 hours after uploading). Prefer doing it by hand: copy `config.voorbeeld.php` to `jensiechat-config.php` one folder above your web folder (or to `config.php` in the folder) and fill in `adres`, `dashboard_wachtwoord` (12+ characters), `taal` (`nl`, `en` or `es`), `naam` and `kleur`.
5. Create the app at Meta (Business type, Instagram with Facebook login), a system user with access to the app, the Page and the Instagram account, and a token with `instagram_basic`, `instagram_manage_comments` and `instagram_manage_messages`. Switch on "Allow access to messages" in the Instagram app.
6. In the dashboard, Health, "Connection with Meta": paste the access token, the app ID and the app secret, and press Save and check. (Or put `meta_token`, `meta_app_id` and `meta_app_geheim` in the settings file yourself.)
7. Create a cron job that runs every minute with the command shown in Health.
8. Optional: the webhook at Meta with the address from Health and the `webhook_controlewoord` (verify token) from your config file, subscribed to `comments`.
9. Create your first automation in test mode, test it from your second account, then switch test mode off.
10. Add the privacy text from `code/privacy/` to your privacy page.

## Update

Upload the new code over the old folder. Your config file and data folder stay. Never overwrite your `config.php` with `config.voorbeeld.php`.

## Remove

Switch automations off, delete the cron job, delete the Jensiechat folder and the data folder (`jensiechat-data` next to your web folder), remove the app at Meta, and update your privacy text.

## Privacy

Jensiechat stores the Instagram username and ID, the comment, the post, the time, whether the message was sent and whether the link was tapped. No IP addresses, no cookies, no profile pictures. After 90 days name, ID and text are erased; counts remain. The dashboard has "Erase someone's data" for requests. The data stays on your own hosting, by default outside the web folder. You are responsible for this data (GDPR); `code/privacy/` has a template text. Not legal advice.

## Honest limits

- Meta decides the rules and changes screens. Tested in November 2026 by the maker, and against a simulated Meta API; other hosts have not been tested yet.
- Without App Review, messages arrive within about a minute, and the app has roughly 200 API calls per hour. When it is busy, people wait in the queue.
- There is no helpdesk. The Health tab says what is wrong and what to do next.

## Licence and maker

MIT, see [LICENSE](LICENSE). Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). Please keep the line "Made with Jensiechat by Jens Kemna, Praktijkwerkbank" in the dashboard footer.

Jensiechat is not a product of, and is not endorsed by, ManyChat or Meta. Instagram and Facebook are trademarks of Meta Platforms, Inc. ManyChat is a trademark of its owner.

Versions: [CHANGELOG.md](CHANGELOG.md). Found a security problem? See [SECURITY.md](SECURITY.md).
