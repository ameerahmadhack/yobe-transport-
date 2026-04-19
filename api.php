<?php
// ============================================================
//  api.php  — YTC Transport API  (v3 — hardened)
// ============================================================

// ── Error handling: catch PHP errors as JSON, not HTML ───────
error_reporting(E_ALL);
ini_set('display_errors', '0');

set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline): bool {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => "PHP Error [$errno]: $errstr in $errfile:$errline",
    ]);
    exit();
});

set_exception_handler(function(Throwable $e): void {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => get_class($e) . ': ' . $e->getMessage(),
    ]);
    exit();
});

// ── Output buffer — prevents stray whitespace breaking JSON ──
ob_start();

// ── CORS ─────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

// ── Load config ───────────────────────────────────────────────
$config_path = __DIR__ . '/config.php';
if (!file_exists($config_path)) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'config.php not found. Upload all files.']);
    exit();
}
require_once $config_path;

// ── Helpers ───────────────────────────────────────────────────
function getBody(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') return $_POST ?: [];
    $json = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) return $_POST ?: [];
    return is_array($json) ? $json : [];
}

function rateLimit(): void {
    $ip   = preg_replace('/[^a-fA-F0-9\.\:\-]/', '', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $file = sys_get_temp_dir() . '/ytc_rl_' . md5($ip) . '.json';

    $data = ['c' => 0, 'w' => time()];
    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        if ($raw) {
            $d = json_decode($raw, true);
            if (is_array($d)) $data = $d;
        }
    }

    if ((time() - (int)($data['w'] ?? 0)) >= 60) {
        $data = ['c' => 1, 'w' => time()];
    } else {
        $data['c'] = (int)($data['c'] ?? 0) + 1;
    }

    @file_put_contents($file, json_encode($data), LOCK_EX);

    if ((int)$data['c'] > 60) {
        respond(['status' => 'error', 'message' => 'Rate limit: 60 req/min exceeded'], 429);
    }
}

function validateKey(PDO $db): void {
    // Check query string, then X-API-Key header
    $key = trim($_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '');

    if ($key === '') {
        respond(['status' => 'error', 'message' => 'Missing api_key. Usage: api.php?action=create&api_key=YOUR_KEY'], 401);
    }

    // Internal dashboard calls — verify session
    if ($key === '__dashboard__') {
        if (empty($_SESSION['ytc_logged_in'])) {
            respond(['status' => 'error', 'message' => 'Dashboard session expired. Please log in again.'], 401);
        }
        return;
    }

    $stmt = $db->prepare('SELECT id FROM api_keys WHERE api_key = :k LIMIT 1');
    $stmt->execute([':k' => $key]);
    if (!$stmt->fetch()) {
        respond(['status' => 'error', 'message' => 'Invalid or revoked API key'], 401);
    }
}

