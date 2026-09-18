<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Kuala_Lumpur');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(static function (Throwable $exception): void {
    error_log((string) $exception);
    if (PHP_SAPI === 'cli') { fwrite(STDERR, "Operation failed; see the PHP error log.\n"); exit(1); }
    if ($exception instanceof mysqli_sql_exception && $exception->getCode() === 1062) json_response(['error' => 'That record already exists. Please check your details.'], 409);
    json_response(['error' => 'The request could not be completed. Please try again.'], 500);
});

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): mysqli
{
    static $connection = null;
    if ($connection instanceof mysqli) return $connection;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $connection = new mysqli(
        getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_USER') ?: 'root',
        getenv('DB_PASSWORD') ?: '', getenv('DB_NAME') ?: 'greensprout_cafe',
        (int) (getenv('DB_PORT') ?: 3306)
    );
    $connection->set_charset('utf8mb4');
    return $connection;
}

function json_response(array $payload, int $status = 200): never
{
    if ($status >= 400 && !array_key_exists('success', $payload)) $payload['success'] = false;
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($input)) json_response(['error' => 'Invalid JSON request body.'], 400);
    return $input;
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
        header('Allow: ' . strtoupper($method));
        json_response(['error' => 'Method not allowed.'], 405);
    }
    if (strtoupper($method) === 'POST') verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null);
}

function require_login(): int
{
    start_app_session();
    if (empty($_SESSION['user_id'])) json_response(['error' => 'Please log in to continue.'], 401);
    $stmt = db()->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']); $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    if (!$record) { $_SESSION = []; json_response(['error' => 'Please log in again.'], 401); }
    $_SESSION['role'] = $record['role'];
    return (int) $_SESSION['user_id'];
}

function require_admin_page(): void
{
    start_app_session();
    if (!empty($_SESSION['user_id'])) {
        $check = db()->prepare('SELECT role FROM users WHERE id = ?');
        $check->bind_param('i', $_SESSION['user_id']); $check->execute();
        $_SESSION['role'] = $check->get_result()->fetch_assoc()['role'] ?? '';
    }
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: login.html'); exit;
    }
}

function csrf_token(): string
{
    start_app_session();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf(mixed $token): void
{
    start_app_session();
    if (!is_string($token) || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(['error' => 'Your session expired. Refresh and try again.'], 403);
    }
}

function clean_text(mixed $value, int $maxLength = 255): string
{
    if (!is_string($value) || mb_strlen($value) > $maxLength) json_response(['error' => 'A text field is invalid or too long.'], 422);
    return trim($value);
}

function valid_phone(string $phone): bool
{
    $digits = preg_replace('/\D/', '', $phone);
    return preg_match('/^\+?[0-9 ()-]+$/', $phone) === 1 && strlen($digits) >= 8 && strlen($digits) <= 15;
}

function rate_limit(string $action, int $limit, int $seconds): void
{
    // Fixed-window local abuse protection. Production deployments also need edge limits.
    $window = intdiv(time(), $seconds);
    $key = hash('sha256', $action . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local') . '|' . $window);
    $expires = time() + $seconds * 2;
    $stmt = db()->prepare('INSERT INTO request_limits (bucket, hits, expires_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE hits = hits + 1');
    $stmt->bind_param('si', $key, $expires); $stmt->execute();
    $read = db()->prepare('SELECT hits FROM request_limits WHERE bucket = ?');
    $read->bind_param('s', $key); $read->execute();
    if ((int) $read->get_result()->fetch_assoc()['hits'] > $limit) {
        header('Retry-After: ' . $seconds);
        json_response(['error' => 'Too many attempts. Please wait a few minutes before trying again.'], 429);
    }
    db()->query('DELETE FROM request_limits WHERE expires_at < UNIX_TIMESTAMP()');
}
