<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$msgs = db()->query('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 100')->fetchAll();
$adminTitle = 'Messages'; $adminActive = 'messages';
include __DIR__ . '/_header.php';
?>
<div class="panel">
  <div class="panel-head"><h2>Contact messages <span class="muted small">(<?= count($msgs) ?>)</span></h2></div>
  <?php if ($msgs): ?>
    <ul class="message-list">
      <?php foreach ($msgs as $m): ?>
        <li><div class="msg-head"><b><?= e($m['name']) ?></b> <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a><?= $m['phone'] ? ' &middot; ' . e($m['phone']) : '' ?>
            <span class="muted small"><?= e(date('d M Y, h:i A', strtotime($m['created_at']))) ?></span></div>
          <p><?= nl2br(e($m['message'])) ?></p></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?><p class="muted pad">No messages yet. Messages sent from the Contact page appear here.</p><?php endif; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
