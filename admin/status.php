<?php

$adminKey = getenv('ADMIN_STATUS_KEY');
$providedKey = isset($_GET['key']) ? $_GET['key'] : '';

if (!$adminKey || !hash_equals($adminKey, $providedKey)) {
    http_response_code(403);
    echo '<h1>403 Forbidden</h1><p>Missing or invalid admin key.</p>';
    exit;
}

require_once __DIR__ . '/../classes/PDO.class.php';

$dbStatus = 'ok';
$dbError = '';
$recentRegistrations = array();
$recentLogins = array();
$tableCounts = array();

try {
    $dbh = PDO_DB::factory();

    $stmt = $dbh->query("SELECT sr.userID, u.login, sr.ip, sr.registrationDate
                          FROM stats_register sr
                          JOIN users u ON u.id = sr.userID
                          ORDER BY sr.registrationDate DESC
                          LIMIT 20");
    $recentRegistrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $dbh->query("SELECT id, login, lastLogin
                          FROM users
                          ORDER BY lastLogin DESC
                          LIMIT 20");
    $recentLogins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach (array('users', 'log', 'hardware', 'stats_register') as $t) {
        $stmt = $dbh->query("SELECT COUNT(*) AS c FROM `$t`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $tableCounts[$t] = $row['c'];
    }
} catch (Exception $e) {
    $dbStatus = 'error';
    $dbError = $e->getMessage();
}

$errorLogPath = '/home/runner/workspace/.mysql/run/php-error.log';
$errorLogLines = array();
if (is_readable($errorLogPath)) {
    $lines = file($errorLogPath, FILE_IGNORE_NEW_LINES);
    if ($lines) {
        $errorLogLines = array_slice($lines, -50);
        $errorLogLines = array_reverse($errorLogLines);
    }
}

function h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>HE Admin Status</title>
    <meta http-equiv="refresh" content="30">
    <style>
        body { font-family: 'Courier New', monospace; background: #0b0f12; color: #c8f7c5; margin: 0; padding: 20px; }
        h1 { color: #7CFC00; border-bottom: 1px solid #2a3a2a; padding-bottom: 8px; }
        h2 { color: #7CFC00; margin-top: 32px; }
        .badge { padding: 2px 10px; border-radius: 4px; font-weight: bold; }
        .ok { background: #143d14; color: #7CFC00; }
        .error { background: #4d1414; color: #ff6b6b; }
        table { border-collapse: collapse; width: 100%; margin-top: 8px; }
        th, td { border: 1px solid #2a3a2a; padding: 6px 10px; text-align: left; font-size: 13px; }
        th { background: #10201a; }
        tr:nth-child(even) { background: #0e1512; }
        .counts { display: flex; gap: 16px; flex-wrap: wrap; }
        .count-box { background: #10201a; border: 1px solid #2a3a2a; padding: 10px 16px; border-radius: 6px; }
        .count-box .n { font-size: 22px; font-weight: bold; color: #7CFC00; }
        pre.log { background: #0e1512; border: 1px solid #2a3a2a; padding: 10px; max-height: 400px; overflow-y: auto; font-size: 12px; white-space: pre-wrap; word-break: break-all; }
        .muted { color: #6a8a6a; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Hacker Experience &mdash; Admin Status</h1>
    <p>
        Database:
        <span class="badge <?= $dbStatus === 'ok' ? 'ok' : 'error' ?>"><?= h($dbStatus) ?></span>
        <?php if ($dbError): ?><span class="muted"><?= h($dbError) ?></span><?php endif; ?>
    </p>
    <p class="muted">Auto-refreshes every 30s. Generated <?= h(date('Y-m-d H:i:s')) ?></p>

    <h2>Table Counts</h2>
    <div class="counts">
        <?php foreach ($tableCounts as $t => $c): ?>
            <div class="count-box">
                <div class="n"><?= h($c) ?></div>
                <div><?= h($t) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>Recent Registrations</h2>
    <table>
        <tr><th>User ID</th><th>Login</th><th>IP</th><th>Registered At</th></tr>
        <?php if (!$recentRegistrations): ?>
            <tr><td colspan="4" class="muted">No registrations yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($recentRegistrations as $r): ?>
            <tr>
                <td><?= h($r['userID']) ?></td>
                <td><?= h($r['login']) ?></td>
                <td><?= h($r['ip']) ?></td>
                <td><?= h($r['registrationDate']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Recent Logins (by last login time)</h2>
    <table>
        <tr><th>User ID</th><th>Login</th><th>Last Login</th></tr>
        <?php if (!$recentLogins): ?>
            <tr><td colspan="3" class="muted">No users yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($recentLogins as $r): ?>
            <tr>
                <td><?= h($r['id']) ?></td>
                <td><?= h($r['login']) ?></td>
                <td><?= h($r['lastLogin']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Recent PHP Error Log (last 50 lines, newest first)</h2>
    <?php if (!$errorLogLines): ?>
        <p class="muted">No errors logged, or log file not readable.</p>
    <?php else: ?>
        <pre class="log"><?php foreach ($errorLogLines as $line) { echo h($line) . "\n"; } ?></pre>
    <?php endif; ?>
</body>
</html>
