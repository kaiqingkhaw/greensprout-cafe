<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config.php';
require_method('POST');
rate_limit('signup', 20, 600);
$input = json_input();
foreach (['username' => 50, 'name' => 100, 'email' => 190, 'phone' => 30, 'password' => 72, 'confirm_password' => 72] as $field => $limit) {
    if (!isset($input[$field]) || !is_string($input[$field]) || mb_strlen($input[$field]) > $limit) {
        json_response(['error' => 'Please provide valid details within the field length limits.'], 422);
    }
}
$username = clean_text($input['username'] ?? '', 50);
$name = clean_text($input['name'] ?? '', 100);
$email = filter_var(clean_text($input['email'] ?? '', 190), FILTER_VALIDATE_EMAIL);
$phone = clean_text($input['phone'] ?? '', 30);
$rawPassword = (string) ($input['password'] ?? '');
if ($username === '' || $name === '' || !$email || $phone === '' || $rawPassword === '') json_response(['error' => 'Please provide valid details in every field.'], 422);
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) json_response(['error' => 'Username must be 3–50 characters using letters, numbers, dots, dashes, or underscores.'], 422);
if (strlen($rawPassword) < 8) json_response(['error' => 'Password must be at least 8 characters.'], 422);
if (strlen($rawPassword) > 72 || str_contains($rawPassword, "\0") || trim($rawPassword) === '') json_response(['error' => 'Password must be 8–72 bytes and cannot contain only spaces.'], 422);
if ($rawPassword !== $input['confirm_password']) json_response(['error' => 'Passwords do not match.'], 422);
$digits = preg_replace('/\D/', '', $phone);
if (!preg_match('/^\+?[0-9 ()-]+$/', $phone) || strlen($digits) < 8 || strlen($digits) > 15) json_response(['error' => 'Enter a phone number with 8–15 digits, optionally including +, spaces, brackets or dashes.'], 422);

try {
    $hash = password_hash($rawPassword, PASSWORD_DEFAULT);
    $stmt = db()->prepare("INSERT INTO users (username, name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, 'user')");
    $stmt->bind_param('sssss', $username, $name, $email, $phone, $hash); $stmt->execute();
    json_response(['success' => true, 'redirect' => 'login.html?signup=success'], 201);
} catch (mysqli_sql_exception $exception) {
    if ((int) $exception->getCode() === 1062) json_response(['error' => 'That username or email is already registered.'], 409);
    error_log($exception->getMessage()); json_response(['error' => 'Registration failed. Please try again.'], 500);
}
