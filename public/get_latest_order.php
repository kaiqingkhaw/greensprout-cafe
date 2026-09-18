<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/order-data.php';
require_method('GET');
$userId = require_login();
try {
    $all = ($_GET['include_completed'] ?? '') === '1';
    $sql = 'SELECT * FROM orders WHERE user_id = ?' . ($all ? '' : " AND status NOT IN ('completed','cancelled')") . ' ORDER BY id DESC LIMIT 1';
    $stmt = db()->prepare($sql); $stmt->bind_param('i', $userId); $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) json_response(['error' => 'No orders found.'], 404);
    json_response(order_payload($order));
} catch (Throwable $e) { error_log($e->getMessage()); json_response(['error' => 'Unable to load your order.'], 500); }
