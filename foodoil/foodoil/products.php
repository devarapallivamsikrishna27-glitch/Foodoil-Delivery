<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Products';
$activeNav = 'products';

$q    = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 60);
$cat  = (string)($_GET['cat'] ?? '');
$sort = (string)($_GET['sort'] ?? 'popularity');
if (!isset(CATEGORIES[$cat])) { $cat = ''; }
$sorts = [
    'popularity' => ['Popularity',           'popularity DESC, id ASC'],
    'price_asc'  => ['Price: Low to High',   'price ASC, id ASC'],
    'price_desc' => ['Price: High to Low',   'price DESC, id ASC'],
    'name'       => ['Name: A to Z',         'name ASC, price ASC'],
];
if (!isset($sorts[$sort])) { $sort = 'popularity'; }

$where  = ['is_active = 1'];
$params = [];
if ($cat !== '') { $where[] = 'category = ?'; $params[] = $cat; }
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(name LIKE ? OR category LIKE ? OR volume LIKE ?)';
    array_push($params, $like, $like, $like);
}
$stmt = db()->prepare('SELECT * FROM products WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $sorts[$sort][1]);
$stmt->execute($params);
$products = $stmt->fetchAll();

function catalog_url(array $over): string {
    $base = ['q' => $_GET['q'] ?? '', 'cat' => $_GET['cat'] ?? '', 'sort' => $_GET['sort'] ?? ''];
    $merged = array_filter(array_merge($base, $over), fn($v) => $v !== '' && $v !== null);
    return url('products.php') . ($merged ? '?' . http_build_query($merged) : '');
}
include __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container catalog">
    <aside class="catalog-side" aria-label="Product categories">
      <h2 class="side-title">Categories</h2>
      <ul class="cat-list">
        <li><a href="<?= e(catalog_url(['cat' => ''])) ?>" class="<?= $cat === '' ? 'active' : '' ?>"><i class="fa-solid fa-bottle-droplet" aria-hidden="true"></i> All Products</a></li>
        <?php foreach (CATEGORIES as $name => $icon): ?>
          <li><a href="<?= e(catalog_url(['cat' => $name])) ?>" class="<?= $cat === $name ? 'active' : '' ?>"><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i> <?= e($name) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <div class="catalog-main">
      <form class="catalog-toolbar" method="get" action="<?= e(url('products.php')) ?>">
        <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
        <div class="search-field">
          <label class="sr-only" for="q">Search oils</label>
          <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search oils..." maxlength="60">
          <button type="submit" class="search-btn" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
        <div class="sort-field">
          <label for="sort">Sort by:</label>
          <select id="sort" name="sort" data-autosubmit>
            <?php foreach ($sorts as $key => [$label]): ?>
              <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>

      <p class="muted small result-count"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?><?= $cat !== '' ? ' in ' . e($cat) : '' ?><?= $q !== '' ? ' for &ldquo;' . e($q) . '&rdquo;' : '' ?> &middot; sample prices</p>

      <?php if ($products): ?>
        <div class="product-grid grid-3">
          <?php foreach ($products as $p) { render_product_card($p); } ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <h3>No oils found</h3>
          <p>We couldn't find anything matching your search. Try a different word or clear the filters.</p>
          <a class="btn btn-primary" href="<?= e(url('products.php')) ?>">Show all products</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
