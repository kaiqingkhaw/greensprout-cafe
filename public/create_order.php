<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config.php';
require_method('POST');
$userId = require_login();
$input = json_input();
$code = strtoupper(clean_text($input['discount_code'] ?? '', 30));
$paymentMethod = $input['payment_method'] ?? 'cash';
if (!is_string($paymentMethod) || !in_array($paymentMethod, ['cash', 'card_demo', 'ewallet_demo'], true)) json_response(['error' => 'Choose a supported payment method.'], 422);
$items = $input['items'] ?? null;
if (is_string($items)) $items = json_decode($items, true);
$requestKey = clean_text($input['request_key'] ?? '', 64);
if (!preg_match('/^[a-zA-Z0-9-]{16,64}$/', $requestKey)) json_response(['error' => 'Missing checkout identifier. Refresh and try again.'], 422);
$deliveryInfo = $input['delivery_info'] ?? null;
if (!is_array($deliveryInfo)) json_response(['error' => 'Delivery details are required.'], 422);
foreach (['fullName'=>100, 'email'=>190, 'phone'=>30, 'address'=>255, 'specialInstructions'=>500] as $key=>$limit) {
    $deliveryInfo[$key] = clean_text($deliveryInfo[$key] ?? '', $limit);
}
if ($deliveryInfo['fullName'] === '' || !filter_var($deliveryInfo['email'], FILTER_VALIDATE_EMAIL) || !valid_phone($deliveryInfo['phone']) || strlen($deliveryInfo['address']) < 10 || stripos($deliveryInfo['address'], 'Kuala Lumpur') === false) json_response(['error' => 'Enter valid contact details and a Kuala Lumpur delivery address.'], 422);
$deliveryInfo = array_intersect_key($deliveryInfo, array_flip(['fullName','email','phone','address','specialInstructions']));
$deliveryInfo['deliveryFee'] = 5.00;
if (!is_array($items) || !$items || count($items) > 50) json_response(['error' => 'Your cart is empty or invalid.'], 422);

$productIds = [];
$productNotes = [];
foreach ($items as $item) {
    if (!is_array($item)) json_response(['error' => 'Invalid cart item.'], 422);
    if (!is_int($item['id'] ?? null) || !is_int($item['quantity'] ?? null)) json_response(['error'=>'Item IDs and quantities must be whole numbers.'],422);
    $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || !$quantity || $quantity < 1 || $quantity > 20) json_response(['error' => 'One or more cart items are invalid.'], 422);
    $productIds[$id] = ($productIds[$id] ?? 0) + $quantity;
    $note = clean_text($item['specialRequest'] ?? '', 200);
    if ($note !== '') $productNotes[$id][] = $quantity . ' × ' . $note;
    if ($productIds[$id] > 20) json_response(['error' => 'Maximum 20 of each product per order.'], 422);
}

