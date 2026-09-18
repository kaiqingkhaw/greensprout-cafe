<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/config.php';
$username = $argv[1] ?? ''; $email = $argv[2] ?? ''; $password = $argv[3] ?? '';
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username) || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || strlen($password) > 72 || trim($password) === '' || str_contains($password, "\0")) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php <username> <email> <password-12+-chars>\n"); exit(1);
}
$name = 'Administrator'; $phone = ''; $role = 'admin'; $hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = db()->prepare('INSERT INTO users (username, name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->bind_param('ssssss', $username, $name, $email, $phone, $hash, $role); $stmt->execute();
fwrite(STDOUT, "Admin account created.\n");
