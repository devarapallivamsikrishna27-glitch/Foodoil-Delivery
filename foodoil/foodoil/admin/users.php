<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$pdo = db();

if (is_post() && post('action') === 'reset') {
    csrf_require();
    $id = (int)post('id');
    $s = $pdo->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'customer'");
    $s->execute([$id]);
    $u = $s->fetch();
    if ($u) {
        $temp = 'Fo' . bin2hex(random_bytes(4)) . '7';      // letters + digits, 11 characters
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($temp, PASSWORD_DEFAULT), $id]);
        flash('success', 'Temporary password for ' . $u['name'] . ': ' . $temp . '  (share it privately; they can change it after logging in).');
    } else {
        flash('error', 'Only customer passwords can be reset here.');
    }
    redirect('admin/users.php');
}
$users = $pdo->query("SELECT u.id, u.name, u.email, u.phone, u.role, u.created_at,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count
    FROM users u ORDER BY u.role = 'admin' DESC, u.id DESC")->fetchAll();
$adminTitle = 'Users'; $adminActive = 'users';
include __DIR__ . '/_header.php';
?>
<div class="panel">
  <div class="panel-head"><h2>Registered users <span class="muted small">(<?= count($users) ?>)</span></h2></div>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Orders</th><th>Joined</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><b><?= e($u['name']) ?></b></td><td><?= e($u['email']) ?></td><td><?= e($u['phone'] ?? '-') ?></td>
        <td><span class="status <?= $u['role'] === 'admin' ? 'status-confirmed' : 'status-pending' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
        <td><?= (int)$u['orders_count'] ?></td><td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
        <td><?php if ($u['role'] === 'customer'): ?>
          <form method="post" action="<?= e(url('admin/users.php')) ?>" class="inline-form" data-confirm="Reset the password for <?= e($u['name']) ?>? Their old password will stop working.">
            <?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <button class="btn btn-outline btn-sm" type="submit">Reset password</button></form>
        <?php else: ?><span class="muted small">-</span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
