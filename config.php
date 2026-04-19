<?php
// ============================================================
//  config.php  — YTC Shared Core  (v3 — hardened)
// ============================================================

// ── Show errors in config itself during debug ────────────────
// Comment these two lines out after confirming system works:
error_reporting(E_ALL);
ini_set('display_errors', '0'); // keep OFF so errors go to JSON, not HTML

// ── Guard: PDO + SQLite must exist ───────────────────────────
if (!class_exists('PDO') || !in_array('sqlite', PDO::getAvailableDrivers())) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'SQLite is not supported on this server. Please run check.php to diagnose.',
    ]);
    exit();
}

// ── Admin Credentials ─────────────────────────────────────────
// To change password:  update ADMIN_PLAIN_PW, delete password.hash, reload once.
define('ADMIN_USERNAME', 'yobe transport service');
define('ADMIN_PLAIN_PW', 'YTC@Admin2025!');

function getPasswordHash(): string {
    // Try to persist hash in a file (faster + more secure than re-hashing)
    $hashFile = __DIR__ . '/password.hash';

    if (file_exists($hashFile) && filesize($hashFile) > 10) {
        return trim(file_get_contents($hashFile));
    }

    if (!function_exists('password_hash')) {
        // Very old PHP fallback (shouldn't happen on PHP 7.4+)
        return md5(ADMIN_PLAIN_PW . 'ytc_salt_2025');
    }

    $hash = password_hash(ADMIN_PLAIN_PW, PASSWORD_BCRYPT, ['cost' => 10]);

    // Try to save — but don't crash if directory isn't writable
    if (is_writable(__DIR__)) {
        file_put_contents($hashFile, $hash, LOCK_EX);
        @chmod($hashFile, 0600);
    }

    return $hash;
}

function verifyPassword(string $plain, string $hash): bool {
    if (function_exists('password_verify')) {
        return password_verify($plain, $hash);
    }
    // Fallback for ancient PHP
    return md5($plain . 'ytc_salt_2025') === $hash;
}

// ── Session ───────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    @session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    @session_start();
}

// ── Database ──────────────────────────────────────────────────
function getDB(): PDO {
    // Try primary path first, then fallback to /tmp
    $paths = [
        __DIR__ . '/database.db',
        sys_get_temp_dir() . '/ytc_database.db',
    ];

    $db = null;
    $lastError = '';

    foreach ($paths as $path) {
        try {
            $db = new PDO('sqlite:' . $path);
            $db->setAttribute(PDO::ATTR_ERRMODE,           PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec('PRAGMA journal_mode=WAL');
            $db->exec('PRAGMA busy_timeout=5000');
            break; // success
        } catch (Exception $e) {
            $lastError = $e->getMessage();
            $db = null;
        }
    }

    if (!$db) {
        throw new RuntimeException('Cannot open database. Error: ' . $lastError);
    }

    // Create tables
    $db->exec("CREATE TABLE IF NOT EXISTS requests (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        name         TEXT    NOT NULL,
        phone        TEXT    NOT NULL,
        pickup       TEXT    NOT NULL,
        destination  TEXT    NOT NULL,
        type         TEXT    NOT NULL DEFAULT 'normal',
        note         TEXT    DEFAULT '',
        status       TEXT    NOT NULL DEFAULT 'pending',
        driver_name  TEXT    DEFAULT '',
        driver_phone TEXT    DEFAULT '',
        vehicle      TEXT    DEFAULT '',
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS api_keys (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        api_key    TEXT    NOT NULL UNIQUE,
        name       TEXT    NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    return $db;
}

// ── Auth Guard ────────────────────────────────────────────────
function requireLogin(): void {
    if (empty($_SESSION['ytc_logged_in'])) {
        header('Location: login.php');
        exit();
    }
}

// ── Helpers ───────────────────────────────────────────────────
function sanitize(string $v): string {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

function respond(array $data, int $code = 200): void {
    // Clear any accidental output buffer
    if (ob_get_level()) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}