try {
    $connection = db();
    $existing = $connection->prepare('SELECT * FROM orders WHERE user_id = ? AND request_key = ?');
    $existing->bind_param('is', $userId, $requestKey); $existing->execute();
    if ($order = $existing->get_result()->fetch_assoc()) {
        $oldItems = json_decode($order['items'], true);
        $oldQuantities = [];
        foreach ($oldItems as $line) $oldQuantities[(int) $line['id']] = (int) $line['quantity'];
        $oldBreakdown = json_decode($order['price_breakdown'], true);
        $expectedDiscount = $code === 'ORGANIC10' ? min(10.0, (float) $oldBreakdown['subtotal']) : 0.0;
        $notesMatch = true;
        foreach ($oldItems as $line) if (($line['specialRequest'] ?? '') !== implode('; ', $productNotes[(int) $line['id']] ?? [])) $notesMatch = false;
        if (($order['payment_method'] ?? 'cash') !== $paymentMethod || $oldQuantities != $productIds || json_decode($order['delivery_details'], true) != $deliveryInfo || !$notesMatch || !in_array($code, ['', 'ORGANIC10'], true) || (float) $oldBreakdown['discount'] !== $expectedDiscount) {
            json_response(['error'=>'This checkout was already saved with different details. Open Invoice to review it before placing another order.'],409);
        }
        require_once dirname(__DIR__) . '/app/order-data.php';
        $payload = order_payload($order);
        json_response(['success'=>true, 'order_number'=>$order['order_number'], 'total'=>$payload['total'], 'items'=>json_decode($payload['items'],true), 'breakdown'=>$payload['breakdown']]);
    }
    $connection->begin_transaction();
    $verifiedItems = []; $subtotal = 0.0;
    $stmt = $connection->prepare('SELECT id, name, price, discounted, discount_percent FROM products WHERE id = ?');
    foreach ($productIds as $id => $quantity) {
        $stmt->bind_param('i', $id); $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if (!$product) throw new DomainException('A product in your cart is no longer available.');
        $price = (float) $product['price'];
        if ((int) $product['discounted'] === 1) $price *= 1 - ((int) $product['discount_percent'] / 100);
        $price = round($price, 2); $subtotal += $price * $quantity;
        $notes = implode('; ', $productNotes[$id] ?? []);
        if (mb_strlen($notes) > 500) throw new DomainException('Please shorten the special requests for this item.');
        $verifiedItems[] = ['id' => (int) $id, 'name' => $product['name'], 'price' => $price, 'quantity' => $quantity, 'specialRequest' => $notes];
    }
    $service = round($subtotal * 0.05, 2);
    $delivery = 5.00;
    if ($code !== '' && $code !== 'ORGANIC10') throw new DomainException('Invalid discount code.');
    $discount = $code === 'ORGANIC10' ? min(10.00, $subtotal) : 0.00;
    $total = round($subtotal + $service + $delivery - $discount, 2);
    $orderNumber = 'GS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(8)));
    $orderDate = date('Y-m-d H:i:s'); $status = 'pending'; $estimated = 45;
    $itemsJson = json_encode($verifiedItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $insert = $connection->prepare('INSERT INTO orders (user_id, order_number, order_date, items, total, status, estimated_delivery_minutes) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insert->bind_param('isssdsi', $userId, $orderNumber, $orderDate, $itemsJson, $total, $status, $estimated); $insert->execute();
    $orderId = $connection->insert_id;
    $line = $connection->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, special_request) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($verifiedItems as $item) {
        $line->bind_param('iisids', $orderId, $item['id'], $item['name'], $item['quantity'], $item['price'], $item['specialRequest']); $line->execute();
    }
    $breakdown = json_encode(['subtotal'=>round($subtotal,2),'service'=>$service,'delivery'=>$delivery,'discount'=>$discount,'total'=>$total]);
    $deliveryJson = json_encode($deliveryInfo);
    $metadata = $connection->prepare('UPDATE orders SET delivery_details = ?, price_breakdown = ?, request_key = ?, payment_method = ? WHERE id = ?');
    $metadata->bind_param('ssssi', $deliveryJson, $breakdown, $requestKey, $paymentMethod, $orderId); $metadata->execute();
    if (($input['save_address'] ?? false) === true) {
        $addressUpdate = $connection->prepare('UPDATE users SET address = ? WHERE id = ?');
        $addressUpdate->bind_param('si', $deliveryInfo['address'], $userId); $addressUpdate->execute();
    }
    $connection->commit();
    json_response(['success' => true, 'order_number' => $orderNumber, 'total' => $total, 'items' => $verifiedItems,
        'breakdown' => ['subtotal' => round($subtotal, 2), 'service' => $service, 'delivery' => $delivery, 'discount' => $discount, 'total' => $total]], 201);
} catch (DomainException $exception) {
    if (isset($connection)) $connection->rollback(); json_response(['error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    if (isset($connection)) $connection->rollback(); error_log($exception->getMessage()); json_response(['error' => 'Failed to save the order.'], 500);
}
