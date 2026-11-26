<?php
/**
 * AURU — Secure Academic & Identity Document Vault
 * File: config.php
 * Role: Bootstrap, database connection, session security, access control helpers
 * Security: PDO prepared statements, native_password MySQL, hardened session cookie flags,
 *           CSRF token generation, secure headers, error suppression.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// 1. Error handling — never leak stack traces or DB credentials to the browser
// ---------------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', '/var/log/auru/errors.log');

// ---------------------------------------------------------------------------
// 2. Hardened session configuration
// ---------------------------------------------------------------------------
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '0');          // Set to '1' once HTTPS is live
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_only_cookies', '1');
ini_set('session.name', 'AURU_SESS');
ini_set('session.gc_maxlifetime', '1800');      // 30 minutes

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID on privilege changes / login to prevent session fixation
function auru_regenerate_session(): void {
    session_regenerate_id(true);
}

// ---------------------------------------------------------------------------
// 3. Database connection (PDO, prepared statements only)
// ---------------------------------------------------------------------------
$host     = 'localhost';
$db       = 'auru_db';
$user     = 'root';
$pass     = 'SecurePassword123!';               // Cloud member sets this via config
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,      // Real server-side prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Log internally, show generic message to user
    error_log("AURU DB connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Service temporarily unavailable. Please try again later.");
}

// ---------------------------------------------------------------------------
// 4. CSRF protection
// ---------------------------------------------------------------------------
function auru_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function auru_csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(auru_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function auru_verify_csrf(string $token): bool {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------------
// 5. Access control helpers
// ---------------------------------------------------------------------------
function auru_require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function auru_require_role(string $role): void {
    auru_require_login();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        die("Access denied.");
    }
}

function auru_is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function auru_escape(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---------------------------------------------------------------------------
// 6. Security headers (defense in depth)
// ---------------------------------------------------------------------------
function auru_send_security_headers(): void {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; style-src \'self\' \'unsafe-inline\'; script-src \'self\'; img-src \'self\' data:; form-action \'self\'; base-uri \'self\';');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=0'); // Set to max-age=31536000; includeSubDomains once HTTPS is live
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}
auru_send_security_headers();
