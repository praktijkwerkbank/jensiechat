# Jensiechat installation path (basic version): the script for Claude

What Claude says and asks in each phase, what the user does, what goes wrong and when a phase is done. The stumbling blocks at Meta really happened during the maker's first installation (November 2026).

Fixed rules: one question at a time; never keys in the chat; the user logs in themselves; nothing live without a clear yes; ask for a screenshot when a screen looks different, with the sentence "Make sure no token, secret or password is visible on it."

Every phase ends with three lines: **What works now**, **How you can see it yourself**, **Next step**. Then update `voortgang.md`.

The dashboard and the config file use Dutch setting names (`adres`, `dashboard_wachtwoord`, `taal`, `naam`, `kleur`); each one is explained in English below and in `config.voorbeeld.php`.

---

## Phase 0: welcome and requirements

**Claude says (briefly, in its own words):**
> With this you set up your own automatic reply for Instagram: someone comments a word under your post and gets the link in their messages, with a short reply under the comment. It runs on your own web hosting, under your own name, without a subscription.
> Honestly, up front: you need web hosting with PHP, you create your own app at Meta (that is the longest part), and you are responsible for the data of people who comment. It takes two to three hours, and you can split that up. Shall we first check that you have everything?

Then `requirements.md`, item by item, one question per message. If something is missing: help with that first.

- **What works now:** nothing installed yet; you know you have everything.
- **How you can see it yourself:** every item in `voortgang.md` is ticked.
- **Next step:** make the choices (phase 1).

## Phase 1: choices

One at a time: the Instagram account name; the website address; where Jensiechat goes (a folder `yourpractice.com/chat` or a subdomain `chat.yourpractice.com`); the language (`nl` or `en`, setting `taal`); a name for the dashboard (`naam`; no "Insta", "IG", "Facebook" or "Meta" in it) and an accent colour (`kleur`; the main colour of the website usually works).

- **What works now:** the choices are fixed.
- **How you can see it yourself:** they are in `voortgang.md`.
- **Next step:** prepare the hosting.

## Phase 2: hosting check and upload

1. **Folder or subdomain.** The user creates a folder in the web folder (often `public_html/chat`) or a subdomain in the host's control panel. For a subdomain: also switch on the (free) certificate; this can take up to an hour.
2. **Upload.** Put the contents of `code/jensiechat/` in that folder. Easiest: upload the folder as a zip in the file manager and extract it there. Check: the folder contains `index.php`, `lib.php`, `keuring.php` and the folders `r`, `taal` and `data`. Hidden files (`.htaccess`) must come along; some file managers hide them.
3. **Hosting check.** Open `https://<address>/keuring.php`. Each item is green, orange or red, with what to do. Fix red first. Most important: "Protection with .htaccess" and "Folder for the data"; the database must never be downloadable over the internet.
4. **config.php.** In the file manager, copy `config.voorbeeld.php`. Safest: name the copy `jensiechat-config.php` and put it one folder above the web folder (next to `public_html`, not inside it). Otherwise: `config.php` in the Jensiechat folder. Only fill in now: `adres` (the web address of the folder), `dashboard_wachtwoord` (dashboard password, at least 12 characters, from the password manager), `taal`, `naam`, `kleur`. The Meta fields stay empty.
5. **Open the dashboard** at `https://<address>/` and log in with the password.

**What goes wrong:**
- A white page or "500": often a PHP version that is too old. See what the hosting check says.
- "There is a typo in config.php around line ...": a missing quote or comma. Every line looks like `'naam' => 'text',`.
- "The adres must start with https://": `adres` contains `http://` or nothing.

- **What works now:** the dashboard is on your own site, behind your password.
- **How you can see it yourself:** under Health, "Token: no token filled in yet" (red, which is correct now) and "Where your data is stored" green or orange.
- **Next step:** the app at Meta (phase 3, the longest part).

## Phase 3: the app at Meta

