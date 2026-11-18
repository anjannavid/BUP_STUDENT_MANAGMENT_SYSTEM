<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $idx = (int)post('day_index');
    if ($idx >= 0 && $idx <= 6 && post('breakfast') !== '' && post('lunch') !== '' && post('dinner') !== '') {
        db_exec('UPDATE mess_menu SET breakfast = ?, lunch = ?, dinner = ? WHERE day_index = ?', 'sssi',
            [mb_substr(post('breakfast'), 0, 200), mb_substr(post('lunch'), 0, 200), mb_substr(post('dinner'), 0, 200), $idx]);
        flash('Menu saved.');
    } else {
        flash('Fill in all three meals.', 'err');
    }
    redirect('admin-mess.php');
}

$menu = db_all('SELECT * FROM mess_menu ORDER BY day_index');
$fb = db_all('SELECT f.*, s.name FROM mess_feedback f JOIN students s ON s.student_id = f.student_id ORDER BY f.updated_at DESC');
$avg = (float)db_val('SELECT COALESCE(AVG(rating),0) FROM mess_feedback');

layout_start('Mess', 'admin', 'admin-mess.php');
?>
<h2>Mess menu and feedback</h2>
<p class="muted">Average rating <b class="mono"><?= number_format($avg, 1) ?> / 5</b> from <?= count($fb) ?> residents.</p>

<div class="panel">
  <h3>Weekly menu</h3>
  <?php foreach ($menu as $m): $i = (int)$m['day_index']; ?>
    <form class="form" method="post" style="padding:12px 0;border-top:1px solid var(--line)">
      <?= csrf_field() ?><input type="hidden" name="day_index" value="<?= $i ?>">
      <label for="mb<?= $i ?>"><?= e($m['day_name']) ?> breakfast<input id="mb<?= $i ?>" name="breakfast" value="<?= e($m['breakfast']) ?>" required></label>
      <label for="ml<?= $i ?>">Lunch<input id="ml<?= $i ?>" name="lunch" value="<?= e($m['lunch']) ?>" required></label>
      <label for="md<?= $i ?>">Dinner<input id="md<?= $i ?>" name="dinner" value="<?= e($m['dinner']) ?>" required></label>
      <div><button class="btn small ghost" type="submit">Save <?= e($m['day_name']) ?></button></div>
    </form>
  <?php endforeach; ?>
</div>

<div class="tablebox"><table>
  <thead><tr><th>Resident</th><th>Rating</th><th>Comment</th></tr></thead>
  <tbody>
  <?php foreach ($fb as $f): ?>
    <tr><td><?= e($f['name']) ?></td><td class="mono"><?= (int)$f['rating'] ?> / 5</td><td><?= $f['comment'] !== '' ? e($f['comment']) : '<span class="muted">No comment</span>' ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$fb): ?><tr><td colspan="3" class="empty">No feedback yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php layout_end();
