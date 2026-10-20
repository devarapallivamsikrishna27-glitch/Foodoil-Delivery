<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pdo = db();

function is_upload_path(string $p): bool { return (bool)preg_match('#^uploads/[a-f0-9]{16}\.(jpg|png|webp)$#', $p); }
function drop_upload(string $p): void { if (is_upload_path($p)) { @unlink(__DIR__ . '/../assets/images/' . $p); } }

function save_product_image(array $file, array &$errors): ?string
{
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code === UPLOAD_ERR_NO_FILE) { return null; }
    if ($code !== UPLOAD_ERR_OK) { $errors['image'] = 'Image upload failed. Try a smaller file.'; return null; }
    if ($file['size'] > 2 * 1024 * 1024) { $errors['image'] = 'Image must be 2 MB or smaller.'; return null; }
    $info = @getimagesize($file['tmp_name']);
    $map  = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($map[$info[2]])) { $errors['image'] = 'Upload a JPG, PNG or WebP image.'; return null; }
    $dir = __DIR__ . '/../assets/images/uploads';
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    $name = 'uploads/' . bin2hex(random_bytes(8)) . '.' . $map[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../assets/images/' . $name)) { $errors['image'] = 'Could not save the image on the server.'; return null; }
    return $name;
}

$errors = [];
$editId = (int)($_GET['edit'] ?? 0);
$f = ['id' => 0, 'name' => '', 'category' => '', 'volume' => '', 'price' => '', 'mrp' => '', 'stock' => '0', 'description' => '', 'is_active' => 1, 'image' => ''];