**Claude says first:** "This is the longest part, count on 45 minutes to an hour and a half. You do everything in your own browser, where you are already logged in to Facebook. I tell you per screen what to look for. Meta sometimes asks for your password or a code; that is normal. If a screen looks different, send a screenshot, without keys on it."

Meta calls the route "Instagram API with Facebook login" (also Unified login). The route "Instagram login" was not offered for new apps when the maker installed, although older explanations still mention it.

### 3.1 Prerequisites
Look on business.facebook.com, Business settings, under Accounts: are the Page and the Instagram account in your portfolio? Are you admin? Is two-factor authentication on? If not: first `requirements.md` items 2 and 3.

### 3.2 Developer account
Go to developers.facebook.com and log in. If this is the first time, Meta registers you as a developer (a few screens, sometimes a phone code). You are looking for a screen with "My apps".

**Goes wrong:** Meta demands an identity check and gets stuck. This happens in an unfamiliar browser or an embedded window. Use your own, trusted browser.

### 3.3 Creating the app
"Create app". Meta asks what the app is for; look for the choice related to Instagram or to businesses. The app type is **Business**. Name: your dashboard name. Connect the app to your business portfolio.

**Goes wrong:** the portfolio is not in the list or stays grey. Check that you are admin of the portfolio. If it does not work from the app screen, try the other way round: Business settings, Accounts, Apps, add an app with the app ID. For the maker, connecting only worked in his own browser.

### 3.4 The Instagram product
In the app menu, look for "Add products" or a list of use cases. Choose Instagram, and within it the setup **with Facebook login** (not "with Instagram login"). Switch on the permissions `instagram_basic`, `instagram_manage_comments` and `instagram_manage_messages`.

**Goes wrong:** the template switches on extra permissions (such as `instagram_content_publish`, `ads_read`, `ads_management`). Jensiechat does not use them. Leaving them is fine while the app is in development.

### 3.5 Your Instagram account as tester
In the app: App roles, Roles, add people, role Instagram tester, fill in your practice account. Then accept it on the Instagram **website** instagram.com, Settings, Apps and websites, Tester invites. It is not always in the phone app.

**Goes wrong:** the invite stays "pending": you are logged in on instagram.com with another account, or you are looking in the phone app.

### 3.6 System user and token
A system user token does not expire. A token from the app dashboard expires after 60 days; we do not want that one.
1. business.facebook.com, Business settings, Users, System users: add a system user with the admin role.
2. For that system user, "Assign assets" (or similar): the app, the Page and the Instagram account, with full control.
3. "Generate token": choose the app, tick `instagram_basic`, `instagram_manage_comments` and `instagram_manage_messages`. If `pages_show_list` or `business_management` are also offered and you are unsure, tick them too; they only give read access to your own Page and portfolio.
4. Put the token in your password manager right away. **Do not paste it in the chat.** Meta shows it only once.

**Goes wrong:**
- The token was created before the system user had access to the Instagram account: Jensiechat then sees no account. Generate it again after assigning.
- Token lost: no problem, create a new one.

### 3.7 App ID and app secret
In the app: App settings, Basic. The **app ID** (digits only) is at the top. The **app secret** is behind a "Show" button (Meta asks for your password). Both into your password manager.

**Goes wrong:** mixing up token and app secret. The token comes from the system user and is long; the app secret comes from the app and is 32 characters.

### 3.8 Message access in Instagram
In the Instagram app on your phone, logged in as your practice account: Settings, Messages and story replies, Message controls or Connected tools: "Allow access to messages" on. The name and place differ per version; you are looking for the switch that gives outside apps access to your messages. If it is off, Meta gives no clear error; nothing arrives.

### If a key leaks
If someone pastes a token, app secret or password in the chat or shows it on a screenshot, treat it as leaked:
- **token:** create a new token for the system user and revoke the old one (at the system user, manage or revoke tokens);
- **app secret:** App settings, Basic, reset the secret;
- **dashboard password:** choose a new one in `config.php`.
Put the new one in `config.php`.

