<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_method('POST');
rate_limit('contact', 10, 600);
$name = clean_text($_POST['name'] ?? '', 100);
$email = filter_var(clean_text($_POST['email'] ?? '', 190), FILTER_VALIDATE_EMAIL);
$phone = clean_text($_POST['phone'] ?? '', 30);
$subject = clean_text($_POST['subject'] ?? '', 150);
$message = clean_text($_POST['message'] ?? '', 2000);
$errors = [];
if ($name === '') $errors['name'] = 'Enter your full name.';
if (!$email) $errors['email'] = 'Enter a valid email address.';
if ($phone !== '' && !valid_phone($phone)) $errors['phone'] = 'Enter a valid phone number or leave it blank.';
if ($subject === '') $errors['subject'] = 'Choose a subject.';
if (mb_strlen($message) < 10) $errors['message'] = 'Write at least 10 characters so we can understand your request.';
if ($errors) json_response(['success' => false, 'error' => 'Please check the highlighted fields.', 'errors' => $errors], 422);
try {
    $stmt = db()->prepare('INSERT INTO contact_submissions (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $name, $email, $phone, $subject, $message); $stmt->execute();
    json_response(['success' => true, 'message' => 'Your message has been saved. Thank you.'], 201);
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage()); json_response(['success' => false, 'error' => 'Unable to send your message right now.'], 500);
}
