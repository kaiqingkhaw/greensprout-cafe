<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/app/config.php';
$conn = db();
$conn->query('CREATE TABLE IF NOT EXISTS request_limits (bucket CHAR(64) PRIMARY KEY, hits INT UNSIGNED NOT NULL, expires_at BIGINT UNSIGNED NOT NULL, INDEX (expires_at)) ENGINE=InnoDB');
if ($conn->query("SHOW COLUMNS FROM order_items LIKE 'special_request'")->num_rows === 0) {
    $conn->query("ALTER TABLE order_items ADD COLUMN special_request VARCHAR(500) NOT NULL DEFAULT ''");
}
$columns = [
    'delivery_details' => 'JSON NULL',
    'price_breakdown' => 'JSON NULL',
    'request_key' => 'VARCHAR(64) NULL',
    'payment_method' => "VARCHAR(20) NOT NULL DEFAULT 'cash'"
];
foreach ($columns as $name => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM orders LIKE '$name'");
    if ($check->num_rows === 0) $conn->query("ALTER TABLE orders ADD COLUMN $name $definition");
}
if ($conn->query("SHOW INDEX FROM orders WHERE Key_name = 'uq_order_request'")->num_rows === 0) {
    $conn->query('ALTER TABLE orders ADD UNIQUE KEY uq_order_request (user_id, request_key)');
}
$conn->begin_transaction();
try {
    $orders = $conn->query('SELECT id, items FROM orders WHERE NOT EXISTS (SELECT 1 FROM order_items WHERE order_id = orders.id)');
    $insert = $conn->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)');
    $find = $conn->prepare('SELECT id FROM products WHERE id = ?');
    while ($order = $orders->fetch_assoc()) {
        $items = json_decode($order['items'], true, 512, JSON_THROW_ON_ERROR);
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0); $find->bind_param('i', $id); $find->execute();
            $productId = $find->get_result()->num_rows ? $id : null;
            $name = (string) $item['name']; $quantity = (int) $item['quantity']; $price = (float) $item['price'];
            if ($quantity < 1 || $price < 0) throw new RuntimeException('Invalid historical order; migration stopped.');
            $insert->bind_param('iisid', $order['id'], $productId, $name, $quantity, $price); $insert->execute();
        }
    }
    $conn->commit();
    echo "Order migration complete. Existing orders preserved.\n";
} catch (Throwable $e) { $conn->rollback(); throw $e; }
