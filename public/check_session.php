<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config.php';
start_app_session();
if (empty($_SESSION['user_id'])) json_response(['loggedIn' => false]);
try {
    $stmt = db()->prepare('SELECT name, email, phone, address, role FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']); $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) { $_SESSION = []; json_response(['loggedIn' => false]); }
    json_response(['loggedIn' => true, 'user' => $user, 'csrfToken' => csrf_token()]);
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage()); json_response(['loggedIn' => false, 'error' => 'Session check unavailable.'], 500);
}

