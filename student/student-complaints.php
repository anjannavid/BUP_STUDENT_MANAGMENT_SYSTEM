<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

$rows = db_all('SELECT * FROM complaints WHERE student_id = ? ORDER BY id DESC', 's', [$me['student_id']]);

layout_start('Complaints', 'student', 'student-complaints.php');
?>
<h2>Complaints and feedback</h2>
<form class="panel form" method="post" action="backend/complain_stud.php">
  <?= csrf_field() ?>
  <label class="full" for="c-sub">Subject<input id="c-sub" name="subject" maxlength="150" required></label>
  <label class="full" for="c-body">Details<textarea id="c-body" name="body" required></textarea></label>
  <div><button class="btn" type="submit">Send to management</button></div>
</form>

<div class="tablebox"><table>
  <thead><tr><th>Date</th><th>Subject</th><th>Reply</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= fdate($r['created_at']) ?></td>
      <td><b><?= e($r['subject']) ?></b><br><span class="muted"><?= e($r['body']) ?></span></td>
      <td><?= $r['reply'] ? e($r['reply']) : '<span class="muted">Waiting</span>' ?></td>
      <td><?= pill($r['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="4" class="empty">No complaints sent.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php layout_end();
