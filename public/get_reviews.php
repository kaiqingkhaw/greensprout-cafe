<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config.php';
require_method('GET');
try {
    $result = db()->query('SELECT u.name, r.rating, r.review, r.created_at AS date FROM reviews r JOIN users u ON u.id = r.user_id ORDER BY r.id DESC LIMIT 6');
    json_response(['reviews' => $result->fetch_all(MYSQLI_ASSOC)]);
} catch (Throwable $error) {
    error_log($error->getMessage());
    json_response(['error' => 'Reviews are temporarily unavailable.'], 500);
}
