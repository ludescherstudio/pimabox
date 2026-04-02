<?php
// ============================================================
// pimabox — config.php
// ============================================================

// --- Auth ---
define('STATS_PASSWORD', 'change-me-please');

// --- Tracker token ---
// A secret string that must be present in the tracking snippet.
// Prevents fake hits from being injected into your analytics.
// Change this to any random string, then update your snippet:
//   fetch('/tracker.php?p=...&r=...&t=YOUR_TOKEN_HERE');
define('TRACKER_TOKEN', 'change-this-token');

// --- Timezone ---
define('TIMEZONE', 'Europe/Vienna'); // full list: php.net/timezones

// --- Branding ---
// Customize the dashboard to match your site's look.
// BRAND_COLOR:    any CSS color — hex, rgb, hsl
// BRAND_LOGO:     path or URL to your logo (leave empty to show BRAND_NAME as text)
// BRAND_NAME:     shown in header and browser tab
// BRAND_FONT:     any system font stack (e.g. 'Georgia, serif')
// BRAND_FONT_URL: full Google Fonts URL — leave empty for system fonts
define('BRAND_COLOR',    '#c4773a');
define('BRAND_LOGO',     '');                // e.g. '/assets/logo.svg'
define('BRAND_NAME',     'My Site');
define('BRAND_FONT',     'Georgia, serif');
define('BRAND_FONT_URL', '');                // e.g. 'https://fonts.googleapis.com/css2?family=Lato'

// --- CSV settings ---
define('CSV_PATH', __DIR__ . '/cache/analytics.csv');
define('CSV_MAX_SIZE_MB', 10);
define('CSV_BACKUP_SUFFIX', '.bak');

// --- IP Geolocation (optional) ---
// Uses ip-api.com (free, no key needed, rate-limited to 45 req/min)
define('GEO_ENABLED', true);

// --- IPs to exclude from tracking ---
define('EXCLUDED_IPS', [
    // '1.2.3.4',
]);

// --- Bot filter ---
define('BOT_PATTERNS', [
    'bot', 'crawl', 'spider', 'slurp', 'wget', 'curl',
    'python-requests', 'axios', 'java/', 'go-http',
    'googlebot', 'bingbot', 'yandexbot', 'duckduckbot',
    'baiduspider', 'facebot', 'ia_archiver',
    'semrushbot', 'ahrefsbot', 'mj12bot', 'dotbot',
    'screaming frog', 'rogerbot', 'exabot', 'sistrix',
    'petalbot', 'bytespider', 'gptbot', 'claudebot',
    'anthropic-ai', 'ccbot', 'dataforseobot',
]);

// --- Dashboard display settings ---
define('RECENT_ENTRIES', 50);
define('TREND_DAYS', 14);

// --- Brute-force protection ---
// Max failed login attempts before temporary lockout
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_SECONDS', 900); // 15 minutes
