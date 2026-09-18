<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_method('POST');
$userId = require_login();
$input = json_input();
$rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT);
$review = clean_text($input['review'] ?? '', 1000);
if ($rating === false || $rating < 1 || $rating > 5 || mb_strlen($review) < 3) json_response(['success' => false, 'error' => 'Choose 1–5 stars and write at least 3 characters.'], 422);
try {
    $stmt = db()->prepare('INSERT INTO reviews (user_id, rating, review) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $userId, $rating, $review); $stmt->execute();
    json_response(['success' => true], 201);
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage()); json_response(['success' => false, 'error' => 'Unable to save your review.'], 500);
}
