<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
function order_payload(array $order): array
{
    $stmt = db()->prepare('SELECT product_id AS id, product_name AS name, quantity, unit_price AS price, special_request AS specialRequest FROM order_items WHERE order_id = ? ORDER BY order_items.id');
    $stmt->bind_param('i', $order['id']); $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($items as &$item) { $item['quantity'] = (int) $item['quantity']; $item['price'] = (float) $item['price']; }
    unset($item);
    $order['items'] = json_encode($items);
    $order['deliveryInfo'] = $order['delivery_details'] ? json_decode($order['delivery_details'], true) : null;
    $order['breakdown'] = $order['price_breakdown'] ? json_decode($order['price_breakdown'], true) : null;
    $order['total'] = (float) $order['total'];
    unset($order['request_key'], $order['delivery_details'], $order['price_breakdown']);
    return $order;
}
