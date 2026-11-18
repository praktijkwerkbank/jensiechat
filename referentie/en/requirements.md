# What you need before you start

Go through this list before installing anything. For each item: what it is, how to check whether you have it, and what to do if you do not. Claude asks about them one at a time.

## 1. An Instagram account that is business or creator, and public

**What it is.** Instagram has personal, business and creator accounts. Only business and creator accounts let an app read comments and send messages. The account must be public; for a private account Meta passes nothing on.

**How to check.** In Instagram: your profile, the menu at the top right, Settings. If there is a section for professional accounts (the name changes between versions, for example "Account type and tools"), you have a professional account. Under Account privacy, "Private account" must be off.

**If you do not have it.** Switching is free and happens in Instagram itself: Settings, Account type and tools, Switch to professional account. Your followers and posts stay.

## 2. A Facebook Page connected to that Instagram account

**What it is.** Jensiechat uses Meta's Instagram connection through Facebook (Meta calls this Unified login). For that, your Instagram account must be connected to a Facebook Page for your practice.

**How to check.** In Instagram: Edit profile. If a Page is shown at "Page", it is connected. Or on Facebook, on your Page: Settings, Linked accounts.

**If you do not have it.** Create a Facebook Page for your practice (free). Then connect your Instagram account to it, through Edit profile in Instagram or through Meta's Accounts Center.

## 3. A Facebook account with admin rights, in a business portfolio

**What it is.** Your personal Facebook account must be admin of the Page and of a business portfolio on business.facebook.com (formerly Business Manager). The Page and the Instagram account sit in that portfolio. Later you create a "system user" there: a technical account whose token does not expire. Two-factor authentication must be on; Meta asks for it on these screens.

**How to check.** Go to business.facebook.com and open Business settings. If you see your Page and your Instagram account under Accounts, and you are admin under Users, People, you are set.

**If you do not have it.** Create a portfolio on business.facebook.com (free) and add your Page and your Instagram account. Switch on two-factor authentication in your Facebook account settings (Accounts Center, Password and security).

## 4. Web hosting with PHP, HTTPS and a scheduled task every minute

**What it is.** Jensiechat is a small PHP program that must be on a server day and night: Meta must be able to reach it and it must be able to check every minute. Exactly needed:
- PHP 8.1 or higher, with the extensions pdo_sqlite (the database), curl (talking to Meta) and mbstring (text);
- HTTPS with a valid certificate on the address where Jensiechat goes;
- a scheduled task (hosts call this a cron job) that can start a PHP file every minute;
- preferably an Apache or LiteSpeed server, which reads `.htaccess` (for protection and the short links). Most ordinary web hosting does.

No MySQL, no Node, no SSH needed.

**How to check.** If you have a WordPress site on your own hosting, you almost always have this already. To be sure, use `keuring.php` from the package: upload it and open it in your browser; it says green or red for each item. It cannot see the cron job: check in your host's control panel whether there is "Cron jobs" or "Scheduled tasks" and whether you can choose "every minute".

**If you do not have it.** If your site is with a website builder without its own hosting (such as Wix, Squarespace or Jimdo), Jensiechat cannot run there. Get a small hosting plan as well and point a subdomain (for example `chat.yourpractice.com`) to it; your site stays where it is. Choose the host yourself; Claude can explain what to look for, but does not sign up for anything for you. If your host cannot run a task every minute, ask whether it can, or choose another plan.

## 5. Access to your host's file manager or FTP

**What it is.** You must be able to upload files, extract a zip, create a folder or subdomain, and edit a file (`config.php`).

**How to check.** Log in at your host. If you see something like "File Manager" or FTP details, you are set.

**If you do not have it.** Ask your host for the control panel login, or for FTP details. If someone else built your site, ask them for access for yourself.

## 6. Your own website with a privacy page

**What it is.** You are responsible for the data of people who comment (GDPR). Your site must say what you keep, why, for how long, and how someone can have it erased. The package includes a template.

**How to check.** Is there a privacy statement on your site, and can you edit it yourself?

**If you do not have it.** Create a "Privacy" page on your site. Claude helps with the text about Jensiechat; for the rest of your privacy statement, a template from your professional association or a lawyer is wise.

## 7. A second Instagram account of your own

**What it is.** To test, you comment with the keyword from another account than your practice account. Jensiechat skips comments from your own practice account, and you do not want to test on followers.

**How to check.** Do you have a personal account, or can you create one with another email address?

**If you do not have it.** Add an extra account in Instagram (Profile, your name at the top, Add account).

## 8. A password manager or another safe place

**What it is.** You get a token and an app secret from Meta, and you choose a dashboard password. These do not belong in a note, email or chat.

**How to check.** Do you already use a password manager (for example the one in your browser or phone, or a separate app)?

**If you do not have it.** The password manager of your phone or browser is enough.

## 9. Two to three hours, may be split up

**What it is.** An estimate for someone who already has the accounts. If you still need to set up a portfolio or hosting, it takes longer. You can stop after every phase; Claude keeps track in `voortgang.md`.

## 10. This skill in Claude

**What it is.** Claude guides you through: in the Claude app or in Claude Code. You log in and click yourself, in your own browser where you are already logged in to Facebook. Claude says for each screen what to look for.

**How to check.** If you are reading this through Claude, you are set.

---

**Done with the list?** Installation starts at phase 1 of `installation-path.md`.
