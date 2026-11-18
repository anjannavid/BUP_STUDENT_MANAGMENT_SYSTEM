<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

$notices = db_all('SELECT * FROM notices ORDER BY created_at DESC, id DESC');

layout_start('Notices', 'student', 'student-notices.php');
?>
<h2>Notices</h2>
<section class="panel">
  <?php foreach ($notices as $n) { echo notice_card($n); } ?>
  <?php if (!$notices): ?><p class="muted">No notices posted.</p><?php endif; ?>
</section>
<?php layout_end();
