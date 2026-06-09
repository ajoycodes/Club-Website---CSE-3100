<?php
/**
 * auth/init.php
 * Session bootstrap, CSRF helpers, and auth guards.
 * Include this at the top of every PHP page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set true when on HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

// ── Remember-Me auto-login ────────────────────────────────────────────────────
// If no active session but a remember_me cookie exists, try to restore the login.
if (empty($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $parts = explode('|', $_COOKIE['remember_me'], 2);
    if (count($parts) === 2 && ctype_digit($parts[0])) {
        $cookieUserId    = (int) $parts[0];
        $hashedToken     = hash('sha256', $parts[1]);
        $stmt = getDB()->prepare(
            'SELECT id, role FROM users
             WHERE id = ? AND remember_token = ? AND remember_expires > NOW()
               AND status = "approved"'
        );
        $stmt->execute([$cookieUserId, $hashedToken]);
        $remembered = $stmt->fetch();
        if ($remembered) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $remembered['id'];
            $_SESSION['role']    = $remembered['role'];
        } else {
            // Stale or invalid — delete the cookie
            setcookie('remember_me', '', time() - 3600, '/', '', false, true);
        }
    }
}

// ── CSRF ─────────────────────────────────────────────────────────────────────

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ── AUTH GUARDS ───────────────────────────────────────────────────────────────

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    $user = current_user();
    if (!$user || $user['role'] !== 'admin') {
        header('Location: /dashboard.php');
        exit;
    }
}

// ── FLASH MESSAGES ────────────────────────────────────────────────────────────

function flash(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ── HELPERS ───────────────────────────────────────────────────────────────────

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}
