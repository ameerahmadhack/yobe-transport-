<?php
// ============================================================
//  check.php — Server Diagnostics for YTC System
//  Upload this first, open in browser, then delete it.
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', '1');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>YTC — Server Check</title>
<style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:32px;font-size:14px;line-height:1.8}
h1{color:#34d399;font-size:20px;margin-bottom:24px}
.ok{color:#34d399}.fail{color:#f87171}.warn{color:#fbbf24}
.section{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:20px;margin-bottom:16px}
.section h2{color:#94a3b8;font-size:13px;text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;border-bottom:1px solid #334155;padding-bottom:8px}
.row{display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #1e293b}
.label{color:#94a3b8}.val{font-weight:bold}
.big{font-size:18px;padding:16px;border-radius:8px;margin-bottom:12px;text-align:center}
.green{background:#052e16;border:1px solid #166534;color:#34d399}
.red{background:#450a0a;border:1px solid #991b1b;color:#f87171}
.yellow{background:#431407;border:1px solid #92400e;color:#fbbf24}
</style>
</head>
<body>
<h1>🔍 YTC Server Diagnostic Report</h1>
<?php
$issues = [];
// 1. PHP Version
echo '<div class="section"><h2>PHP Environment</h2>';
$phpv = PHP_VERSION;
$ok = version_compare($phpv, '7.4', '>=');
echo '<div class="row"><span class="label">PHP Version</span><span class="val '.($ok?'ok':'fail').'">'.$phpv.($ok?' ✅':' ❌ need 7.4+').'</span></div>';
if (!$ok) $issues[] = 'PHP version too old: '.$phpv;

// 2. SQLite / PDO
$pdo_ok    = class_exists('PDO');
$sqlite_ok = $pdo_ok && in_array('sqlite', PDO::getAvailableDrivers());
echo '<div class="row"><span class="label">PDO Extension</span><span class="val '.($pdo_ok?'ok':'fail').'">'.($pdo_ok?'Loaded ✅':'NOT loaded ❌').'</span></div>';
echo '<div class="row"><span class="label">PDO SQLite Driver</span><span class="val '.($sqlite_ok?'ok':'fail').'">'.($sqlite_ok?'Available ✅':'NOT available ❌').'</span></div>';
if (!$pdo_ok)    $issues[] = 'PDO extension not loaded';
if (!$sqlite_ok) $issues[] = 'PDO SQLite driver missing — host does not support SQLite';

if ($sqlite_ok) {
    try {
        $t = new PDO('sqlite::memory:');
        $t->exec('CREATE TABLE t (id INTEGER PRIMARY KEY)');
        $t->exec('INSERT INTO t VALUES (1)');
        $r = $t->query('SELECT id FROM t')->fetchColumn();
        echo '<div class="row"><span class="label">SQLite In-Memory Test</span><span class="val ok">Passed ✅ (got: '.$r.')</span></div>';
    } catch (Exception $e) {
        echo '<div class="row"><span class="label">SQLite In-Memory Test</span><span class="val fail">FAILED ❌ '.htmlspecialchars($e->getMessage()).'</span></div>';
        $issues[] = 'SQLite test: '.$e->getMessage();
    }
}
echo '</div>';

// 3. File System
echo '<div class="section"><h2>File System & Permissions</h2>';
$dir = __DIR__;
$dir_w = is_writable($dir);
$db_path = $dir.'/database.db';
echo '<div class="row"><span class="label">Document Root</span><span class="val">'.htmlspecialchars($dir).'</span></div>';
echo '<div class="row"><span class="label">Directory Writable</span><span class="val '.($dir_w?'ok':'fail').'">'.($dir_w?'Yes ✅':'No ❌').'</span></div>';
if (!$dir_w) $issues[] = 'Directory not writable — cannot create database.db';

if ($sqlite_ok) {
    try {
        $db = new PDO('sqlite:'.$db_path);
        $db->exec('CREATE TABLE IF NOT EXISTS _test (id INTEGER PRIMARY KEY)');
        $db->exec('DROP TABLE IF EXISTS _test');
        echo '<div class="row"><span class="label">database.db Create/Write Test</span><span class="val ok">Success ✅</span></div>';
    } catch (Exception $e) {
        echo '<div class="row"><span class="label">database.db Create/Write Test</span><span class="val fail">FAILED ❌ '.htmlspecialchars($e->getMessage()).'</span></div>';
        $issues[] = 'Cannot create database.db: '.$e->getMessage();
    }
}
echo '</div>';

// 4. Sessions
echo '<div class="section"><h2>Sessions & Security Functions</h2>';
if (session_status() === PHP_SESSION_NONE) @session_start();
$sess_ok = (session_status() === PHP_SESSION_ACTIVE);
echo '<div class="row"><span class="label">Sessions Working</span><span class="val '.($sess_ok?'ok':'fail').'">'.($sess_ok?'Yes ✅':'FAILED ❌').'</span></div>';
$rb_ok = function_exists('random_bytes');
$pw_ok = function_exists('password_hash');
echo '<div class="row"><span class="label">random_bytes()</span><span class="val '.($rb_ok?'ok':'fail').'">'.($rb_ok?'Available ✅':'MISSING ❌').'</span></div>';
echo '<div class="row"><span class="label">password_hash()</span><span class="val '.($pw_ok?'ok':'fail').'">'.($pw_ok?'Available ✅':'MISSING ❌').'</span></div>';
if (!$rb_ok) $issues[] = 'random_bytes() missing';
if (!$pw_ok) $issues[] = 'password_hash() missing';
echo '</div>';

// Summary
echo '<div class="section"><h2>Summary</h2>';
if (empty($issues)) {
    echo '<div class="big green">✅ ALL CHECKS PASSED — Server is ready for YTC!</div>';
} else {
    foreach ($issues as $i) echo '<div class="big red">❌ '.htmlspecialchars($i).'</div>';
}
if (!$sqlite_ok) {
    echo '<div class="big yellow">⚠️ SQLite not available. Options: (1) re-upload with db path fix OR (2) ask for MySQL version of the system</div>';
}
echo '</div>';
?>
<div style="background:#450a0a;border:1px solid #991b1b;border-radius:10px;padding:14px;color:#f87171;text-align:center">
  ⚠️ <strong>DELETE check.php after use</strong> — it exposes server information
</div>
</body>
</html>
