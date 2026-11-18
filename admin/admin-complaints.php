<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $reply = post('reply');
    if ($reply === '') {
        flash('Write a reply first.', 'err');
    } else {
        db_exec("UPDATE complaints SET reply = ?, status = 'Replied' WHERE id = ?", 'si', [$reply, (int)post('id')]);
        flash('Reply sent.');
    }
    redirect('admin-complaints.php');
}

$rows = db_all('SELECT c.*, s.name FROM complaints c JOIN students s ON s.student_id = c.student_id
                ORDER BY c.status = \'Replied\', c.id DESC');

layout_start('Complaints', 'admin', 'admin-complaints.php');
?>
<h2>Complaints and feedback</h2>
<?php foreach ($rows as $c): ?>
  <section class="panel">
    <div class="meta" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:4px">
      <?= pill($c['status']) ?><span class="muted mono"><?= fdate($c['created_at']) ?> · <?= e($c['name']) ?></span>
    </div>
    <h3><?= e($c['subject']) ?></h3>
    <p class="muted"><?= e($c['body']) ?></p>
    <form class="form" method="post" style="margin-top:12px">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
      <label class="full" for="rp-<?= (int)$c['id'] ?>">Reply<textarea id="rp-<?= (int)$c['id'] ?>" name="reply" required><?= e($c['reply']) ?></textarea></label>
      <div><button class="btn small" type="submit"><?= $c['reply'] ? 'Update reply' : 'Send reply' ?></button></div>
    </form>
  </section>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="panel empty">No complaints.</div><?php endif; ?>
<?php layout_end();
