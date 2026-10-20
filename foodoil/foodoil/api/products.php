<?php
/**
 * GET /api/products.php?q=oil   - small JSON search used by the header search box.
 */
require_once __DIR__ . '/../includes/functions.php';

$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60);
if (mb_strlen($q) < 2) {
    json_out(['ok' => true, 'products' => []]);
}
$like = '%' . addcslashes($q, '%_\\') . '%';
$s = db()->prepare('SELECT id, name, volume, price, image FROM products WHERE is_active = 1 AND (name LIKE ? OR category LIKE ? OR volume LIKE ?) ORDER BY popularity DESC LIMIT 6');
$s->execute([$like, $like, $like]);
$out = [];
foreach ($s->fetchAll() as $p) {
    $out[] = [
        'id' => (int)$p['id'], 'name' => $p['name'], 'volume' => $p['volume'],
        'price' => (float)$p['price'], 'image' => product_image($p),
        'url' => url('product-details.php?id=' . (int)$p['id']),
    ];
}
json_out(['ok' => true, 'products' => $out]);
