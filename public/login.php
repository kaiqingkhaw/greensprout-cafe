<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config.php';
require_method('POST');
rate_limit('login', 30, 300);
start_app_session();
if (!is_string($_POST['username'] ?? null) || !is_string($_POST['password'] ?? null) || mb_strlen($_POST['username']) > 50 || strlen($_POST['password']) > 72 || str_contains($_POST['password'], "\0")) {
    json_response(['error' => 'Please enter a valid username and password.'], 422);
}

$username = clean_text($_POST['username'] ?? '', 50);
$password = (string) ($_POST['password'] ?? '');
if ($username === '' || $password === '') json_response(['error' => 'Username and password are required.'], 422);

try {
    $stmt = db()->prepare('SELECT id, username, name, email, phone, password, role FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username); $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user || !password_verify($password, $user['password'])) {
        usleep(250000); json_response(['error' => 'Invalid username or password.'], 401);
    }
    session_regenerate_id(true);
    $_SESSION = [];
    foreach (['username', 'name', 'email', 'phone', 'role'] as $field) $_SESSION[$field] = $user[$field];
    $_SESSION['user_id'] = (int) $user['id'];
    csrf_token();
    json_response(['success' => true, 'redirect' => $user['role'] === 'admin' ? 'admin_home.php' : 'MainMenu.html']);
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage()); json_response(['error' => 'Unable to log in right now.'], 500);
}
