<?php
// ============================================================
//  login.php  — YTC Admin Login  (v3)
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Safe require
if (!file_exists(__DIR__ . '/config.php')) {
    die('<h2 style="font-family:monospace;color:red">ERROR: config.php not found.<br>Make sure all files are uploaded to the same folder.</h2>');
}
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['ytc_logged_in'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $ip_key   = 'ytc_fail_' . md5($_SERVER['REMOTE_ADDR'] ?? '');

    $fails    = (int)($_SESSION[$ip_key] ?? 0);
    $lock_at  = (int)($_SESSION[$ip_key . '_t'] ?? 0);

    if ($fails >= 5 && (time() - $lock_at) < 300) {
        $wait = 300 - (time() - $lock_at);
        $error = "Too many failed attempts. Wait {$wait}s.";
    } elseif ($username === ADMIN_USERNAME && verifyPassword($password, getPasswordHash())) {
        session_regenerate_id(true);
        $_SESSION['ytc_logged_in'] = true;
        $_SESSION['ytc_user']      = $username;
        unset($_SESSION[$ip_key], $_SESSION[$ip_key . '_t']);
        header('Location: index.php');
        exit();
    } else {
        if ($fails >= 5 && (time() - $lock_at) >= 300) {
            $_SESSION[$ip_key] = 1;
        } else {
            $_SESSION[$ip_key] = $fails + 1;
        }
        $_SESSION[$ip_key . '_t'] = time();
        $error = 'Incorrect username or password.';
        // Small delay to slow brute force
        usleep(500000);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Login — Yobe Transport Service</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --green:#16a34a;--green-l:#dcfce7;--green-d:#15803d;
  --red:#dc2626;--red-l:#fee2e2;
  --gray-50:#f9fafb;--gray-100:#f3f4f6;--gray-200:#e5e7eb;
  --gray-400:#9ca3af;--gray-500:#6b7280;--gray-700:#374151;--gray-900:#111827;
  --white:#ffffff;--shadow:0 4px 24px rgba(0,0,0,.10);--r:14px;--rs:8px;
}
body{font-family:'DM Sans',sans-serif;background:var(--gray-50);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
body::before{content:'';position:fixed;inset:0;z-index:0;background:radial-gradient(ellipse 60% 40% at 20% 20%,rgba(22,163,74,.09) 0%,transparent 70%),radial-gradient(ellipse 50% 50% at 80% 80%,rgba(37,99,235,.06) 0%,transparent 70%);pointer-events:none}
.wrap{position:relative;z-index:1;width:100%;max-width:400px}
.logo{text-align:center;margin-bottom:28px}
.logo-icon{display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:16px;background:var(--green);font-size:28px;box-shadow:0 8px 20px rgba(22,163,74,.3);margin-bottom:12px}
.logo h1{font-size:19px;font-weight:700;color:var(--gray-900)}
.logo p{font-size:13px;color:var(--gray-500);margin-top:3px}
.card{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--r);box-shadow:var(--shadow);padding:30px 28px}
.card h2{font-size:16px;font-weight:700;color:var(--gray-900);margin-bottom:20px}
.err{display:flex;align-items:center;gap:8px;background:var(--red-l);border:1px solid #fca5a5;border-radius:var(--rs);padding:10px 14px;margin-bottom:16px;font-size:13px;color:var(--red);font-weight:500}
.field{margin-bottom:15px}
.field label{display:block;font-size:12px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.fw{position:relative}
.fw .ico{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);pointer-events:none;font-size:15px}
.field input{width:100%;padding:11px 38px;border:1.5px solid var(--gray-200);border-radius:var(--rs);font-family:inherit;font-size:14px;color:var(--gray-900);outline:none;transition:border-color .15s}
.field input:focus{border-color:var(--green)}
.eye{position:absolute;right:11px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray-400);font-size:16px;padding:4px;border-radius:4px;line-height:1}
.eye:hover{color:var(--gray-700)}
.btn{width:100%;padding:12px;background:var(--green);color:var(--white);border:none;border-radius:var(--rs);font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;transition:background .15s;margin-top:6px}
.btn:hover{background:var(--green-d)}
.btn:active{transform:scale(.98)}
.foot{text-align:center;margin-top:20px;font-size:12px;color:var(--gray-400)}
.foot strong{color:var(--green)}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <div class="logo-icon">🚗</div>
    <h1>Yobe Transport Service</h1>
    <p>Powered by Yobe Tech Connect</p>
  </div>
  <div class="card">
    <h2>Admin Login</h2>
    <?php if ($error): ?>
    <div class="err">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="field">
        <label>Username</label>
        <div class="fw">
          <span class="ico">👤</span>
          <input type="text" name="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 placeholder="Enter username" required autocomplete="username" />
        </div>
      </div>
      <div class="field">
        <label>Password</label>
        <div class="fw">
          <span class="ico">🔒</span>
          <input type="password" id="pw" name="password"
                 placeholder="Enter password" required autocomplete="current-password" />
          <button type="button" class="eye" onclick="togglePw()">👁</button>
        </div>
      </div>
      <button type="submit" class="btn">Sign In →</button>
    </form>
  </div>
  <div class="foot">Secure admin · <strong>YTC</strong> &copy; <?= date('Y') ?></div>
</div>
<script>
function togglePw(){var f=document.getElementById('pw');f.type=f.type==='password'?'text':'password'}
</script>
</body>
</html>
