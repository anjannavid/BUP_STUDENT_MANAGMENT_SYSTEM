<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $status = post('status');
    if (in_array($status, ['Pending', 'In progress', 'Resolved'], true)) {
        db_exec('UPDATE maintenance_requests SET status = ? WHERE id = ?', 'si', [$status, (int)post('id')]);
        flash("Status set to $status.");
    }
    redirect('admin-maintenance.php');
}

$rows = db_all('SELECT m.*, s.name, s.room_no FROM maintenance_requests m JOIN students s ON s.student_id = m.student_id
                ORDER BY m.status = \'Resolved\', m.id DESC');

layout_start('Maintenance', 'admin', 'admin-maintenance.php');
?>
<h2>Maintenance requests</h2>
<div class="tablebox"><table>
  <thead><tr><th>Date</th><th>Resident</th><th>Room</th><th>Request</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $m): ?>
    <tr>
      <td><?= fdate($m['created_at']) ?></td>
      <td><?= e($m['name']) ?></td>
      <td><?= e($m['room_no'] ?: '–') ?></td>
      <td><b><?= e($m['category']) ?></b><br><span class="muted"><?= e($m['description']) ?></span>
        <?php if ($m['image_path']): ?><br><a href="../<?= e($m['image_path']) ?>" target="_blank" rel="noopener">View photo</a><?php endif; ?></td>
      <td>
        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
          <label for="ms-<?= (int)$m['id'] ?>" style="font-weight:500"><span class="muted" style="font-size:.7rem">Update status</span>
            <select id="ms-<?= (int)$m['id'] ?>" name="status" data-autosubmit>
              <?php foreach (['Pending', 'In progress', 'Resolved'] as $st): ?>
                <option<?= $st === $m['status'] ? ' selected' : '' ?>><?= e($st) ?></option>
              <?php endforeach; ?>
            </select></label>
          <noscript><button class="btn small" type="submit">Save</button></noscript>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="5" class="empty">No requests.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php layout_end();
