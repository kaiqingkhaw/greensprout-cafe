<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/order-data.php';
require_method('GET');
$userId = require_login();
$number = clean_text($_GET['order_number'] ?? '', 32);
if ($number === '') json_response(['error' => 'Order number is required.'], 422);
try {
    $stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? AND order_number = ?');
    $stmt->bind_param('is', $userId, $number); $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) json_response(['error' => 'Order not found.'], 404);
    json_response(order_payload($order));
} catch (Throwable $e) { error_log($e->getMessage()); json_response(['error' => 'Unable to load your order.'], 500); }
