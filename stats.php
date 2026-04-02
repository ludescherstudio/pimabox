<?php
// ============================================================
// pimabox — stats.php
// ============================================================

require_once __DIR__ . '/config.php';
date_default_timezone_set(TIMEZONE);
session_start();

// ---- Brute-force protection ----
$maxAttempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
$lockoutSecs = defined('LOCKOUT_SECONDS')    ? LOCKOUT_SECONDS    : 900;
$attempts    = $_SESSION['login_attempts']   ?? 0;
$lockedUntil = $_SESSION['locked_until']     ?? 0;
$isLocked    = time() < $lockedUntil;

// ---- Auth ----
if (!$isLocked && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === STATS_PASSWORD) {
        session_regenerate_id(true);
        $_SESSION['pimabox_auth']   = true;
        $_SESSION['login_attempts'] = 0;
        $_SESSION['locked_until']   = 0;
    } else {
        $attempts++;
        $_SESSION['login_attempts'] = $attempts;
        if ($attempts >= $maxAttempts) {
            $_SESSION['locked_until'] = time() + $lockoutSecs;
            $isLocked = true;
        }
        $authError = true;
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
$authed = !empty($_SESSION['pimabox_auth']);

// ---- Branding ----
$brandColor   = defined('BRAND_COLOR')    ? BRAND_COLOR    : '#c4773a';
$brandLogo    = defined('BRAND_LOGO')     ? BRAND_LOGO     : '';
$brandName    = defined('BRAND_NAME')     ? BRAND_NAME     : 'My Site';
$brandFont    = defined('BRAND_FONT')     ? BRAND_FONT     : 'Georgia, serif';
$brandFontUrl = defined('BRAND_FONT_URL') ? BRAND_FONT_URL : '';
$advancedMode = defined('ADVANCED_MODE')  ? ADVANCED_MODE  : false;

// ---- DB helper ----
function openDb(): ?SQLite3 {
    if (!file_exists(DB_PATH)) return null;
    try {
        $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READONLY);
        $db->enableExceptions(true);
        $db->busyTimeout(3000);
        return $db;
    } catch (Exception $e) {
        return null;
    }
}

// ---- CSV Export ----
if ($authed && isset($_GET['export'])) {
    $db = openDb();
    if ($db) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="pimabox-export-' . date('Y-m-d') . '.csv"');
        echo "date,time,page,referrer,device,country\n";
        $res = $db->query('SELECT date,time,page,referrer,device,country FROM hits ORDER BY date DESC, time DESC');
        while ($row = $res->fetchArray(SQLITE3_NUM)) {
            echo implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }
        $db->close();
    }
    exit;
}

// ---- Clear all data (Advanced Mode only) ----
if ($authed && $advancedMode && isset($_POST['clear_data']) && ($_POST['confirm_clear'] ?? '') === 'yes') {
    try {
        $db = new SQLite3(DB_PATH);
        $db->exec('DELETE FROM hits');
        $db->exec('VACUUM');
        $db->close();
        $clearSuccess = true;
    } catch (Exception $e) {
        $clearError = true;
    }
}

// ---- Data ----
$stats = [
    'total'      => 0,
    'today'      => 0,
    'uniq_today' => 0,
    'uniq_total' => 0,
    'pages'      => [],
    'pages_prev' => [],
    'referrers'  => [],
    'devices'    => ['desktop' => 0, 'mobile' => 0, 'tablet' => 0],
    'countries'  => [],
    'trend'      => [],
    'hours'      => array_fill(0, 24, ['views' => 0, 'uniq' => []]),
    'recent'     => [],
    'db_size'    => 0,
    'db_rows'    => 0,
];