if (is_post()) {
    csrf_require();
    $action = post('action');

    if ($action === 'delete') {
        $id = (int)post('id');
        $s = $pdo->prepare('SELECT image FROM products WHERE id = ?'); $s->execute([$id]); $row = $s->fetch();
        if ($row) {
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            drop_upload((string)$row['image']);
            flash('success', 'Product deleted. Past orders keep their saved product names.');
        }
        redirect('admin/products.php');
    }

    if ($action === 'stock') {
        $id = (int)post('id'); $stockRaw = post('stock');
        if (ctype_digit($stockRaw) && (int)$stockRaw <= 100000) {
            $pdo->prepare('UPDATE products SET stock = ? WHERE id = ?')->execute([(int)$stockRaw, $id]);
            flash('success', 'Stock updated.');
        } else {
            flash('error', 'Stock must be a whole number between 0 and 100000.');
        }
        redirect('admin/products.php');
    }

    if ($action === 'save') {
        $f = [
            'id' => (int)post('id'), 'name' => post('name'), 'category' => post('category'), 'volume' => post('volume'),
            'price' => post('price'), 'mrp' => post('mrp'), 'stock' => post('stock', '0'), 'description' => post('description'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0, 'image' => '',
        ];
        if (mb_strlen($f['name']) < 2 || mb_strlen($f['name']) > 120) { $errors['name'] = 'Enter a product name (2 to 120 characters).'; }
        if (!isset(CATEGORIES[$f['category']])) { $errors['category'] = 'Choose a category.'; }
        if ($f['volume'] === '' || mb_strlen($f['volume']) > 30) { $errors['volume'] = 'Enter the volume, for example 1 Litre.'; }
        if (!is_numeric($f['price']) || (float)$f['price'] <= 0 || (float)$f['price'] > 100000) { $errors['price'] = 'Enter a price greater than 0.'; }
        $mrp = $f['mrp'] === '' ? $f['price'] : $f['mrp'];
        if (!isset($errors['price']) && (!is_numeric($mrp) || (float)$mrp < (float)$f['price'])) { $errors['mrp'] = 'MRP must be equal to or higher than the price.'; }
        if (!ctype_digit($f['stock']) || (int)$f['stock'] > 100000) { $errors['stock'] = 'Stock must be a whole number (0 or more).'; }
        if (mb_strlen($f['description']) > 1000) { $errors['description'] = 'Description can be at most 1000 characters.'; }

        $old = null;
        if ($f['id'] > 0) {
            $s = $pdo->prepare('SELECT * FROM products WHERE id = ?'); $s->execute([$f['id']]); $old = $s->fetch();
            if (!$old) { flash('error', 'That product no longer exists.'); redirect('admin/products.php'); }
        }
        $newImage = save_product_image($_FILES['image'] ?? [], $errors);

        if (!$errors) {
            if ($old) {
                $img = $newImage ?? $old['image'];
                $pdo->prepare('UPDATE products SET name=?, category=?, volume=?, price=?, mrp=?, description=?, image=?, stock=?, is_active=? WHERE id=?')
                    ->execute([$f['name'], $f['category'], $f['volume'], (float)$f['price'], (float)$mrp, $f['description'], $img, (int)$f['stock'], $f['is_active'], $f['id']]);
                if ($newImage) { drop_upload((string)$old['image']); }
                flash('success', 'Product updated.');
            } else {
                $pdo->prepare('INSERT INTO products (name, category, volume, price, mrp, description, image, stock, is_active) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute([$f['name'], $f['category'], $f['volume'], (float)$f['price'], (float)$mrp, $f['description'], $newImage ?? 'products/default.svg', (int)$f['stock'], $f['is_active']]);
                flash('success', 'Product added.');
            }
            redirect('admin/products.php');
        } elseif ($newImage) {
            drop_upload($newImage);   // validation failed elsewhere: do not keep the orphan file
        }
        $editId = $f['id'];
    }
} elseif ($editId > 0) {
    $s = $pdo->prepare('SELECT * FROM products WHERE id = ?'); $s->execute([$editId]);
    $row = $s->fetch();
    if ($row) { $f = array_merge($f, $row); } else { flash('error', 'Product not found.'); redirect('admin/products.php'); }
}

$products = $pdo->query('SELECT * FROM products ORDER BY category, price')->fetchAll();
$fe = fn($k) => isset($errors[$k]) ? '<small class="field-error show">' . e($errors[$k]) . '</small>' : '';
$adminTitle = 'Products'; $adminActive = 'products';
include __DIR__ . '/_header.php';
?>
<div class="panel" id="product-form">
  <div class="panel-head"><h2><?= $f['id'] ? 'Edit product' : 'Add a product' ?></h2><?php if ($f['id']): ?><a class="btn btn-outline btn-sm" href="<?= e(url('admin/products.php')) ?>">Cancel editing</a><?php endif; ?></div>
  <form method="post" enctype="multipart/form-data" action="<?= e(url('admin/products.php')) ?>" class="pad" novalidate>
    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
    <div class="form-grid cols-3">
      <div class="field span-2"><label for="name">Product name</label><input id="name" name="name" value="<?= e($f['name']) ?>" maxlength="120" required><?= $fe('name') ?></div>
      <div class="field"><label for="category">Category</label>
        <select id="category" name="category" required><option value="">Select category</option>
          <?php foreach (CATEGORIES as $c => $icon): ?><option value="<?= e($c) ?>" <?= $f['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select><?= $fe('category') ?></div>
      <div class="field"><label for="volume">Volume</label><input id="volume" name="volume" value="<?= e($f['volume']) ?>" placeholder="1 Litre" maxlength="30" required><?= $fe('volume') ?></div>
      <div class="field"><label for="price">Price (&#8377;)</label><input id="price" name="price" type="number" step="0.01" min="1" value="<?= e($f['price']) ?>" required><?= $fe('price') ?></div>
      <div class="field"><label for="mrp">MRP (&#8377;) <small class="muted">optional</small></label><input id="mrp" name="mrp" type="number" step="0.01" min="1" value="<?= e($f['mrp']) ?>"><?= $fe('mrp') ?></div>
      <div class="field"><label for="stock">Stock</label><input id="stock" name="stock" type="number" min="0" step="1" value="<?= e($f['stock']) ?>" required><?= $fe('stock') ?></div>
      <div class="field"><label for="image">Image <small class="muted">JPG, PNG or WebP, max 2 MB</small></label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><?= $fe('image') ?>
        <?php if ($f['id'] && $f['image']): ?><small class="muted">Current: <?= e($f['image']) ?> (leave empty to keep)</small><?php endif; ?></div>
      <div class="field span-3"><label for="description">Description</label><textarea id="description" name="description" rows="3" maxlength="1000"><?= e($f['description']) ?></textarea><?= $fe('description') ?></div>
      <div class="field span-3"><label class="check"><input type="checkbox" name="is_active" value="1" <?= $f['is_active'] ? 'checked' : '' ?>> Show this product in the store</label></div>
    </div>
    <button type="submit" class="btn btn-primary"><?= $f['id'] ? 'Save changes' : 'Add product' ?></button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>All products <span class="muted small">(<?= count($products) ?>)</span></h2></div>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Visible</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><div class="cell-prod"><img src="<?= e(product_image($p)) ?>" alt=""><div><b><?= e($p['name']) ?></b><br><small class="muted"><?= e($p['volume']) ?></small></div></div></td>
        <td><?= e($p['category']) ?></td>
        <td><?= e(money($p['price'])) ?><?= $p['mrp'] > $p['price'] ? '<br><small class="muted"><s>' . e(money($p['mrp'])) . '</s></small>' : '' ?></td>
        <td>
          <form method="post" action="<?= e(url('admin/products.php')) ?>" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="stock"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <label class="sr-only" for="st<?= (int)$p['id'] ?>">Stock for <?= e($p['name']) ?></label>
            <input id="st<?= (int)$p['id'] ?>" class="stock-input <?= (int)$p['stock'] <= 10 ? 'low' : '' ?>" type="number" name="stock" min="0" value="<?= (int)$p['stock'] ?>">
            <button class="btn btn-outline btn-sm" type="submit">Update</button>
          </form>
        </td>
        <td><?= $p['is_active'] ? '<span class="status status-delivered">Yes</span>' : '<span class="status status-cancelled">Hidden</span>' ?></td>
        <td class="actions">
          <a class="btn btn-outline btn-sm" href="<?= e(url('admin/products.php?edit=' . (int)$p['id'])) ?>#product-form">Edit</a>
          <form method="post" action="<?= e(url('admin/products.php')) ?>" class="inline-form" data-confirm="Delete <?= e($p['name'] . ' (' . $p['volume'] . ')') ?>? This cannot be undone.">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-danger-outline btn-sm" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
