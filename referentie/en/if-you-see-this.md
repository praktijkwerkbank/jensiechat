# If you see this, do this

Known problems during installation and use. If unsure, ask for a screenshot, without token, secret or password on it.

## Hosting and dashboard

| What you see | What it is | What to do |
|---|---|---|
| White page or "500 Internal Server Error" | Usually a PHP version that is too old | Open `keuring.php`. If PHP is red: choose PHP 8.1 or higher in the host's control panel. |
| "Not ready yet: there is no config.php" | The settings file is missing and the setup screen `instellen.php` is not in the folder | Upload `instellen.php` again and open `https://<address>/`. Or by hand: copy `config.voorbeeld.php` to `config.php` (or to `jensiechat-config.php` one folder above the web folder) and fill it in. |
| "This screen is closed" | The setup screen only works in the first 24 hours after uploading | In the file manager, create an empty file `instellen.aan` in the Jensiechat folder and reload the page. |
| "Your host does not let Jensiechat write a file here..." | The host does not let the setup screen write a settings file | Press "Download config.php" and put that file in the Jensiechat folder as `config.php`, using the file manager. |
| "Jensiechat is already set up", while you did not go through the setup screen yourself | Someone else filled in the screen, or an old settings file was still there | In the file manager, delete the settings file: look in both places (`jensiechat-config.php` one folder above the web folder, `config.php` in the Jensiechat folder), and also the database `jensiechat.db` (in `jensiechat-data` next to the web folder, or in `data/` in the Jensiechat folder): an existing database also keeps the screen closed. Then go through the setup screen again, with `instellen.aan` if needed. Choose a new password that you use nowhere else. |
| "There is a typo in ... around line ..." | A quote or comma too many or too few in the settings file | Check that line. Every line has the form `'naam' => 'text',` |
| "The adres ... must start with https://" | The address in the setup screen or `adres` in the settings file is http or empty | Fill in the full address with https://, without a / at the end. |
| "The dashboard_wachtwoord ... is too short" | Fewer than 12 characters | Choose a longer password in the settings file. |
| "This password is not correct" | Different password than in the settings file | Copy it from your password manager. Watch out for spaces. |
| "Too many wrong passwords" | 20 wrong attempts in fifteen minutes | Wait fifteen minutes. |
| "The PHP extension pdo_sqlite is switched off" | SQLite is off at the host | Tick it under PHP extensions in the control panel, or ask the host. |
| "Jensiechat cannot store data anywhere" | No write access | Create a folder `jensiechat-data` next to the web folder, or set `datamap` to a path where the server may write. |
| Health: "Where your data is stored" red | The database can be downloaded over the internet | Switch nothing on. Set `datamap` in the settings file to a folder outside the web folder, or ask the host why `.htaccess` does not work. |
| Health: "Where your data is stored" orange | The database is inside the web folder, but protected | Works, but better: a folder `jensiechat-data` next to `public_html`. |
| A short link gives "Not Found" | The server does not read `.htaccess` (often nginx) | Set `mooie_links` to `false` in the settings file. New messages then get links with `?s=`. |

## Meta

| What you see | What it is | What to do |
|---|---|---|
| At "Connection with Meta": "A key contains a space or an odd character. Copy it again." | A space, line break or other character came along when copying | Copy the key again from your password manager and paste it, without a space before or after it. |
| At "Connection with Meta": "Your host does not let Jensiechat change the settings file..." | The host does not let Jensiechat change the settings file | Put the keys into the settings file yourself using the file manager: `meta_token`, `meta_app_id` and `meta_app_geheim`, between the quotes. Claude says which line; you paste. |
| Meta asks for an identity check and gets stuck | Unfamiliar browser or embedded window | Use your own, trusted browser in which you are already logged in to Facebook. |
| At "Create app" your portfolio is missing or grey | Not admin of the portfolio, or connecting fails from this screen | Check in Business settings that you are admin. Try the other way round: Business settings, Accounts, Apps, add app with the app ID. |
| Only "Instagram login" can be chosen, no Facebook login | Different screen or order | Look in the use case or settings for "API setup with Facebook login". Send a screenshot. |
| The tester invite stays "pending" | Accepted on another account, or looked for in the phone app | Log in on instagram.com as your practice account: Settings, Apps and websites, Tester invites. |
| The token does not work (Health red, code 190) | Pasted wrongly, expired or revoked | Copy it again from your password manager without spaces and paste it at Health, "Connection with Meta"; otherwise create a new token for the system user. |
| Token green, but "Instagram account not found" | The system user has no access to the Instagram account | Assign the Instagram account to the system user in Business settings, then create a new token. |
| Webhook notification with a wrong signature refused | The saved app secret is wrong | Copy the app secret again (App settings, Basic, Show) and paste it at Health, "Connection with Meta". |
| Meta says the webhook address cannot be verified | Wrong verify word, or no valid certificate | Copy the word again from the settings file, without a space. Open the address in the browser: you should see "nee", not an error page. |
| "No notification received yet" at the webhook | Normal without App Review | Do nothing. The own check picks up all comments. |
| Permission error when sending (code 10, 200 or 3) | Token lacks a permission, or the message switch is off | Check the three permissions of the token; in Instagram switch on "Allow access to messages". |
| "(#4) Application request limit reached" or "Meta limit" | The app's hourly budget is used up | Wait. Messages stay in the queue and go out by themselves. |

## Testing and use

| What you see | What it is | What to do |
|---|---|---|
| Comment posted, nothing happens | Keyword does not match, automation is off, or you comment with your practice account | Use "Try a comment" in the dashboard; comment with your second account. |
| Activity: "Skipped, test mode" | The account is not on the test list | Add your second account at Test accounts, spelled correctly. |
| Activity: "Already received this message in the last 24 hours" | Same person, same automation | Intended. Test with another automation or wait a day. |
| Message arrives after about a minute | The own check picked it up | Normal without App Review. |
| Message arrives as text with the link below, without a button | Meta refused the button form | Works. Health says at "Buttons" what happened. |
| Activity: "Meta does not allow this message" | Comment older than 7 days, or already answered by another tool | Answer by hand. Switch off another tool on the same post first. |
| Health: "Own check" red | The cron job is not running | Check the cron job in the host's control panel; the command is in Health. Some hosts show the task's output in the panel. |
| Health: "Queue" keeps growing | Limit or outage at Meta | Wait; check the log. Comments younger than 7 days still go out. |
