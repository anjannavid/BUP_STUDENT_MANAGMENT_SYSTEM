<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $name = post('guest_name');
        $rel = post('relation');
        $date = post('visit_date');
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($name === '' || $rel === '' || !$d || $d->format('Y-m-d') !== $date) {
            throw new RuntimeException('Enter the guest name, relation and a valid visit date.');
        }
        if ($date < today()) {
            throw new RuntimeException('The visit date cannot be in the past.');
        }
        $img = save_upload('id_proof', 'guest_ids');
        db_exec('INSERT INTO guests (student_id, guest_name, relation, visit_date, id_proof_path) VALUES (?, ?, ?, ?, ?)',
            'sssss', [$me['student_id'], $name, $rel, $date, $img]);
        flash('Guest request sent for approval.');
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
    }
    redirect('student-guests.php');
}

$rows = db_all('SELECT * FROM guests WHERE student_id = ? ORDER BY visit_date DESC, id DESC', 's', [$me['student_id']]);

layout_start('Guests', 'student', 'student-guests.php');
?>
<h2>Guest log</h2>
<form class="panel form" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <label for="g-name">Guest name<input id="g-name" name="guest_name" required></label>
  <label for="g-rel">Relation<input id="g-rel" name="relation" required placeholder="Parent, sibling, relative"></label>
  <label for="g-date">Visit date<input id="g-date" name="visit_date" type="date" min="<?= today() ?>" value="<?= today() ?>" required></label>
  <label for="g-id">Guest ID photo (optional, up to 2 MB)<input id="g-id" name="id_proof" type="file" accept="image/jpeg,image/png,image/webp"></label>
  <div><button class="btn" type="submit">Request approval</button></div>
</form>

<div class="tablebox"><table>
  <thead><tr><th>Visit date</th><th>Guest</th><th>Relation</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= fdate($r['visit_date']) ?></td>
      <td><?= e($r['guest_name']) ?></td>
      <td><?= e($r['relation']) ?></td>
      <td><?= pill($r['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="4" class="empty">No guest visits yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php layout_end();
