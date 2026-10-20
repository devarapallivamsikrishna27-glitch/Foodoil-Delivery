<?php
/**
 * FoodOil - shared helpers: sessions, CSRF, auth, cart maths, rendering.
 */
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Kolkata');

const SITE_NAME           = 'FoodOil';
const DELIVERY_FEE        = 40.00;
const FREE_DELIVERY_ABOVE = 999.00;
const MAX_QTY             = 10;
const ORDER_STATUSES      = ['Pending', 'Confirmed', 'Out for Delivery', 'Delivered', 'Cancelled'];
const PAYMENT_LABELS      = ['COD' => 'Cash on Delivery', 'UPI' => 'UPI (demo)', 'CARD' => 'Card (demo)'];
const CATEGORIES          = [
    'Sunflower Oil'  => 'fa-sun',
    'Groundnut Oil'  => 'fa-seedling',
    'Coconut Oil'    => 'fa-leaf',
    'Mustard Oil'    => 'fa-pepper-hot',
    'Olive Oil'      => 'fa-lemon',
    'Rice Bran Oil'  => 'fa-wheat-awn',
    'Sesame Oil'     => 'fa-bowl-food',
];

/* ---------- session ---------- */
if (session_status() === PHP_SESSION_NONE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('FOODOILSESS');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
    session_start();
}

/* ---------- error handling ---------- */
set_exception_handler(function ($ex) {
    error_log('FoodOil error: ' . $ex);
    http_response_code(500);
    if (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => 'Something went wrong on the server. Please try again.']);
    } else {
        $detail = APP_DEBUG ? '<pre>' . htmlspecialchars((string)$ex) . '</pre>' : '';
        echo '<!doctype html><meta charset="utf-8"><title>Error</title><body style="font-family:Arial,sans-serif;max-width:560px;margin:60px auto">'
           . '<h2>Something went wrong</h2><p>Please go back and try again.</p>' . $detail . '</body>';
    }
    exit;
});

/* ---------- small helpers ---------- */
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path)
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function money($n): string
{
    $n = (float)$n;
    return '₹' . number_format($n, floor($n) == $n ? 0 : 2);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function render_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    foreach ($_SESSION['flash'] as $f) {
        $icon = $f['type'] === 'success' ? 'fa-circle-check' : ($f['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info');
        echo '<div class="alert alert-' . e($f['type']) . '" role="alert"><i class="fa-solid ' . $icon . '" aria-hidden="true"></i><span>' . e($f['message']) . '</span></div>';
    }
    unset($_SESSION['flash']);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $t = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function csrf_require(): void
{
    if (!csrf_valid()) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}

/* ---------- authentication ---------- */
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare('SELECT id, name, email, phone, role FROM users WHERE id = ?');
            $s->execute([(int)$_SESSION['uid']]);
            $user = $s->fetch() ?: null;
            if (!$user) {
                unset($_SESSION['uid']);
            }
        }
    }
    return $user;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

function safe_next(?string $next): string
{
    $next = (string)$next;
    return preg_match('/^[a-z0-9\-]+\.php(\?[A-Za-z0-9=&%_\-]*)?$/i', $next) ? $next : '';
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('info', 'Please log in to continue.');
        $self = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $qs   = $_SERVER['QUERY_STRING'] ?? '';
        redirect('login.php?next=' . urlencode($self . ($qs !== '' ? '?' . $qs : '')));
    }
    return $u;
}

function require_admin(): array
{
    $u = current_user();
    if (!$u) {
        flash('info', 'Please log in with an admin account.');
        redirect('login.php?next=' . urlencode('admin/dashboard.php'));
    }
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><title>Access denied</title><body style="font-family:Arial,sans-serif;max-width:520px;margin:80px auto">'
           . '<h2>Access denied</h2><p>Only administrators can open this page.</p><p><a href="' . e(url('index.php')) . '">Back to FoodOil</a></p></body>';
        exit;
    }
    return $u;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    unset($_SESSION['login_fail'], $_SESSION['lock_until']);
}

/* ---------- products & rendering ---------- */
function product_image(array $p): string
{
    $img = !empty($p['image']) ? $p['image'] : 'products/default.svg';
    return url('assets/images/' . $img);
}

function discount_percent(array $p): int
{
    return ($p['mrp'] > $p['price']) ? (int)round(($p['mrp'] - $p['price']) / $p['mrp'] * 100) : 0;
}

function stock_info(int $stock): array
{
    if ($stock <= 0) {
        return ['out', 'Out of stock'];
    }
    if ($stock <= 10) {
        return ['low', 'Only ' . $stock . ' left'];
    }
    return ['in', 'In stock'];
}

function stars(float $rating): string
{
    $html = '<span class="stars" aria-label="Rated ' . e(number_format($rating, 1)) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $cls = $rating >= $i ? 'fa-solid fa-star' : ($rating >= $i - 0.5 ? 'fa-solid fa-star-half-stroke' : 'fa-regular fa-star');
        $html .= '<i class="' . $cls . '" aria-hidden="true"></i>';
    }
    return $html . '</span>';
}

