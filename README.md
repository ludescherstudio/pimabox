# pimabox

> *Pima* (Swahili) — to measure, to assess.  
> *Box* — a self-contained system you drop in and it runs.  
> **pimabox** is exactly that: a compact, silent little box you put on your server that measures your website traffic. No cloud. No complexity. Just your data, on your hosting.

**Cookie-free, GDPR-compliant website analytics for PHP shared hosting.**

No Node.js. No Docker. No external database. No cookies. No consent banner.  
Just upload 5 files and you're done.

Designed for beginners — if you can upload files via FTP and edit a text file, you can run pimabox. Works on any website: static HTML, WordPress, or any PHP-based site.

---

## Why pimabox?

| | pimabox | Matomo | Plausible | Google Analytics |
|---|---|---|---|---|
| No cookies | ✅ | ⚠️ | ✅ | ❌ |
| No external database | ✅ | ❌ | ❌ | — |
| Shared hosting | ✅ | ⚠️ | ❌ | — |
| Self-hosted | ✅ | ✅ | ✅ | ❌ |
| Install time | ~5 min | 30 min | 1–2h | 5 min |

---

## Installation

### Step 1 — Upload files

Upload these files to your web root via FTP:

```
your-webroot/
├── tracker.php
├── pimabox.php
├── config.php
├── robots.txt      ← merge with yours if you already have one
├── .htaccess       ← add the Rewrite line if you already have one (see below)
└── cache/
    └── .htaccess
```

> **Already have a `.htaccess`?** Don't overwrite it. Just add this one line:
> ```apache
> RewriteRule ^pimabox$ pimabox.php [L]
> ```

> **Already have a `robots.txt`?** Add these lines to it instead:
> ```
> Disallow: /pimabox
> Disallow: /tracker.php
> Disallow: /cache/
> ```

### Step 2 — Set your passwords and timezone

Open `config.php` and set two things:

```php
define('STATS_PASSWORD', 'your-dashboard-password');  // to log into your dashboard
define('TRACKER_TOKEN',  'my-secret-word');            // a second password for the snippet below
define('TIMEZONE',       'Europe/Vienna'); // full list: php.net/timezones
```

> **Why two passwords?**  
> The tracker token appears in your page's HTML source code — anyone can see it. It only allows *writing* hits, not reading your dashboard. Your dashboard password stays completely secret and is never exposed in your code.
>
> **Important:** Use a different value for the tracker token than any of your existing passwords — since it's visible in your source code, treat it as a public identifier, not a secret.

### Step 3 — Add the tracking snippet

Replace `my-secret-word` with whatever you chose above.

**Static HTML sites**

Paste this into every HTML file you want to track — homepage, about, contact, imprint, blog posts, everything. It goes at the very end of your file, after your footer, directly before the closing `</body>` tag. If you're not sure where that is, search for `</body>` in your file — there's only one of them. Before you save, replace `my-secret-word` with the tracker token you set in Step 2.

```html
<script>
fetch('/tracker.php?p=' + encodeURIComponent(location.pathname)
  + '&r=' + encodeURIComponent(document.referrer)
  + '&t=my-secret-word');
</script>
```

**WordPress**

Open your WordPress admin, go to **Appearance → Theme File Editor**, open `functions.php`, and paste this at the very end of the file. Before you save, replace `my-secret-word` with the tracker token you set in Step 2.

```php
function pimabox_tracker() { ?>
<script>
fetch('/tracker.php?p=' + encodeURIComponent(location.pathname)
  + '&r=' + encodeURIComponent(document.referrer)
  + '&t=my-secret-word');
</script>
<?php }
add_action('wp_footer', 'pimabox_tracker');
```

This runs automatically on every page of your WordPress site — no need to touch individual pages or posts.

### Done.

Open `yourdomain.com/pimabox` in your browser, enter your password, and see your dashboard.

### Step 4 — Make it yours (optional)

Open `config.php` and change the accent color to match your site:

```php
define('BRAND_COLOR', '#0d9488'); // any hex color — avoid Google Fonts for GDPR compliance
define('BRAND_NAME',  'My Site'); // shown in the dashboard header
```

Logo, font and more can also be configured — see the Configuration reference below.

---

## Security

pimabox is designed to be reasonably secure out of the box for a self-hosted tool on shared hosting.

