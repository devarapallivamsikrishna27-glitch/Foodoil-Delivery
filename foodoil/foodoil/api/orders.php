<?php
/**
 * POST /api/orders.php  - create an order.
 * Prices, stock and totals are always recalculated here from the database; values sent
 * by the browser (other than product ids and quantities) are never trusted.
 */
require_once __DIR__ . '/../includes/functions.php';

if (!is_post()) {
    json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
}
if (!csrf_valid()) {
    json_out(['ok' => false, 'message' => 'Your session expired. Please refresh the page and try again.'], 419);
}
$user = current_user();
if (!$user) {
    json_out(['ok' => false, 'login' => true, 'message' => 'Please log in to place an order.'], 401);
}

$in = json_input();
$f = [
    'name'    => trim((string)($in['name'] ?? '')),
    'phone'   => preg_replace('/\D/', '', (string)($in['phone'] ?? '')),
    'address' => trim((string)($in['address'] ?? '')),
    'city'    => trim((string)($in['city'] ?? '')),
    'state'   => trim((string)($in['state'] ?? '')),
    'pincode' => preg_replace('/\D/', '', (string)($in['pincode'] ?? '')),
    'payment' => strtoupper(trim((string)($in['payment'] ?? ''))),
];
$err = [];
if (mb_strlen($f['name']) < 2 || mb_strlen($f['name']) > 100)           { $err['name'] = 'Enter your full name.'; }
if (!preg_match('/^[6-9]\d{9}$/', $f['phone']))                           { $err['phone'] = 'Enter a valid 10-digit mobile number.'; }
if (mb_strlen($f['address']) < 10 || mb_strlen($f['address']) > 255)      { $err['address'] = 'Enter your full delivery address (at least 10 characters).'; }
if (!preg_match('/^[\p{L} .\'-]{2,60}$/u', $f['city']))                   { $err['city'] = 'Enter a valid city name.'; }
if (!in_array($f['state'], INDIAN_STATES, true))                          { $err['state'] = 'Select your state.'; }
if (!preg_match('/^[1-9]\d{5}$/', $f['pincode']))                         { $err['pincode'] = 'Enter a valid 6-digit PIN code.'; }
if (!isset(PAYMENT_LABELS[$f['payment']]))                                { $err['payment'] = 'Choose a payment method.'; }
if ($err) {
    json_out(['ok' => false, 'message' => 'Please fix the highlighted fields.', 'errors' => $err], 422);
}

$items = is_array($in['items'] ?? null) ? $in['items'] : [];
if (!$items) {
    json_out(['ok' => false, 'message' => 'Your cart is empty.'], 422);
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $cart = resolve_cart($items, true);   // locks the product rows until commit

    if (!$cart['lines'] || $cart['removed'] || $cart['adjusted']) {
        $pdo->rollBack();
        json_out([
            'ok' => false, 'cartChanged' => true,
            'message' => 'Some items in your cart changed. Please review your cart and try again.',
            'details' => array_merge($cart['removed'], $cart['adjusted']),
        ], 409);
    }

    $ins = $pdo->prepare('INSERT INTO orders (order_code, user_id, customer_name, phone, address, city, state, pincode, subtotal, delivery_fee, total, payment_method, status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'Pending\')');
    $ins->execute([bin2hex(random_bytes(8)), $user['id'], $f['name'], $f['phone'], $f['address'], $f['city'], $f['state'], $f['pincode'],
                   $cart['subtotal'], $cart['delivery'], $cart['total'], $f['payment']]);
    $orderId = (int)$pdo->lastInsertId();
    $code    = 'FO' . $orderId;
    $pdo->prepare('UPDATE orders SET order_code = ? WHERE id = ?')->execute([$code, $orderId]);

    $item  = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, image, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stock = $pdo->prepare('UPDATE products SET stock = stock - ?, popularity = popularity + ? WHERE id = ? AND stock >= ?');
    foreach ($cart['lines'] as $l) {
        $item->execute([$orderId, $l['id'], $l['name'] . ' (' . $l['volume'] . ')', $l['raw_image'] ?: 'products/default.svg', $l['qty'], $l['price'], $l['subtotal']]);
        $stock->execute([$l['qty'], $l['qty'], $l['id'], $l['qty']]);
        if ($stock->rowCount() !== 1) {
            throw new RuntimeException('Stock changed while placing the order.');
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

json_out(['ok' => true, 'code' => $code, 'redirect' => url('order-success.php?code=' . urlencode($code))]);
