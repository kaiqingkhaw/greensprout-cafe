<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config.php';
start_app_session();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="icon" href="assets/favicon.svg?v=2" type="image/svg+xml"><title>Sign out | GreenSprout Café</title></head><body><form method="post"><input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '"><button>Confirm sign out</button></form></body></html>';
    exit;
}
require_method('POST');
$_SESSION = [];
setcookie(session_name(), '', ['expires' => time()-3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) json_response(['success' => true]);
header('Location: login.html', true, 303);
