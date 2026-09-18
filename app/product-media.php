<?php
declare(strict_types=1);

/** Serve the starter photos locally while preserving administrator-supplied URLs. */
function product_image_url(string $source): string
{
    $photos = [
        'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?auto=format&fit=crop&w=900&q=80' => 'avocado-toast',
        'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=80' => 'harvest-bowl',
        'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=900&q=80' => 'berry-parfait',
        'https://images.unsplash.com/photo-1610970881699-44a5587cabec?auto=format&fit=crop&w=900&q=80' => 'green-smoothie',
    ];
    return isset($photos[$source]) ? 'assets/images/' . $photos[$source] . '.jpg' : $source;
}
