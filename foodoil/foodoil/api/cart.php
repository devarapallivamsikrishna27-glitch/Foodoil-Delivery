<?php
/**
 * POST /api/cart.php   body: {"items":[{"id":1,"qty":2}, ...]}
 * Returns trusted cart lines with current database prices, stock-limited quantities and totals.
 * The cart itself lives in the browser (localStorage); this endpoint validates it.
 */
require_once __DIR__ . '/../includes/functions.php';

if (!is_post()) {
    json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
}
if (!csrf_valid()) {
    json_out(['ok' => false, 'message' => 'Your session expired. Please refresh the page.'], 419);
}
$in    = json_input();
$items = is_array($in['items'] ?? null) ? $in['items'] : [];
$cart  = resolve_cart($items);
foreach ($cart['lines'] as &$line) {
    unset($line['raw_image']);
}
unset($line);
json_out(['ok' => true] + $cart);
