<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $status = post('status');
    if (in_array($status, ['Approved', 'Rejected'], true)) {
        db_exec("UPDATE guests SET status = ? WHERE id = ? AND status = 'Pending'", 'si', [$status, (int)post('id')]);
        flash('Guest request ' . strtolower($status) . '.');
    }
    redirect('admin-guests.php');
}

$rows = db_all('SELECT g.*, s.name FROM guests g JOIN students s ON s.student_id = g.student_id
                ORDER BY g.status = \'Pending\' DESC, g.visit_date DESC, g.id DESC');

layout_start('Guests', 'admin', 'admin-guests.php');
?>
<h2>Guest requests</h2>
<div class="tablebox"><table>
  <thead><tr><th>Visit date</th><th>Resident</th><th>Guest</th><th>Relation</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $g): ?>
    <tr>
      <td><?= fdate($g['visit_date']) ?></td>
      <td><?= e($g['name']) ?></td>
      <td><?= e($g['guest_name']) ?>
        <?php if ($g['id_proof_path']): ?><br><a href="../<?= e($g['id_proof_path']) ?>" target="_blank" rel="noopener">View ID photo</a><?php endif; ?></td>
      <td><?= e($g['relation']) ?></td>
      <td><?= pill($g['status']) ?></td>
      <td>
        <?php if ($g['status'] === 'Pending'): ?>
        <div class="row-actions">
          <?php foreach (['Approved' => 'Approve', 'Rejected' => 'Reject'] as $val => $label): ?>
            <form method="post" class="inline"><?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$g['id'] ?>"><input type="hidden" name="status" value="<?= $val ?>">
              <button class="btn small<?= $val === 'Rejected' ? ' danger' : '' ?>" type="submit"><?= $label ?></button></form>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="empty">No guest requests.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php layout_end();