function status_class(string $status): string
{
    return 'status-' . strtolower(str_replace(' ', '-', $status));
}

function render_product_card(array $p): void
{
    [$sc, $sl] = stock_info((int)$p['stock']);
    $detail = url('product-details.php?id=' . (int)$p['id']);
    $disc   = discount_percent($p);
    echo '<article class="product-card">';
    echo '<a class="product-card-img" href="' . e($detail) . '"><img src="' . e(product_image($p)) . '" alt="' . e($p['name'] . ' ' . $p['volume']) . '" loading="lazy">';
    if ($disc > 0) {
        echo '<span class="badge-off">' . $disc . '% OFF</span>';
    }
    echo '</a><div class="product-card-body">';
    echo '<h3><a href="' . e($detail) . '">' . e($p['name']) . '</a></h3>';
    echo '<p class="muted small">' . e($p['volume']) . '</p>';
    echo '<p class="price">' . e(money($p['price'])) . ($disc > 0 ? ' <s>' . e(money($p['mrp'])) . '</s>' : '') . '</p>';
    echo '<p class="stock stock-' . $sc . '"><span class="dot"></span>' . e($sl) . '</p>';
    if ((int)$p['stock'] > 0) {
        echo '<button type="button" class="btn btn-primary btn-block" data-add-to-cart data-id="' . (int)$p['id'] . '" data-stock="' . (int)$p['stock'] . '" data-name="' . e($p['name']) . '">Add to Cart</button>';
    } else {
        echo '<button type="button" class="btn btn-disabled btn-block" disabled>Out of Stock</button>';
    }
    echo '</div></article>';
}

/* ---------- cart maths (server is the source of truth) ---------- */
function delivery_fee_for(float $subtotal): float
{
    return ($subtotal <= 0 || $subtotal >= FREE_DELIVERY_ABOVE) ? 0.0 : DELIVERY_FEE;
}

/**
 * Turns [{id, qty}, ...] from the browser into trusted cart lines using database prices.
 * Unknown / inactive / out-of-stock products are reported in 'removed';
 * quantities above stock or above MAX_QTY are reported in 'adjusted'.
 */
function resolve_cart(array $rawItems, bool $lock = false): array
{
    $wanted = [];
    foreach ($rawItems as $it) {
        if (!is_array($it)) {
            continue;
        }
        $id  = (int)($it['id'] ?? 0);
        $qty = (int)($it['qty'] ?? 0);
        if ($id > 0 && $qty > 0) {
            $wanted[$id] = ($wanted[$id] ?? 0) + $qty;
        }
        if (count($wanted) >= 40) {
            break;
        }
    }
    $result = ['lines' => [], 'removed' => [], 'adjusted' => [], 'subtotal' => 0.0, 'delivery' => 0.0, 'total' => 0.0, 'count' => 0];
    if (!$wanted) {
        return $result;
    }
    $ids = array_keys($wanted);
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT id, name, volume, price, mrp, image, stock FROM products WHERE is_active = 1 AND id IN ($in)" . ($lock ? ' FOR UPDATE' : '');
    $st  = db()->prepare($sql);
    $st->execute($ids);
    $found = [];
    foreach ($st->fetchAll() as $row) {
        $found[(int)$row['id']] = $row;
    }
    foreach ($wanted as $id => $qty) {
        if (!isset($found[$id])) {
            $result['removed'][] = 'An item that is no longer available';
            continue;
        }
        $p     = $found[$id];
        $stock = (int)$p['stock'];
        if ($stock <= 0) {
            $result['removed'][] = $p['name'] . ' (' . $p['volume'] . ') is out of stock';
            continue;
        }
        $max = min($stock, MAX_QTY);
        if ($qty > $max) {
            $result['adjusted'][] = $p['name'] . ' (' . $p['volume'] . ') quantity changed to ' . $max;
            $qty = $max;
        }
        $sub = round((float)$p['price'] * $qty, 2);
        $result['lines'][] = [
            'id' => (int)$p['id'], 'name' => $p['name'], 'volume' => $p['volume'],
            'price' => (float)$p['price'], 'image' => product_image($p),
            'qty' => $qty, 'stock' => $stock, 'max' => $max, 'subtotal' => $sub,
            'raw_image' => $p['image'],
        ];
        $result['subtotal'] += $sub;
        $result['count']    += $qty;
    }
    $result['subtotal'] = round($result['subtotal'], 2);
    $result['delivery'] = delivery_fee_for($result['subtotal']);
    $result['total']    = round($result['subtotal'] + $result['delivery'], 2);
    return $result;
}

/* ---------- orders ---------- */
function restock_order(PDO $pdo, int $orderId): void
{
    $s = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ? AND product_id IS NOT NULL');
    $s->execute([$orderId]);
    $u = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
    foreach ($s->fetchAll() as $r) {
        $u->execute([(int)$r['quantity'], (int)$r['product_id']]);
    }
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $d   = json_decode($raw ?: '', true);
    return is_array($d) ? $d : [];
}

const INDIAN_STATES = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana',
    'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
    'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana',
    'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Andaman and Nicobar Islands', 'Chandigarh',
    'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry',
];
