<?php
// ============================================================
//  api_keys.php  — API Key Manager  (v3)
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (!file_exists(__DIR__ . '/config.php')) {
    die('<h2 style="font-family:monospace;color:red">ERROR: config.php not found.</h2>');
}
require_once __DIR__ . '/config.php';
requireLogin();

$db   = getDB();
$msg  = '';
$mtype = 'success';
$new_key = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = (string)($_POST['act'] ?? '');

    if ($act === 'generate') {
        $name = sanitize((string)($_POST['key_name'] ?? ''));
        if (!$name) {
            $msg = 'Enter a name for this key.'; $mtype = 'error';
        } else {
            $new_key = 'ytc_' . bin2hex(random_bytes(16));
            $db->prepare('INSERT INTO api_keys (api_key, name) VALUES (:k, :n)')
               ->execute([':k' => $new_key, ':n' => $name]);
            $msg = 'Key generated for "' . htmlspecialchars($name) . '"';
        }
    }

    if ($act === 'delete') {
        $id = (int)($_POST['key_id'] ?? 0);
        if ($id) {
            $db->prepare('DELETE FROM api_keys WHERE id = :id')->execute([':id' => $id]);
            $msg = 'Key deleted.';
        }
    }
}

$keys   = $db->query('SELECT * FROM api_keys ORDER BY created_at DESC')->fetchAll();
$reqs   = (int)$db->query('SELECT COUNT(*) FROM requests')->fetchColumn();
$host   = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com');
$base   = rtrim(dirname($_SERVER['PHP_SELF'] ?? '/'), '/');
$api_url = $host . $base . '/api.php?action=create&api_key=YOUR_KEY';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>API Keys — Yobe Transport Service</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --green:#16a34a;--green-l:#dcfce7;--green-d:#15803d;
  --blue:#2563eb;--blue-l:#dbeafe;
  --red:#dc2626;--red-l:#fee2e2;
  --gray-50:#f9fafb;--gray-100:#f3f4f6;--gray-200:#e5e7eb;
  --gray-400:#9ca3af;--gray-500:#6b7280;--gray-600:#4b5563;
  --gray-700:#374151;--gray-900:#111827;--white:#ffffff;
  --shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 16px rgba(0,0,0,.08);--shadow-lg:0 10px 32px rgba(0,0,0,.1);
  --r:14px;--rs:8px;
}
body{font-family:'DM Sans',sans-serif;background:var(--gray-50);color:var(--gray-900);min-height:100vh}
header{background:var(--white);border-bottom:1px solid var(--gray-200);position:sticky;top:0;z-index:100;padding:0 24px}
.hi{max-width:960px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;height:64px;gap:10px}
.brand{display:flex;align-items:center;gap:9px;font-weight:700;font-size:16px;color:var(--gray-900);text-decoration:none}
.b-icon{width:32px;height:32px;border-radius:9px;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:15px}
.nav{display:flex;gap:5px}
.nav a{padding:7px 12px;border-radius:var(--rs);text-decoration:none;font-size:13px;font-weight:500;color:var(--gray-600);transition:all .15s}
.nav a:hover,.nav a.act{background:var(--green-l);color:var(--green-d)}
.btn-out{padding:7px 12px;border-radius:var(--rs);border:1.5px solid var(--gray-200);background:var(--white);font-family:inherit;font-size:13px;font-weight:500;cursor:pointer;color:var(--gray-600);text-decoration:none;transition:all .15s}
.btn-out:hover{border-color:var(--red);color:var(--red);background:#fff1f2}
main{max-width:960px;margin:0 auto;padding:28px 24px 64px}
.pt{font-size:21px;font-weight:700;margin-bottom:3px}
.ps{font-size:13px;color:var(--gray-500);margin-bottom:24px}
.mini-stats{display:flex;gap:12px;margin-bottom:22px;flex-wrap:wrap}
.ms{flex:1;min-width:110px;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--r);padding:13px 16px;box-shadow:var(--shadow-sm)}
.ms .l{font-size:11px;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:.05em}
.ms .v{font-size:26px;font-weight:700;margin-top:2px}
.ms.k .v{color:var(--green)}.ms.r .v{color:var(--blue)}
.api-status{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:99px;font-size:12px;font-weight:600;background:var(--green-l);color:var(--green-d);border:1px solid #bbf7d0}
.dot{width:7px;height:7px;border-radius:50%;background:var(--green);animation:pulse 2s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}
.alert{padding:11px 15px;border-radius:var(--rs);font-size:13px;font-weight:500;margin-bottom:18px;display:flex;align-items:center;gap:7px}
.alert.success{background:var(--green-l);color:var(--green-d);border:1px solid #bbf7d0}
.alert.error{background:var(--red-l);color:var(--red);border:1px solid #fca5a5}
.nk-banner{background:var(--gray-900);color:var(--white);border-radius:var(--r);padding:18px 22px;margin-bottom:20px}
.nk-banner h3{font-size:13px;font-weight:600;color:#86efac;margin-bottom:8px}
.nk-banner p{font-size:11px;color:#9ca3af;margin-bottom:11px}
.kcopy{display:flex;gap:7px;align-items:center}
.kval{flex:1;padding:9px 12px;background:#1f2937;border:1px solid #374151;border-radius:var(--rs);font-family:'DM Mono',monospace;font-size:12px;color:#34d399;word-break:break-all}
.btn-cp{padding:9px 14px;border-radius:var(--rs);background:var(--green);color:var(--white);border:none;font-family:inherit;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .15s}
.btn-cp:hover{background:var(--green-d)}
.section{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--r);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:20px}
.sh{padding:16px 22px;border-bottom:1px solid var(--gray-100)}
.sh h2{font-size:14px;font-weight:700}.sh p{font-size:12px;color:var(--gray-500);margin-top:2px}
.sb{padding:18px 22px}
.gen-row{display:flex;gap:9px}
.gen-row input{flex:1;padding:9px 12px;border:1.5px solid var(--gray-200);border-radius:var(--rs);font-family:inherit;font-size:13px;color:var(--gray-900);outline:none;transition:border-color .15s}
.gen-row input:focus{border-color:var(--green)}
.btn-gen{padding:9px 18px;border-radius:var(--rs);background:var(--green);color:var(--white);border:none;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .15s}
.btn-gen:hover{background:var(--green-d)}
.url-box{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:var(--rs);padding:13px 15px;margin-top:15px}
.url-box label{font-size:10px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px}
.ur{display:flex;gap:7px}
.uval{flex:1;padding:7px 11px;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--rs);font-family:'DM Mono',monospace;font-size:11px;color:var(--blue);word-break:break-all}
.btn-cu{padding:7px 12px;border-radius:var(--rs);background:var(--blue-l);color:var(--blue);border:1px solid #bfdbfe;font-family:inherit;font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .15s}
.btn-cu:hover{background:var(--blue);color:var(--white)}
table{width:100%;border-collapse:collapse}
th{text-align:left;padding:9px 15px;font-size:10px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;background:var(--gray-50);border-bottom:1px solid var(--gray-200)}
td{padding:12px 15px;border-bottom:1px solid var(--gray-100);font-size:13px;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--gray-50)}
.kname{font-weight:600}
.kmono{font-family:'DM Mono',monospace;font-size:11px;color:var(--gray-600)}
.kdate{font-size:11px;color:var(--gray-400)}
.btn-del{padding:5px 10px;border-radius:var(--rs);background:var(--red-l);color:var(--red);border:1px solid #fca5a5;font-family:inherit;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s}
.btn-del:hover{background:var(--red);color:var(--white)}
.empty-td{text-align:center;padding:40px;color:var(--gray-400);font-size:13px}
@media(max-width:600px){.gen-row{flex-direction:column}.kmono{display:none}}
</style>
</head>
<body>

<header>
  <div class="hi">
    <a href="index.php" class="brand"><div class="b-icon">🚗</div>YTC Admin</a>
    <div class="nav">
      <a href="index.php">Dashboard</a>
      <a href="api_keys.php" class="act">🔑 Keys</a>
    </div>
    <a href="logout.php" class="btn-out">Logout</a>
  </div>
</header>

<main>
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:5px">
    <h1 class="pt">🔑 API Key Manager</h1>
    <span class="api-status" id="api-ind"><span class="dot"></span> Checking…</span>
  </div>
  <p class="ps">Generate keys to allow your React app to send data securely.</p>

  <div class="mini-stats">
    <div class="ms k"><div class="l">Active Keys</div><div class="v"><?= count($keys) ?></div></div>
    <div class="ms r"><div class="l">Total Requests</div><div class="v"><?= $reqs ?></div></div>
  </div>

  <?php if ($msg): ?>
  <div class="alert <?= $mtype ?>"><?= $mtype==='success'?'✅':'⚠️' ?> <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <?php if ($new_key): ?>
  <div class="nk-banner">
    <h3>🎉 New Key Generated — Copy it now!</h3>
    <p>This will NOT be shown in full again. Save it somewhere safe.</p>
    <div class="kcopy">
      <div class="kval" id="nk"><?= htmlspecialchars($new_key) ?></div>
      <button class="btn-cp" onclick="cp('nk',this)">📋 Copy</button>
    </div>
  </div>
  <?php endif; ?>

  <!-- Generate -->
  <div class="section">
    <div class="sh"><h2>Generate New API Key</h2><p>Give it a descriptive name</p></div>
    <div class="sb">
      <form method="POST">
        <input type="hidden" name="act" value="generate" />
        <div class="gen-row">
          <input type="text" name="key_name" placeholder="e.g. React App, Mobile App, Test Key…" required />
          <button type="submit" class="btn-gen">⚡ Generate</button>
        </div>
      </form>
      <div class="url-box">
        <label>API Endpoint URL (replace YOUR_KEY)</label>
        <div class="ur">
          <div class="uval" id="api-url"><?= htmlspecialchars($api_url) ?></div>
          <button class="btn-cu" onclick="cp('api-url',this)">📋 Copy URL</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Keys List -->
  <div class="section">
    <div class="sh"><h2>Active API Keys</h2><p>Delete a key to instantly revoke it</p></div>
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Key (masked)</th><th>Created</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($keys)): ?>
          <tr><td class="empty-td" colspan="5">No keys yet — generate one above.</td></tr>
        <?php else: foreach ($keys as $k): ?>
          <tr>
            <td><?= (int)$k['id'] ?></td>
            <td class="kname"><?= htmlspecialchars($k['name']) ?></td>
            <td class="kmono"><?= htmlspecialchars(substr($k['api_key'],0,14)) ?>••••••••</td>
            <td class="kdate"><?= date('d M Y', strtotime($k['created_at'])) ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('Delete this key? Apps using it will lose access immediately.')">
                <input type="hidden" name="act"    value="delete" />
                <input type="hidden" name="key_id" value="<?= (int)$k['id'] ?>" />
                <button type="submit" class="btn-del">🗑 Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
function cp(id, btn) {
  var t = document.getElementById(id).textContent.trim();
  navigator.clipboard.writeText(t).then(function(){
    var o=btn.textContent; btn.textContent='✅ Copied!';
    setTimeout(function(){btn.textContent=o;},2000);
  });
}
async function checkApi() {
  var ind = document.getElementById('api-ind');
  try {
    var r = await fetch('api.php?action=stats&api_key=__dashboard__');
    var d = await r.json();
    if (d.status === 'success') {
      ind.innerHTML = '<span class="dot"></span> API Online';
    } else {
      ind.innerHTML = '🔴 ' + (d.message||'API Error');
      ind.style.cssText = 'background:var(--red-l);color:var(--red);border-color:#fca5a5';
    }
  } catch(e) {
    ind.innerHTML = '🔴 API Unreachable';
    ind.style.cssText = 'background:var(--red-l);color:var(--red);border-color:#fca5a5';
  }
}
checkApi();
</script>
</body>
</html>