- **What works now:** the app exists and you have a token, app ID and app secret, without them appearing in the chat.
- **How you can see it yourself:** under System users you see your system user with the app, the Page and the Instagram account; your password manager holds the three values.
- **Next step:** the keys into `config.php` and the cron job (phase 4).

## Phase 4: keys into config.php and the cron job

1. Open `config.php` in the file manager and fill in: `meta_app_id`, `meta_app_geheim` (app secret), `meta_token`, and a self-made `webhook_controlewoord` (webhook verify word, for example twenty random letters and digits from the password manager). Save.
2. Dashboard, Health, **Check now**. "Token" and "Instagram account" turn green, with your account name.
3. Health shows the command for the cron job at "Own check". In the host's control panel, create a cron job that runs **every minute** with that command. If PHP has another path at the host (for example a path with a version number), use the host's path.
4. After two minutes: reload Health.

**Goes wrong:** see `if-you-see-this.md`.

- **What works now:** Jensiechat talks to Meta and checks every minute.
- **How you can see it yourself:** under Health, Token, Instagram account and Own check are green.
- **Next step:** optionally the webhook (phase 5), otherwise the first automation (phase 6).

## Phase 5: webhook (optional)

In the app at Meta: Instagram, Webhooks (sometimes under the use case). The callback URL is shown in Health at "Notifications from Meta (webhook)". The verify word (at Meta: Verify token) is the `webhook_controlewoord` from `config.php`; Health does not show it. Subscribe to `comments`. Then press **Switch on notifications** in Health.

Say honestly: without App Review, Meta usually only sends comment notifications for accounts with a role on the app. "No notification received yet" is normal. The cron job does the work; with the webhook, a comment from a tester sometimes arrives faster.

**Goes wrong:** Meta says the address cannot be verified. Verify word copied with a trailing space, no valid certificate, or the address is blocked. Opening the address in the browser should show "nee", not an error page of the host.

- **What works now:** Meta knows your webhook address.
- **How you can see it yourself:** the log says "Meta has verified the webhook address".
- **Next step:** the first automation.

## Phase 6: the first automation, in test mode

Ask one at a time: the keyword (short, one word); the link; which post (one post, all posts, or "the next post I publish").

Claude writes a proposal in the practice's tone of voice: the message (above buttons at most 610 characters; without buttons at most 1000 bytes), the button text (at most 20 characters), three public replies. Check with "Try a comment".

Save with **On** and **Test mode** on, with the second account in the test list. Then the user comments the keyword under the post from the second account.

Check: within about a minute the private message arrives on the second account, the public reply is under the comment, and after tapping the button Activity shows "Clicked". Then, after a clear yes: test mode off.

Advice: say in your post what people get and which word to type; no "follow me first"; keep replying personally too; if another tool (such as ManyChat) is on for the same post, switch that off first, because Meta allows only one private reply per comment.

- **What works now:** everyone who types the keyword gets the message.
- **How you can see it yourself:** Activity shows your test comment with "Message with button sent", "Public reply" and "Clicked".
- **Next step:** the privacy text (phase 7).

## Phase 7: privacy

Claude fills in `code/privacy/privacy-text.en.md` with the practice's details. The user reads it, adjusts it and puts it on the privacy page. Point to "Erase someone's data" under Health for requests, and to the data processing agreement with the host (often a checkbox or download in the account). Say that this is not legal advice.

- **What works now:** you tell visitors what happens with their data.
- **How you can see it yourself:** the text is on your privacy page.
- **Next step:** finish (phase 8).

## Phase 8: done and maintenance

Pass on: look at Health once a week; if something stops, comments younger than 7 days are still answered once it works again; once a year review the privacy text and replace the dashboard password. Note in `voortgang.md` that the installation is done.

Updating: upload the new code over the old folder. `config.php` and the data folder stay. Never overwrite `config.php` with `config.voorbeeld.php`.

Stopping: automations off, cron job removed, the folder and the data folder deleted, the app removed at Meta, privacy text updated.