if ($authed) {
    $db = openDb();
    if ($db) {
        $today     = date('Y-m-d');
        $trendFrom = date('Y-m-d', strtotime('-' . (TREND_DAYS - 1) . ' days'));
        $weekStart = date('Y-m-d', strtotime('Monday this week'));
        $prevStart = date('Y-m-d', strtotime('Monday last week'));
        $prevEnd   = date('Y-m-d', strtotime('Sunday last week'));

        // Total views
        $stats['total'] = (int) $db->querySingle('SELECT COUNT(*) FROM hits');

        // Today views
        $stats['today'] = (int) $db->querySingle("SELECT COUNT(*) FROM hits WHERE date = '$today'");

        // Unique visitors today
        $stats['uniq_today'] = (int) $db->querySingle("SELECT COUNT(DISTINCT vid) FROM hits WHERE date = '$today' AND vid != ''");

        // Unique visitors total
        $stats['uniq_total'] = (int) $db->querySingle("SELECT COUNT(DISTINCT vid) FROM hits WHERE vid != ''");

        // Top pages (all time)
        $res = $db->query("SELECT page, COUNT(*) as c FROM hits GROUP BY page ORDER BY c DESC LIMIT 10");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats['pages'][$row['page']] = $row['c'];
        }

        // Top pages (last week for delta)
        $res = $db->query("SELECT page, COUNT(*) as c FROM hits WHERE date >= '$prevStart' AND date <= '$prevEnd' GROUP BY page ORDER BY c DESC");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats['pages_prev'][$row['page']] = $row['c'];
        }

        // Referrers
        $res = $db->query("SELECT referrer, COUNT(*) as c FROM hits WHERE referrer != '' GROUP BY referrer ORDER BY c DESC LIMIT 10");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $ref = preg_replace('/^www\./', '', parse_url($row['referrer'], PHP_URL_HOST) ?: $row['referrer']);
            $stats['referrers'][$ref] = ($stats['referrers'][$ref] ?? 0) + $row['c'];
        }
        arsort($stats['referrers']);

        // Devices
        $res = $db->query("SELECT device, COUNT(*) as c FROM hits GROUP BY device");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $dev = $row['device'] ?: 'desktop';
            $stats['devices'][$dev] = ($stats['devices'][$dev] ?? 0) + $row['c'];
        }

        // Countries
        $res = $db->query("SELECT country, COUNT(*) as c FROM hits WHERE country != '' GROUP BY country ORDER BY c DESC LIMIT 20");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats['countries'][$row['country']] = $row['c'];
        }

        // Trend (last N days)
        for ($i = TREND_DAYS - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $stats['trend'][$d] = ['views' => 0, 'uniq' => 0];
        }
        $res = $db->query("SELECT date, COUNT(*) as views, COUNT(DISTINCT vid) as uniq FROM hits WHERE date >= '$trendFrom' GROUP BY date");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            if (isset($stats['trend'][$row['date']])) {
                $stats['trend'][$row['date']] = ['views' => $row['views'], 'uniq' => $row['uniq']];
            }
        }

        // Hours
        $res = $db->query("SELECT CAST(substr(time,1,2) AS INTEGER) as h, COUNT(*) as views, COUNT(DISTINCT vid) as uniq FROM hits GROUP BY h");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $h = (int) $row['h'];
            if ($h >= 0 && $h < 24) {
                $stats['hours'][$h] = ['views' => $row['views'], 'uniq' => $row['uniq']];
            }
        }

        // Recent hits
        $res = $db->query("SELECT date,time,page,referrer,device,country FROM hits ORDER BY id DESC LIMIT " . RECENT_ENTRIES);
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats['recent'][] = $row;
        }

        // DB info
        $stats['db_size'] = file_exists(DB_PATH) ? round(filesize(DB_PATH) / 1024, 1) : 0;
        $stats['db_rows'] = $stats['total'];

        $db->close();
    }
}

$trendMax = 1;
foreach ($stats['trend'] as $t) $trendMax = max($trendMax, $t['views']);
$hourMax  = max(array_merge([1], array_column($stats['hours'], 'views')));
$devTotal = array_sum($stats['devices']);

