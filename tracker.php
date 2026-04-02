<?php
// ============================================================
// pimabox — tracker.php
// Receives page view pings, filters bots, logs to CSV.
// No cookies. No IP storage. GDPR-friendly.
// ============================================================

header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) exit;
require_once $configPath;

date_default_timezone_set(TIMEZONE);

// ---- Tracker token check ----
if (defined('TRACKER_TOKEN') && TRACKER_TOKEN !== '') {
    if (($_GET['t'] ?? '') !== TRACKER_TOKEN) exit;
}

// ---- Helpers ----

function isBot(string $ua): bool {
    if (empty($ua)) return true;
    $ua = strtolower($ua);
    foreach (BOT_PATTERNS as $pattern) {
        if (strpos($ua, strtolower($pattern)) !== false) return true;
    }
    return false;
}

function getDevice(string $ua): string {
    if (preg_match('/(mobile|android|iphone|ipod|blackberry|windows phone|opera mini)/i', $ua)) return 'mobile';
    if (preg_match('/(tablet|ipad)/i', $ua)) return 'tablet';
    return 'desktop';
}

function getCountry(string $ip): string {
    if (!GEO_ENABLED) return '';
    if (in_array($ip, ['127.0.0.1', '::1'], true) || preg_match('/^(192\.168|10\.|172\.(1[6-9]|2\d|3[01]))/', $ip)) return 'local';
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $res = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=countryCode', false, $ctx);
    if ($res) {
        $data = json_decode($res, true);
        return $data['countryCode'] ?? '';
    }
    return '';
}

/**
 * Privacy-safe visitor hash.
 * Combines date + truncated IP (first 3 octets only) + UA + daily salt.
 * The salt rotates daily → hash cannot be linked across days.
 * IP is never stored — only used transiently to compute the hash.
 */
function visitorHash(string $ip, string $ua, string $date): string {
    // Use only first 3 octets of IPv4 (masks last octet) — /24 anonymization
    $ipAnon = preg_replace('/\.\d+$/', '.0', $ip);
    // Daily salt stored in cache dir — rotates automatically each day
    $saltFile = dirname(CSV_PATH) . '/.salt_' . $date;
    if (!file_exists($saltFile)) {
        file_put_contents($saltFile, bin2hex(random_bytes(16)));
        // Clean up old salt files (keep only last 2 days)
        foreach (glob(dirname(CSV_PATH) . '/.salt_*') as $f) {
            if ($f !== $saltFile && filemtime($f) < strtotime('-2 days')) @unlink($f);
        }
    }
    $salt = file_get_contents($saltFile);
    return substr(hash('sha256', $salt . $date . $ipAnon . $ua), 0, 12);
}

function sanitize(string $val, int $maxLen = 512): string {
    $val = str_replace(["\r", "\n", "\t"], ' ', $val);
    $val = ltrim($val, '=+-@');
    return mb_substr(trim($val), 0, $maxLen);
}

function rotateCsvIfNeeded(string $csvPath): void {
    if (!file_exists($csvPath)) return;
    if (filesize($csvPath) / 1048576 >= CSV_MAX_SIZE_MB) {
        $backup = $csvPath . '.' . date('Ymd-His') . CSV_BACKUP_SUFFIX;
        rename($csvPath, $backup);
        $backups = glob(dirname($csvPath) . '/' . basename($csvPath) . '.*.bak');
        if ($backups && count($backups) > 3) {
            sort($backups);
            foreach (array_slice($backups, 0, count($backups) - 3) as $old) @unlink($old);
        }
    }
}

// ---- Main ----

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ip = isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0])
    : ($_SERVER['REMOTE_ADDR'] ?? '');

if (isBot($ua)) exit;
if (!empty(EXCLUDED_IPS) && in_array($ip, EXCLUDED_IPS, true)) exit;

$page     = sanitize($_GET['p'] ?? '/', 255);
$referrer = sanitize($_GET['r'] ?? '', 512);

$host = $_SERVER['HTTP_HOST'] ?? '';
if ($host && strpos($referrer, $host) !== false) $referrer = '';

$device  = getDevice($ua);
$country = getCountry($ip);
$date    = date('Y-m-d');
$time    = date('H:i:s');
$vid     = visitorHash($ip, $ua, $date); // privacy-safe daily visitor token

$csvPath  = CSV_PATH;
$cacheDir = dirname($csvPath);
if (!is_dir($cacheDir)) mkdir($cacheDir, 0750, true);

rotateCsvIfNeeded($csvPath);

$needsHeader = !file_exists($csvPath) || filesize($csvPath) === 0;
$row = implode(',', [
    $date, $time,
    '"' . str_replace('"', '""', $page) . '"',
    '"' . str_replace('"', '""', $referrer) . '"',
    $device, $country, $vid,
]);

$fh = fopen($csvPath, 'a');
if ($fh && flock($fh, LOCK_EX)) {
    if ($needsHeader) fwrite($fh, "date,time,page,referrer,device,country,vid\n");
    fwrite($fh, $row . "\n");
    flock($fh, LOCK_UN);
    fclose($fh);
}