// ── Main ──────────────────────────────────────────────────────
try {
    $db = getDB();
    rateLimit();
    validateKey($db);

    $action = trim($_GET['action'] ?? '');

    if ($action === '') {
        respond([
            'status'  => 'ok',
            'message' => 'YTC API is running ✅',
            'actions' => ['create', 'get_all', 'update_status', 'assign_driver', 'delete', 'stats'],
        ]);
    }

    switch ($action) {

        // ─────────────────────────────────────────────────────
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respond(['status' => 'error', 'message' => 'POST method required'], 405);
            }
            $b = getBody();

            $name        = sanitize((string)($b['name']        ?? ''));
            $phone       = sanitize((string)($b['phone']       ?? ''));
            $pickup      = sanitize((string)($b['pickup']      ?? ''));
            $destination = sanitize((string)($b['destination'] ?? ''));
            $type        = sanitize((string)($b['type']        ?? 'normal'));
            $note        = sanitize((string)($b['note']        ?? ''));

            $errors = [];
            if ($name === '')        $errors[] = 'name';
            if ($phone === '')       $errors[] = 'phone';
            if ($pickup === '')      $errors[] = 'pickup';
            if ($destination === '') $errors[] = 'destination';

            if ($errors) {
                respond(['status' => 'error', 'message' => 'Required fields missing: ' . implode(', ', $errors)], 400);
            }

            if (!in_array($type, ['normal', 'emergency', 'vip'], true)) $type = 'normal';

            $stmt = $db->prepare(
                'INSERT INTO requests (name, phone, pickup, destination, type, note)
                 VALUES (:name, :phone, :pickup, :destination, :type, :note)'
            );
            $stmt->execute([
                ':name'        => $name,
                ':phone'       => $phone,
                ':pickup'      => $pickup,
                ':destination' => $destination,
                ':type'        => $type,
                ':note'        => $note,
            ]);

            respond(['status' => 'success', 'message' => 'Request submitted', 'id' => (int)$db->lastInsertId()]);

        // ─────────────────────────────────────────────────────
        case 'get_all':
            $rows = $db->query('SELECT * FROM requests ORDER BY created_at DESC')->fetchAll();
            respond(['status' => 'success', 'count' => count($rows), 'data' => $rows]);

        // ─────────────────────────────────────────────────────
        case 'update_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respond(['status' => 'error', 'message' => 'POST required'], 405);
            }
            $b      = getBody();
            $id     = (int)($b['id'] ?? 0);
            $status = sanitize((string)($b['status'] ?? ''));

            if (!$id)     respond(['status' => 'error', 'message' => 'id required'], 400);
            if (!$status) respond(['status' => 'error', 'message' => 'status required'], 400);

            if (!in_array($status, ['pending', 'approved', 'rejected', 'completed'], true)) {
                respond(['status' => 'error', 'message' => 'status must be: pending, approved, rejected, completed'], 400);
            }

            $stmt = $db->prepare('UPDATE requests SET status = :s WHERE id = :id');
            $stmt->execute([':s' => $status, ':id' => $id]);

            if ($stmt->rowCount() === 0) respond(['status' => 'error', 'message' => 'Request #'.$id.' not found'], 404);
            respond(['status' => 'success', 'message' => 'Status updated to: ' . $status]);

        // ─────────────────────────────────────────────────────
        case 'assign_driver':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respond(['status' => 'error', 'message' => 'POST required'], 405);
            }
            $b    = getBody();
            $id   = (int)($b['id'] ?? 0);
            $dn   = sanitize((string)($b['driver_name']  ?? ''));
            $dp   = sanitize((string)($b['driver_phone'] ?? ''));
            $veh  = sanitize((string)($b['vehicle']      ?? ''));

            if (!$id || !$dn || !$dp || !$veh) {
                respond(['status' => 'error', 'message' => 'Required: id, driver_name, driver_phone, vehicle'], 400);
            }

            $stmt = $db->prepare(
                'UPDATE requests SET driver_name=:dn, driver_phone=:dp, vehicle=:v, status="approved" WHERE id=:id'
            );
            $stmt->execute([':dn' => $dn, ':dp' => $dp, ':v' => $veh, ':id' => $id]);

            if ($stmt->rowCount() === 0) respond(['status' => 'error', 'message' => 'Request #'.$id.' not found'], 404);
            respond(['status' => 'success', 'message' => 'Driver assigned to request #' . $id]);

        // ─────────────────────────────────────────────────────
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respond(['status' => 'error', 'message' => 'POST required'], 405);
            }
            $b  = getBody();
            $id = (int)($b['id'] ?? 0);
            if (!$id) respond(['status' => 'error', 'message' => 'id required'], 400);

            $db->prepare('DELETE FROM requests WHERE id = :id')->execute([':id' => $id]);
            respond(['status' => 'success', 'message' => 'Request #'.$id.' deleted']);

        // ─────────────────────────────────────────────────────
        case 'stats':
            respond([
                'status' => 'success',
                'data'   => [
                    'total'     => (int)$db->query("SELECT COUNT(*) FROM requests")->fetchColumn(),
                    'pending'   => (int)$db->query("SELECT COUNT(*) FROM requests WHERE status='pending'")->fetchColumn(),
                    'approved'  => (int)$db->query("SELECT COUNT(*) FROM requests WHERE status='approved'")->fetchColumn(),
                    'completed' => (int)$db->query("SELECT COUNT(*) FROM requests WHERE status='completed'")->fetchColumn(),
                    'rejected'  => (int)$db->query("SELECT COUNT(*) FROM requests WHERE status='rejected'")->fetchColumn(),
                    'emergency' => (int)$db->query("SELECT COUNT(*) FROM requests WHERE type='emergency'")->fetchColumn(),
                    'api_keys'  => (int)$db->query("SELECT COUNT(*) FROM api_keys")->fetchColumn(),
                ],
            ]);

        // ─────────────────────────────────────────────────────
        default:
            respond(['status' => 'error', 'message' => "Unknown action: '$action'. Valid: create, get_all, update_status, assign_driver, delete, stats"], 400);
    }

} catch (Throwable $e) {
    if (ob_get_level()) ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
        'hint'    => 'If this mentions SQLite, your host may not support it. Run check.php to diagnose.',
    ]);
    exit();
}