**What's protected:**
- `config.php` is blocked from web access via `.htaccess` — no one can read your password from the browser
- `cache/` is fully blocked — the SQLite database cannot be downloaded directly
- The login form has **brute-force protection**: after 5 failed attempts, the form locks out for 15 minutes (configurable in `config.php`)
- After a successful login, the session ID is regenerated to prevent session fixation attacks
- All inputs to `tracker.php` are safely bound using PDO prepared statements to prevent SQL injection
- The **tracker token** (`TRACKER_TOKEN`) ensures only your own snippet can write hits — preventing fake data from being injected

**robots.txt** excludes `/stats`, `/tracker.php`, and `/cache/` from being indexed or crawled by search engines and bots.

**Important:** pimabox should only be used on sites with HTTPS. The login password is sent via POST — over plain HTTP it would be visible in transit. Most shared hosts (including World4You) provide free SSL — make sure it's active.

**Configuring lockout settings:**
```php
define('MAX_LOGIN_ATTEMPTS', 5);    // Failed attempts before lockout
define('LOCKOUT_SECONDS',    900);  // Lockout duration (900 = 15 minutes)
```

---

## What gets tracked

| Field | Example | Notes |
|---|---|---|
| Date | `2024-03-15` | Server date |
| Time | `14:32:01` | Server time |
| Page | `/blog/hello-world` | URL path |
| Referrer | `google.com` | Domain only |
| Device | `desktop` / `mobile` / `tablet` | Via User-Agent, never stored |
| Country | `AT` | Via IP lookup — IP itself never stored |

**Never stored:** IP address, cookies, fingerprint, user identity, browser details.

---

## Dashboard

- **KPIs** — Total pageviews, today, unique pages, referrers, countries
- **14-day trend** — Daily bar chart
- **Top pages** — Ranked with visual bars
- **Referrers** — Where your visitors come from
- **Device split** — Desktop / Mobile / Tablet
- **Countries** — Top countries
- **Recent hits** — Last 50 page views
- **CSV Export** — Download all your data anytime

---

## Configuration reference

All settings in `config.php`:

```php
define('STATS_PASSWORD',    'change-me');       // Dashboard password
define('TRACKER_TOKEN',     'change-me');        // Secret for tracking snippet
define('TIMEZONE',          'Europe/Vienna');    // PHP timezone string

define('BRAND_COLOR',       '#c4773a');          // Dashboard accent color
define('BRAND_LOGO',        '');                 // Path/URL to logo image
define('BRAND_NAME',        'My Site');          // Site name in header

define('DB_PATH',           __DIR__.'/cache/analytics.db'); // SQLite database path
define('GEO_ENABLED',       true);               // Country lookup via ip-api.com
define('EXCLUDED_IPS',      ['1.2.3.4']);        // Your own IPs to ignore
define('MAX_LOGIN_ATTEMPTS',5);                  // Attempts before lockout
define('LOCKOUT_SECONDS',   900);                // Lockout duration in seconds
define('RECENT_ENTRIES',    50);                 // Rows in recent hits table
define('TREND_DAYS',        14);                 // Days shown in trend chart
```

---

## Privacy & GDPR

- No cookies — no consent banner needed
- IPs used only for country lookup, then discarded — **never written to disk**
- Only the country code (e.g. `AT`) is stored, not the IP
- All data stays on your own server
- For zero external requests: set `GEO_ENABLED = false`

---

## Honest limitations

- **Pageviews, not unique visitors** — without cookies or fingerprinting, sessions can't be tracked. This is intentional.
- **Not for high-traffic sites** — SQLite handles millions of rows comfortably, but concurrent write spikes (500+ simultaneous visitors) may cause brief delays.
- **No real-time view** — dashboard reflects data as written to the database.

---

## Requirements

- PHP 7.4+
- Apache with `.htaccess` support (standard on all shared hosts)
- `fopen` / `fwrite` enabled (standard)
- SQLite3 and PDO_SQLITE extensions enabled (standard on all major hosts)
- `allow_url_fopen` (only for country detection)
- HTTPS (strongly recommended)

---

## License

MIT — free to use, modify, and self-host.

---

*pimabox — measure more. manage less.*

---

## Support

pimabox is free and open-source. If it saves you time or a cookie banner, consider buying me a coffee. ☕

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/ludescherstudio)
