<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/product-media.php';
try {
    $result = db()->query('SELECT id, name, description, price, category, image, featured, discounted, discount_percent AS discountPercent, calories, protein, carbs, fats, fiber FROM products ORDER BY name');
    $products = [];
    while ($row = $result->fetch_assoc()) {
        foreach (['id', 'discountPercent', 'calories', 'protein', 'carbs', 'fats', 'fiber'] as $key) $row[$key] = (int) $row[$key];
        $row['price'] = (float) $row['price']; $row['featured'] = (bool) $row['featured']; $row['discounted'] = (bool) $row['discounted'];
        $row['image'] = product_image_url($row['image']);
        $products[] = $row;
    }
    json_response($products);
} catch (mysqli_sql_exception $exception) {
    error_log($exception->getMessage()); json_response(['error' => 'Unable to load products.'], 500);
}
