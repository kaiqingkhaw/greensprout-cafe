<?php
declare(strict_types=1);
require __DIR__ . '/../app/product-media.php';
$original = 'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?auto=format&fit=crop&w=900&q=80';
$local = product_image_url($original);
if ($local !== 'assets/images/avocado-toast.jpg' || !is_file(__DIR__ . '/../public/' . $local)) {
    throw new RuntimeException('The starter photo must resolve to its bundled file.');
}
foreach (['', 'https://example.invalid/custom.jpg', $original . '&crop=faces'] as $custom) {
    if (product_image_url($custom) !== $custom) throw new RuntimeException('Custom image URLs must be preserved.');
}
echo "PASS bundled starter photo, existing asset, blank image and custom URLs.\n";