$lockRemaining = '';
if ($isLocked) {
    $secs = $_SESSION['locked_until'] - time();
    $lockRemaining = $secs > 60 ? round($secs/60).' min' : $secs.' sec';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($brandName) ?> · Stats</title>
<?php if ($brandFontUrl): ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="<?= htmlspecialchars($brandFontUrl) ?>" rel="stylesheet">
<?php endif; ?>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --accent:  <?= htmlspecialchars($brandColor) ?>;
    --bg:      #faf8f5;
    --surface: #ffffff;
    --border:  #e8e0d4;
    --text:    #2c2416;
    --muted:   #8a7a68;
    --danger:  #c0392b;
    --font:    <?= htmlspecialchars($brandFont) ?>;
    --sans:    system-ui, -apple-system, sans-serif;
  }
  body { font-family: var(--sans); background: var(--bg); color: var(--text); min-height: 100vh; }

  /* Login */
  .login-wrap { display:flex; align-items:center; justify-content:center; min-height:100vh; padding:1rem; }
  .login-box { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:2.5rem 2rem; width:100%; max-width:360px; box-shadow:0 4px 24px rgba(44,36,22,.07); }
  .login-logo { margin-bottom:1.5rem; }
  .login-logo img { max-height:36px; max-width:160px; }
  .login-box h1 { font-family:var(--font); font-size:1.5rem; font-weight:700; margin-bottom:.25rem; }
  .login-box .sub { font-size:.78rem; color:var(--muted); margin-bottom:1.75rem; text-transform:uppercase; letter-spacing:.05em; }
  .login-box input[type=password] { width:100%; padding:.7rem 1rem; border:1px solid var(--border); border-radius:10px; font-size:.95rem; background:var(--bg); color:var(--text); outline:none; transition:border-color .15s; }
  .login-box input[type=password]:focus { border-color:var(--accent); }
  .login-box input:disabled, .login-box button:disabled { opacity:.4; cursor:not-allowed; }
  .login-box button { margin-top:.6rem; width:100%; padding:.75rem; background:var(--accent); color:#fff; border:none; border-radius:10px; font-size:.95rem; font-weight:500; cursor:pointer; transition:opacity .15s; }
  .login-box button:hover:not(:disabled) { opacity:.88; }
  .login-error  { margin-top:.6rem; font-size:.8rem; color:var(--accent); }
  .login-locked { margin-top:.6rem; font-size:.8rem; color:#888; background:#f5f0eb; padding:.5rem .75rem; border-radius:6px; }
  .attempts-hint { font-size:.72rem; color:var(--muted); margin-top:.4rem; }

  /* Header */
  header { background:var(--surface); border-bottom:1px solid var(--border); padding:1rem 1.5rem; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
  .header-brand { display:flex; align-items:center; gap:.75rem; }
  .header-brand img { max-height:28px; max-width:120px; }
  .header-brand h1 { font-family:var(--font); font-size:1.2rem; font-weight:700; }
  .header-meta { font-size:.75rem; color:var(--muted); }
  .header-actions { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
  .btn-ghost { background:none; border:1px solid var(--border); border-radius:8px; padding:.3rem .75rem; font-size:.78rem; color:var(--muted); cursor:pointer; transition:all .15s; text-decoration:none; display:inline-block; }
  .btn-ghost:hover { border-color:var(--accent); color:var(--accent); }
  .btn-export { background:var(--accent); color:#fff; border:none; border-radius:8px; padding:.3rem .75rem; font-size:.78rem; cursor:pointer; text-decoration:none; display:inline-block; transition:opacity .15s; }
  .btn-export:hover { opacity:.85; }

  /* Layout */
  main { max-width:1160px; margin:0 auto; padding:2rem 1.5rem; }

  /* KPI */
  .kpi-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:14px; margin-bottom:1.5rem; }
  .kpi { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:1.25rem 1.25rem 1rem; }
  .kpi.highlight { border-left:3px solid var(--accent); }
  .kpi-label { font-size:.65rem; text-transform:uppercase; letter-spacing:.12em; color:var(--accent); margin-bottom:.5rem; }
  .kpi-value { font-family:var(--font); font-size:1.9rem; font-weight:700; line-height:1; }
  .kpi-sub { font-size:.72rem; color:var(--muted); margin-top:.3rem; }

  /* Cards */
  .card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:1.5rem; margin-bottom:14px; }
  .card h2 { font-family:var(--font); font-size:.95rem; font-weight:700; margin-bottom:1rem; padding-bottom:.75rem; border-bottom:1px solid var(--border); }
  .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px; }
  .grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:14px; }
  @media (max-width:900px) { .grid-3 { grid-template-columns:1fr 1fr; } }
  @media (max-width:640px) { .grid-2, .grid-3 { grid-template-columns:1fr; } }

  /* Trend */
  .trend-chart { display:flex; align-items:flex-end; gap:3px; height:80px; }
  .bar-col { flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end; gap:3px; cursor:default; position:relative; }
  .bar-views { width:100%; background:var(--accent); border-radius:3px 3px 0 0; min-height:2px; opacity:.75; transition:opacity .15s; position:relative; }
  .bar-uniq  { width:60%; background:var(--accent); border-radius:2px 2px 0 0; min-height:1px; opacity:.35; position:absolute; bottom:0; left:20%; }
  .bar-col:hover .bar-views { opacity:1; }
  .bar-col .lbl { font-size:.52rem; color:var(--muted); }
  .trend-legend { display:flex; gap:1rem; margin-top:.6rem; }
  .legend-dot { display:inline-block; width:8px; height:8px; border-radius:2px; margin-right:.3rem; vertical-align:middle; }

  /* Hours */
  .hours-chart { display:flex; align-items:flex-end; gap:2px; height:60px; }
  .hour-col { flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end; gap:2px; }
  .hour-col .bar { width:100%; background:var(--accent); opacity:.65; border-radius:2px 2px 0 0; min-height:2px; transition:opacity .15s; }
  .hour-col:hover .bar { opacity:1; }
  .hour-col .lbl { font-size:.48rem; color:var(--muted); }

  /* Rank list */
  .rank-list { list-style:none; }
  .rank-list li { display:flex; align-items:center; gap:.6rem; padding:.45rem 0; border-bottom:1px solid var(--border); font-size:.82rem; }
  .rank-list li:last-child { border-bottom:none; }
  .rank-n { font-size:.68rem; color:var(--muted); width:1.2rem; text-align:right; flex-shrink:0; }
  .rank-label { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .rank-track { width:60px; height:5px; background:var(--bg); border-radius:3px; overflow:hidden; flex-shrink:0; }
  .rank-fill  { height:100%; background:var(--accent); opacity:.6; border-radius:3px; }
  .rank-count { font-size:.8rem; font-weight:600; width:2.2rem; text-align:right; flex-shrink:0; }
  .delta { font-size:.68rem; width:2.8rem; text-align:right; flex-shrink:0; }
  .delta.up   { color:#2d6a4f; }
  .delta.down { color:#b5341b; }
  .delta.same { color:var(--muted); }

  /* Device */
  .dev-row { display:flex; align-items:center; gap:.75rem; padding:.4rem 0; }
  .dev-label { font-size:.8rem; width:4.5rem; flex-shrink:0; }
  .dev-track { flex:1; height:7px; background:var(--bg); border-radius:4px; overflow:hidden; }
  .dev-fill { height:100%; background:var(--accent); border-radius:4px; }
  .dev-fill.mobile { opacity:.7; }
  .dev-fill.tablet { opacity:.45; }
  .dev-pct { font-size:.75rem; color:var(--muted); width:2.5rem; text-align:right; flex-shrink:0; }

  /* World map */
  .map-wrap { position:relative; width:100%; }
  .map-wrap svg { width:100%; height:auto; display:block; }
  .country-shape { fill:#f0e8de; stroke:#fff; stroke-width:.5; transition:fill .2s; cursor:default; }
  .country-shape.has-data { fill:var(--accent); }
  .country-shape.has-data:hover { opacity:.8; }
  .map-tooltip { position:fixed; background:var(--text); color:#fff; padding:.3rem .6rem; border-radius:6px; font-size:.72rem; pointer-events:none; display:none; z-index:100; white-space:nowrap; }

  /* Table */
  .tbl-wrap { overflow-x:auto; }
  table { width:100%; border-collapse:collapse; font-size:.8rem; }
  thead th { text-align:left; padding:.5rem .75rem; font-size:.65rem; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); border-bottom:2px solid var(--border); background:var(--bg); white-space:nowrap; }
  tbody tr:hover { background:var(--bg); }
  tbody td { padding:.42rem .75rem; border-bottom:1px solid var(--border); max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  tbody tr:last-child td { border-bottom:none; }
  .muted { color:var(--muted); }
  .badge { display:inline-block; padding:.15rem .5rem; border-radius:20px; font-size:.65rem; text-transform:uppercase; letter-spacing:.05em; }
  .badge-desktop { background:#f5ede4; color:var(--accent); }
  .badge-mobile  { background:#fdf3e8; color:#b8712e; }
  .badge-tablet  { background:#fef7ee; color:#c08040; }

  /* Danger zone */
  .danger-zone { border:1px solid #f5c6c2; border-radius:14px; padding:1.5rem; margin-bottom:14px; background:#fff9f9; }
  .danger-zone h2 { font-family:var(--font); font-size:.95rem; font-weight:700; color:var(--danger); margin-bottom:.5rem; }
  .danger-zone p { font-size:.82rem; color:var(--muted); margin-bottom:1rem; }
  .danger-zone .db-info { font-size:.78rem; color:var(--muted); margin-bottom:1rem; font-family:monospace; background:#f5f0eb; padding:.5rem .75rem; border-radius:6px; }
  .btn-danger { background:var(--danger); color:#fff; border:none; border-radius:8px; padding:.5rem 1rem; font-size:.82rem; cursor:pointer; transition:opacity .15s; }
  .btn-danger:hover { opacity:.85; }
  .confirm-box { margin-top:.75rem; display:none; }
  .confirm-box.visible { display:block; }
  .confirm-box p { font-size:.8rem; color:var(--danger); margin-bottom:.5rem; }

  .alert-success { background:#e8f5e9; border:1px solid #a5d6a7; color:#2d6a4f; padding:.6rem 1rem; border-radius:8px; font-size:.82rem; margin-bottom:1rem; }
  .file-info { font-size:.72rem; color:var(--muted); margin-top:.75rem; }
  .section-note { font-size:.72rem; color:var(--muted); margin-top:.5rem; }
  .empty { text-align:center; padding:2.5rem; color:var(--muted); font-size:.85rem; }
  .no-data { color:var(--muted); font-size:.82rem; padding:.5rem 0; }
</style>
</head>
<body>

<?php if (!$authed): ?>
<div class="login-wrap">
  <div class="login-box">
    <?php if ($brandLogo): ?>
      <div class="login-logo"><img src="<?= htmlspecialchars($brandLogo) ?>" alt="<?= htmlspecialchars($brandName) ?>"></div>
    <?php endif; ?>
    <h1><?= htmlspecialchars($brandName) ?></h1>
    <div class="sub">Analytics Dashboard</div>
    <form method="POST">
      <input type="password" name="password" placeholder="Password" autofocus autocomplete="current-password" <?= $isLocked ? 'disabled' : '' ?>>
      <button type="submit" <?= $isLocked ? 'disabled' : '' ?>>Login</button>
      <?php if ($isLocked): ?>
        <p class="login-locked">Too many failed attempts. Try again in <?= $lockRemaining ?>.</p>
      <?php elseif (!empty($authError)): ?>
        <p class="login-error">✗ Wrong password</p>
        <?php $rem = $maxAttempts - $attempts; if ($rem <= 2 && $rem > 0): ?>
          <p class="attempts-hint"><?= $rem ?> attempt<?= $rem === 1 ? '' : 's' ?> remaining before lockout.</p>
        <?php endif; ?>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php else: ?>

<header>
  <div class="header-brand">
    <?php if ($brandLogo): ?>
      <img src="<?= htmlspecialchars($brandLogo) ?>" alt="<?= htmlspecialchars($brandName) ?>">
    <?php else: ?>
      <h1><?= htmlspecialchars($brandName) ?></h1>
    <?php endif; ?>
  </div>
  <span class="header-meta"><?= date('d.m.Y · H:i') ?> · <?= TIMEZONE ?></span>
  <div class="header-actions">
    <a href="?export=1" class="btn-export">↓ Export CSV</a>
    <a href="?" class="btn-ghost">Refresh</a>
    <a href="?logout=1" class="btn-ghost">Logout</a>
  </div>
</header>

<main>

<?php if (!empty($clearSuccess)): ?>
  <div class="alert-success">All data cleared successfully.</div>
<?php endif; ?>

<?php if ($stats['total'] === 0): ?>
  <div class="card"><div class="empty">No data yet — add the tracking snippet to your site to get started.</div></div>
<?php else: ?>

<!-- KPIs -->
<div class="kpi-row">
  <div class="kpi highlight">
    <div class="kpi-label">Total Views</div>
    <div class="kpi-value"><?= number_format($stats['total']) ?></div>
  </div>
  <div class="kpi highlight">
    <div class="kpi-label">Est. Visitors</div>
    <div class="kpi-value"><?= number_format($stats['uniq_total']) ?></div>
    <div class="kpi-sub">approx. unique</div>
  </div>
  <div class="kpi">
    <div class="kpi-label">Today Views</div>
    <div class="kpi-value"><?= number_format($stats['today']) ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label">Today Visitors</div>
    <div class="kpi-value"><?= number_format($stats['uniq_today']) ?></div>
    <div class="kpi-sub">approx. unique</div>
  </div>
  <div class="kpi">
    <div class="kpi-label">Pages</div>
    <div class="kpi-value"><?= number_format(count($stats['pages'])) ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label">Referrers</div>
    <div class="kpi-value"><?= number_format(count($stats['referrers'])) ?></div>
  </div>
</div>

<!-- Trend -->
<div class="card">
  <h2><?= TREND_DAYS ?>-Day Trend</h2>
  <div class="trend-chart">
    <?php foreach ($stats['trend'] as $d => $t):
      $hv = $trendMax > 0 ? max(2, round($t['views'] / $trendMax * 100)) : 2;
      $hu = $t['views'] > 0 ? max(1, round($t['uniq'] / $t['views'] * $hv)) : 0;
    ?>
    <div class="bar-col" title="<?= $d ?>&#10;Views: <?= $t['views'] ?>&#10;Visitors: <?= $t['uniq'] ?>">
      <div class="bar-views" style="height:<?= $hv ?>%">
        <div class="bar-uniq" style="height:<?= ($hv > 0 ? round($hu/$hv*100) : 0) ?>%"></div>
      </div>
      <div class="lbl"><?= date('d', strtotime($d)) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="trend-legend">
    <span><span class="legend-dot" style="background:var(--accent);opacity:.75"></span><span style="font-size:.72rem;color:var(--muted)">Pageviews</span></span>
    <span><span class="legend-dot" style="background:var(--accent);opacity:.35"></span><span style="font-size:.72rem;color:var(--muted)">Est. Visitors</span></span>
  </div>
</div>

<!-- Pages + Referrers -->
<div class="grid-2">
  <div class="card">
    <h2>Top Pages</h2>
    <?php if (empty($stats['pages'])): ?>
      <p class="no-data">No data yet</p>
    <?php else:
      $maxP = max($stats['pages']); $i = 1; ?>
      <ul class="rank-list">
      <?php foreach ($stats['pages'] as $p => $c):
        $prev   = $stats['pages_prev'][$p] ?? 0;
        $diff   = $c - $prev;
        $dClass = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'same');
        $dLabel = $diff > 0 ? '+' . $diff : ($diff < 0 ? (string)$diff : '—');
      ?>
        <li>
          <span class="rank-n"><?= $i++ ?></span>
          <span class="rank-label" title="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></span>
          <div class="rank-track"><div class="rank-fill" style="width:<?= round($c/$maxP*100) ?>%"></div></div>
          <span class="rank-count"><?= $c ?></span>
          <span class="delta <?= $dClass ?>"><?= $dLabel ?></span>
        </li>
      <?php endforeach; ?>
      </ul>
      <p class="section-note">Delta = this week vs. last week</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Referrers</h2>
    <?php if (empty($stats['referrers'])): ?>
      <p class="no-data">No referrers yet</p>
    <?php else:
      $maxR = max($stats['referrers']); $i = 1; ?>
      <ul class="rank-list">
      <?php foreach ($stats['referrers'] as $r => $c): ?>
        <li>
          <span class="rank-n"><?= $i++ ?></span>
          <span class="rank-label" title="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></span>
          <div class="rank-track"><div class="rank-fill" style="width:<?= round($c/$maxR*100) ?>%"></div></div>
          <span class="rank-count"><?= $c ?></span>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<!-- Hours + Device + Countries -->
<div class="grid-3">
  <div class="card">
    <h2>Time of Day</h2>
    <div class="hours-chart">
      <?php for ($h = 0; $h < 24; $h++):
        $hh = $hourMax > 0 ? max(2, round($stats['hours'][$h]['views'] / $hourMax * 100)) : 2; ?>
        <div class="hour-col" title="<?= sprintf('%02d', $h) ?>:00 — <?= $stats['hours'][$h]['views'] ?> views">
          <div class="bar" style="height:<?= $hh ?>%"></div>
          <div class="lbl"><?= $h % 6 === 0 ? sprintf('%02d', $h) : '' ?></div>
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <div class="card">
    <h2>Device Type</h2>
    <?php foreach (['desktop','mobile','tablet'] as $type):
      $pct = $devTotal > 0 ? round($stats['devices'][$type] / $devTotal * 100) : 0; ?>
      <div class="dev-row">
        <span class="dev-label"><?= ucfirst($type) ?></span>
        <div class="dev-track"><div class="dev-fill <?= $type ?>" style="width:<?= $pct ?>%"></div></div>
        <span class="dev-pct"><?= $pct ?>%</span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>Top Countries</h2>
    <?php if (empty($stats['countries'])): ?>
      <p class="no-data">Geo disabled or no data</p>
    <?php else:
      $maxC = max($stats['countries']); $i = 1; ?>
      <ul class="rank-list">
      <?php foreach (array_slice($stats['countries'], 0, 7, true) as $co => $c): ?>
        <li>
          <span class="rank-n"><?= $i++ ?></span>
          <span class="rank-label"><?= htmlspecialchars($co) ?></span>
          <div class="rank-track"><div class="rank-fill" style="width:<?= round($c/$maxC*100) ?>%"></div></div>
          <span class="rank-count"><?= $c ?></span>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<!-- World Map -->
<?php if (!empty($stats['countries'])): ?>
<div class="card">
  <h2>World Map</h2>
  <?php $jsCountries = json_encode($stats['countries']); $jsMax = max($stats['countries']); ?>
  <div class="map-wrap">
    <div class="map-tooltip" id="map-tip"></div>
    <svg viewBox="0 0 1010 666" xmlns="http://www.w3.org/2000/svg" id="world-map">
      <g id="countries">
        <path class="country-shape" id="US" d="M155,180 L155,200 L185,200 L195,215 L220,215 L220,200 L240,195 L255,200 L255,215 L270,215 L275,205 L290,205 L295,195 L310,190 L315,175 L300,165 L285,160 L270,155 L250,150 L230,148 L215,150 L200,155 L185,160 L170,165 Z"/>
        <path class="country-shape" id="CA" d="M100,100 L100,155 L155,180 L170,165 L185,160 L200,155 L215,150 L230,148 L250,150 L260,140 L270,125 L265,110 L250,100 L230,90 L210,85 L190,80 L170,80 L150,85 L130,90 Z"/>
        <path class="country-shape" id="MX" d="M155,215 L155,235 L170,250 L185,255 L200,255 L210,245 L215,235 L215,215 L195,215 L185,200 L155,200 Z"/>
        <path class="country-shape" id="BR" d="M255,280 L250,310 L255,340 L270,360 L285,370 L300,375 L320,370 L335,360 L345,340 L345,310 L335,285 L320,270 L305,265 L290,265 L275,268 Z"/>
        <path class="country-shape" id="AR" d="M270,370 L265,400 L268,430 L275,455 L285,465 L295,460 L300,440 L300,415 L300,375 L285,370 Z"/>
        <path class="country-shape" id="GB" d="M460,140 L455,150 L460,160 L470,165 L475,155 L475,145 L468,138 Z"/>
        <path class="country-shape" id="FR" d="M465,165 L460,175 L462,188 L472,195 L482,192 L488,182 L485,170 L475,163 Z"/>
        <path class="country-shape" id="DE" d="M480,148 L475,158 L478,170 L488,175 L498,170 L502,160 L498,150 L488,145 Z"/>
        <path class="country-shape" id="ES" d="M455,190 L450,202 L455,215 L468,220 L480,218 L487,208 L484,195 L472,188 Z"/>
        <path class="country-shape" id="IT" d="M482,175 L480,185 L482,200 L488,210 L495,215 L500,208 L498,195 L494,182 L490,175 Z"/>
        <path class="country-shape" id="PL" d="M495,148 L490,158 L492,168 L502,172 L512,168 L515,158 L510,148 L500,144 Z"/>
        <path class="country-shape" id="UA" d="M515,148 L510,160 L512,172 L522,178 L535,175 L540,165 L538,153 L528,146 Z"/>
        <path class="country-shape" id="RU" d="M510,90 L505,110 L510,130 L530,140 L560,145 L600,148 L640,145 L680,140 L710,135 L730,125 L735,110 L725,98 L700,90 L670,85 L640,82 L610,82 L580,85 L555,88 L530,90 Z"/>
        <path class="country-shape" id="TR" d="M535,188 L530,198 L535,210 L548,215 L562,212 L568,202 L565,190 L553,185 Z"/>
        <path class="country-shape" id="CN" d="M660,175 L655,195 L660,218 L678,232 L700,238 L720,235 L735,222 L738,205 L732,188 L718,178 L700,172 L682,170 Z"/>
        <path class="country-shape" id="IN" d="M625,220 L620,240 L622,265 L632,285 L645,295 L658,292 L665,278 L665,258 L660,238 L648,222 Z"/>
        <path class="country-shape" id="JP" d="M740,175 L735,185 L738,198 L748,205 L758,202 L762,190 L758,178 L748,172 Z"/>
        <path class="country-shape" id="AU" d="M710,340 L705,365 L710,390 L728,408 L748,415 L768,410 L780,395 L782,372 L775,352 L760,338 L742,330 Z"/>
        <path class="country-shape" id="ZA" d="M510,380 L505,400 L510,420 L522,432 L535,435 L548,428 L555,412 L552,395 L540,382 L525,375 Z"/>
        <path class="country-shape" id="EG" d="M525,225 L520,238 L522,252 L532,260 L542,258 L548,245 L545,232 L535,224 Z"/>
        <path class="country-shape" id="NG" d="M488,268 L483,282 L486,298 L496,308 L508,308 L515,298 L513,282 L502,270 Z"/>
        <path class="country-shape" id="SA" d="M558,228 L553,242 L555,258 L565,268 L578,268 L585,258 L583,242 L572,230 Z"/>
        <path class="country-shape" id="ID" d="M700,285 L695,295 L698,308 L710,315 L722,312 L728,300 L725,288 L712,280 Z"/>
        <path class="country-shape" id="KR" d="M730,185 L725,195 L728,205 L738,210 L745,205 L748,195 L742,185 Z"/>
        <path class="country-shape" id="AT" d="M492,162 L488,170 L490,178 L498,182 L506,178 L508,168 L503,160 Z"/>
        <path class="country-shape" id="CH" d="M476,168 L472,176 L475,184 L483,188 L490,183 L491,174 L486,167 Z"/>
        <path class="country-shape" id="NL" d="M474,148 L470,155 L472,163 L480,166 L486,161 L487,153 L481,147 Z"/>
        <path class="country-shape" id="BE" d="M470,158 L466,165 L468,173 L476,176 L482,171 L482,163 L476,157 Z"/>
        <path class="country-shape" id="SE" d="M492,108 L488,122 L490,138 L500,146 L510,143 L514,130 L512,116 L502,108 Z"/>
        <path class="country-shape" id="NO" d="M475,100 L470,115 L472,130 L482,140 L492,138 L496,125 L493,110 L484,100 Z"/>
        <path class="country-shape" id="FI" d="M505,105 L500,120 L502,135 L512,142 L522,138 L525,124 L522,110 L512,104 Z"/>
        <path class="country-shape" id="DK" d="M480,135 L476,143 L480,152 L488,155 L494,150 L495,142 L490,135 Z"/>
        <path class="country-shape" id="PT" d="M448,192 L444,202 L446,215 L454,220 L460,215 L461,202 L457,192 Z"/>
        <path class="country-shape" id="GR" d="M508,198 L503,208 L505,220 L514,226 L522,222 L525,210 L521,198 Z"/>
        <path class="country-shape" id="RO" d="M515,165 L510,175 L512,185 L522,190 L530,187 L532,177 L528,167 Z"/>
        <path class="country-shape" id="CZ" d="M494,158 L490,166 L492,174 L500,178 L508,174 L510,166 L505,158 Z"/>
        <path class="country-shape" id="HU" d="M504,170 L500,178 L502,186 L510,190 L518,186 L520,178 L515,170 Z"/>
        <path class="country-shape" id="CO" d="M228,268 L223,280 L225,295 L235,305 L246,303 L250,291 L247,278 L237,268 Z"/>
        <path class="country-shape" id="PK" d="M610,200 L605,215 L608,230 L620,240 L630,238 L635,224 L632,210 L620,202 Z"/>
        <path class="country-shape" id="IR" d="M570,195 L565,208 L568,225 L578,235 L590,233 L595,220 L592,206 L582,196 Z"/>
        <path class="country-shape" id="KZ" d="M578,148 L573,165 L576,182 L590,192 L605,190 L610,175 L606,160 L594,150 Z"/>
        <path class="country-shape" id="TH" d="M672,248 L667,262 L670,278 L680,288 L690,285 L694,272 L690,258 L680,248 Z"/>
        <path class="country-shape" id="VN" d="M688,248 L683,262 L686,278 L695,285 L702,280 L705,265 L700,250 Z"/>
        <path class="country-shape" id="MY" d="M690,285 L685,295 L688,305 L698,310 L706,305 L708,293 L702,283 Z"/>
        <path class="country-shape" id="MM" d="M662,238 L657,252 L660,268 L670,278 L680,275 L683,260 L678,244 Z"/>
        <path class="country-shape" id="KE" d="M540,295 L535,310 L538,325 L548,332 L558,328 L560,315 L556,300 Z"/>
        <path class="country-shape" id="ET" d="M540,268 L535,282 L538,298 L548,306 L558,303 L560,288 L555,274 Z"/>
        <path class="country-shape" id="DZ" d="M470,210 L465,225 L468,242 L478,252 L490,250 L493,236 L488,222 Z"/>
        <path class="country-shape" id="MA" d="M452,205 L447,218 L450,232 L460,240 L470,237 L472,224 L468,210 Z"/>
      </g>
    </svg>
  </div>
  <p class="section-note">Country detection requires GEO_ENABLED in config.php</p>
</div>
<?php endif; ?>

<!-- Recent hits -->
<div class="card">
  <h2>Recent <?= RECENT_ENTRIES ?> Hits</h2>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr><th>Date</th><th>Time</th><th>Page</th><th>Referrer</th><th>Device</th><th>Country</th></tr>
      </thead>
      <tbody>
        <?php foreach ($stats['recent'] as $r): ?>
        <tr>
          <td class="muted"><?= htmlspecialchars($r['date']) ?></td>
          <td class="muted"><?= htmlspecialchars($r['time']) ?></td>
          <td><?= htmlspecialchars($r['page']) ?></td>
          <td class="muted"><?= !empty($r['referrer']) ? htmlspecialchars(preg_replace('/^www\./', '', parse_url($r['referrer'], PHP_URL_HOST) ?: $r['referrer'])) : '—' ?></td>
          <td><span class="badge badge-<?= htmlspecialchars($r['device'] ?: 'desktop') ?>"><?= htmlspecialchars($r['device'] ?: 'desktop') ?></span></td>
          <td><?= htmlspecialchars($r['country'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="file-info">analytics.db · <?= $stats['db_size'] ?> KB · <?= number_format($stats['db_rows']) ?> rows · powered by <a href="https://github.com/ludescherstudio/pimabox" style="color:var(--muted)">pimabox</a></p>
</div>

<?php endif; // total > 0 ?>

<!-- Danger Zone (Advanced Mode only) -->
<?php if ($advancedMode): ?>
<div class="danger-zone">
  <h2>⚠ Danger Zone</h2>
  <p>These actions are irreversible. Only visible because ADVANCED_MODE is enabled in config.php.</p>
  <div class="db-info">
    analytics.db · <?= $stats['db_size'] ?> KB · <?= number_format($stats['db_rows']) ?> rows
  </div>
  <button class="btn-danger" onclick="document.getElementById('confirm-clear').classList.toggle('visible')">Clear all data</button>
  <div class="confirm-box" id="confirm-clear">
    <p>This will permanently delete all <?= number_format($stats['db_rows']) ?> rows. Are you sure?</p>
    <form method="POST">
      <input type="hidden" name="clear_data" value="1">
      <input type="hidden" name="confirm_clear" value="yes">
      <button type="submit" class="btn-danger">Yes, delete everything</button>
    </form>
  </div>
</div>
<?php endif; ?>

</main>

<script>
(function() {
  const data   = <?= $jsCountries ?? '{}' ?>;
  const max    = <?= $jsMax ?? 1 ?>;
  const tip    = document.getElementById('map-tip');
  if (!tip) return;
  Object.entries(data).forEach(([code, count]) => {
    const el = document.getElementById(code);
    if (!el) return;
    el.classList.add('has-data');
    el.style.opacity = 0.2 + (count / max) * 0.8;
    el.addEventListener('mousemove', e => {
      tip.style.display = 'block';
      tip.style.left = (e.clientX + 12) + 'px';
      tip.style.top  = (e.clientY - 28) + 'px';
      tip.textContent = code + ': ' + count.toLocaleString() + ' views';
    });
    el.addEventListener('mouseleave', () => { tip.style.display = 'none'; });
  });
})();
</script>

<?php endif; // authed ?>
</body>
</html>
