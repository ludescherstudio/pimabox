<?php
/**
 * MentaMap — stats.php
 * Passwortgeschütztes Dashboard für Gamelog-Auswertung.
 */

// --- Passwortschutz ---
// Passwort hier und in config.php (ADMIN_PASSWORD) synchron halten
$adminPw = 'mentamap2026';
@include_once __DIR__ . '/config.php';
if (defined('ADMIN_PASSWORD')) $adminPw = ADMIN_PASSWORD;

session_start();

// Login via POST — Passwort nie in der URL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pw'])) {
    if ($_POST['pw'] === $adminPw) {
        $_SESSION['stats_auth'] = true;
    }
    header('Location: /stats');
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['stats_auth']);
    header('Location: /stats');
    exit;
}

if (empty($_SESSION['stats_auth'])) {
    http_response_code(403);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Stats</title>
    <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#faf8f5;}
    form{display:flex;gap:8px;}input{padding:10px 14px;border:1px solid #ccc;border-radius:8px;font-size:14px;}
    button{padding:10px 18px;background:#c4773a;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px;}</style>
    </head><body><form method="post">
    <input type="password" name="pw" placeholder="Passwort" autofocus>
    <button>Login</button>
    </form></body></html>');
}

// --- CSV einlesen ---
$csvFile = __DIR__ . '/cache/gamelog.csv';
$entries = [];

if (file_exists($csvFile)) {
    $lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        [$datetime, $test, $typ, $type] = array_pad(explode(',', $line, 4), 4, '');
        $entries[] = compact('datetime', 'test', 'typ', 'type');
    }
}

$entries = array_reverse($entries); // neueste zuerst
$total   = count($entries);
$flash   = count(array_filter($entries, fn($e) => $e['type'] === 'flash'));
$pro     = count(array_filter($entries, fn($e) => $e['type'] === 'pro'));
$convRate = $flash > 0 ? round($pro / $flash * 100, 1) : 0;

// Per Test
$byTest = [];
foreach ($entries as $e) {
    $byTest[$e['test']][$e['type']] = ($byTest[$e['test']][$e['type']] ?? 0) + 1;
}

// Archetypen-Ranking
$archetypes = [];
foreach ($entries as $e) {
    if ($e['typ']) {
        $archetypes[$e['typ']]['count'] = ($archetypes[$e['typ']]['count'] ?? 0) + 1;
        $archetypes[$e['typ']]['test']  = $e['test']; // gleicher Archetyp → immer gleicher Test
    }
}
uasort($archetypes, fn($a, $b) => $b['count'] - $a['count']);

// Feedback einlesen
$feedbackFile = __DIR__ . '/data/feedback.csv';
$fbUp = 0; $fbDown = 0; $fbByTyp = [];
if (file_exists($feedbackFile)) {
    $fbLines = file($feedbackFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($fbLines as $i => $line) {
        if ($i === 0 && str_starts_with($line, 'datum')) continue; // Header
        $parts = explode(',', $line, 4);
        $vote  = $parts[2] ?? '';
        $typ   = trim($parts[3] ?? '');
        if ($vote === 'up')   $fbUp++;
        if ($vote === 'down') $fbDown++;
        if ($typ) {
            $fbByTyp[$typ]['up']   = ($fbByTyp[$typ]['up']   ?? 0) + ($vote === 'up'   ? 1 : 0);
            $fbByTyp[$typ]['down'] = ($fbByTyp[$typ]['down'] ?? 0) + ($vote === 'down' ? 1 : 0);
        }
    }
}
$fbTotal   = $fbUp + $fbDown;
$fbUpRate  = $fbTotal > 0 ? round($fbUp / $fbTotal * 100) : 0;
uasort($fbByTyp, fn($a, $b) => ($b['up'] + $b['down']) - ($a['up'] + $a['down']));

// Pro-Tag (letzte 14 Tage)
$byDay = [];
foreach ($entries as $e) {
    $day = substr($e['datetime'], 0, 10);
    $byDay[$day] = ($byDay[$day] ?? 0) + 1;
}
krsort($byDay);
$byDay = array_slice($byDay, 0, 14, true);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MentaMap Stats</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Georgia', serif; background: #faf8f5; color: #2c2416; padding: 40px 24px; }
h1 { font-size: 1.6rem; font-weight: 700; color: #1e1810; margin-bottom: 6px; }
.subtitle { font-family: sans-serif; font-size: 13px; color: #8a7a68; margin-bottom: 36px; }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 36px; }
.card { background: #fff; border: 1px solid #e8e0d4; border-radius: 14px; padding: 22px 20px; }
.card-label { font-family: sans-serif; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #c4773a; margin-bottom: 8px; }
.card-value { font-size: 2rem; font-weight: 700; color: #1e1810; }
.card-sub { font-family: sans-serif; font-size: 12px; color: #8a7a68; margin-top: 4px; }
h2 { font-size: 1rem; font-weight: 700; color: #1e1810; margin-bottom: 14px; }
.section { background: #fff; border: 1px solid #e8e0d4; border-radius: 14px; padding: 24px; margin-bottom: 24px; }
table { width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 13px; }
th { text-align: left; padding: 8px 12px; background: #fdf6ee; color: #8a7a68; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; border-bottom: 1px solid #e8e0d4; }
td { padding: 9px 12px; border-bottom: 1px solid #f0e8de; color: #2c2416; }
tr:last-child td { border-bottom: none; }
.badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-family: sans-serif; }
.badge-flash { background: #fdf0e4; color: #c4773a; }
.badge-pro   { background: #2c2416; color: #f0c896; }
.bar-wrap { background: #f0e8de; border-radius: 4px; height: 6px; margin-top: 6px; }
.bar-fill { background: #c4773a; border-radius: 4px; height: 6px; }
</style>
</head>
<body>

<h1>MentaMap Stats</h1>
<div class="subtitle">
  Stand: <?php echo date('d.m.Y H:i'); ?>
  · <a href="/stats" style="color:#c4773a;">Aktualisieren</a>
  · <a href="/stats?logout=1" style="color:#8a7a68;">Logout</a>
</div>

<!-- KPIs -->
<div class="grid">
  <div class="card">
    <div class="card-label">Gesamt</div>
    <div class="card-value"><?php echo number_format($total, 0, ',', '.'); ?></div>
    <div class="card-sub">Auswertungen total</div>
  </div>
  <div class="card">
    <div class="card-label">Flash</div>
    <div class="card-value"><?php echo number_format($flash, 0, ',', '.'); ?></div>
    <div class="card-sub">Kostenlose Berichte</div>
  </div>
  <div class="card">
    <div class="card-label">Pro</div>
    <div class="card-value"><?php echo number_format($pro, 0, ',', '.'); ?></div>
    <div class="card-sub">Bezahlte Berichte</div>
  </div>
  <div class="card">
    <div class="card-label">Conversion</div>
    <div class="card-value"><?php echo $convRate; ?>%</div>
    <div class="card-sub">Flash → Pro</div>
  </div>
  <div class="card">
    <div class="card-label">Zufriedenheit</div>
    <div class="card-value"><?php echo $fbUpRate; ?>%</div>
    <div class="card-sub">👍 <?php echo $fbUp; ?> · 👎 <?php echo $fbDown; ?> (<?php echo $fbTotal; ?> Stimmen)</div>
  </div>
</div>

<!-- Pro Modul -->
<div class="section">
  <h2>Pro Modul</h2>
  <table>
    <tr><th>Modul</th><th>Flash</th><th>Pro</th></tr>
    <?php foreach ($byTest as $test => $counts): ?>
    <tr>
      <td><?php echo htmlspecialchars($test); ?></td>
      <td><?php echo $counts['flash'] ?? 0; ?></td>
      <td><?php echo $counts['pro']   ?? 0; ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<!-- Archetypen-Ranking -->
<div class="section">
  <h2>Archetypen-Ranking</h2>
  <table>
    <tr><th>Typ</th><th>Modul</th><th>Anzahl</th><th></th></tr>
    <?php $maxA = max(array_column($archetypes, 'count') ?: [1]); ?>
    <?php foreach (array_slice($archetypes, 0, 10, true) as $typ => $data): ?>
    <tr>
      <td><?php echo htmlspecialchars($typ); ?></td>
      <td style="font-family:sans-serif;font-size:11px;color:#8a7a68;"><?php echo htmlspecialchars($data['test']); ?></td>
      <td><?php echo $data['count']; ?></td>
      <td style="width:120px;"><div class="bar-wrap"><div class="bar-fill" style="width:<?php echo round($data['count']/$maxA*100); ?>%"></div></div></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<!-- Feedback pro Archetyp -->
<?php if (!empty($fbByTyp)): ?>
<div class="section">
  <h2>Feedback pro Archetyp</h2>
  <table>
    <tr><th>Archetyp</th><th>Modul</th><th>👍</th><th>👎</th><th>Zufriedenheit</th></tr>
    <?php foreach (array_slice($fbByTyp, 0, 15, true) as $typ => $votes):
      $total   = $votes['up'] + $votes['down'];
      $rate    = $total > 0 ? round($votes['up'] / $total * 100) : 0;
      $fbTest  = $archetypes[$typ]['test'] ?? '';
    ?>
    <tr>
      <td><?php echo htmlspecialchars($typ); ?></td>
      <td style="font-family:sans-serif;font-size:11px;color:#8a7a68;"><?php echo htmlspecialchars($fbTest); ?></td>
      <td><?php echo $votes['up']; ?></td>
      <td><?php echo $votes['down']; ?></td>
      <td>
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="bar-wrap" style="width:80px;"><div class="bar-fill" style="width:<?php echo $rate; ?>%"></div></div>
          <span style="font-family:sans-serif;font-size:12px;color:#8a7a68;"><?php echo $rate; ?>%</span>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

<!-- Letzte 14 Tage -->
<div class="section">
  <h2>Letzte 14 Tage</h2>
  <table>
    <tr><th>Tag</th><th>Auswertungen</th><th></th></tr>
    <?php $maxD = max(array_values($byDay) ?: [1]); ?>
    <?php foreach ($byDay as $day => $count): ?>
    <tr>
      <td><?php echo $day; ?></td>
      <td><?php echo $count; ?></td>
      <td style="width:160px;"><div class="bar-wrap"><div class="bar-fill" style="width:<?php echo round($count/$maxD*100); ?>%"></div></div></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<!-- Letzte Einträge -->
<div class="section">
  <h2>Letzte 50 Einträge</h2>
  <table>
    <tr><th>Zeit</th><th>Modul</th><th>Typ</th><th>Flash/Pro</th></tr>
    <?php foreach (array_slice($entries, 0, 50) as $e): ?>
    <tr>
      <td><?php echo htmlspecialchars($e['datetime']); ?></td>
      <td><?php echo htmlspecialchars($e['test']); ?></td>
      <td><?php echo htmlspecialchars($e['typ']); ?></td>
      <td><span class="badge badge-<?php echo $e['type']; ?>"><?php echo $e['type']; ?></span></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

</body>
</html>