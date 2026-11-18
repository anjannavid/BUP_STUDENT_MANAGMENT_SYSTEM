<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

const MAINT_CATEGORIES = ['Electrical', 'Plumbing', 'Furniture', 'Cleaning', 'Internet', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $cat = post('category');
        $desc = post('description');
        if (!in_array($cat, MAINT_CATEGORIES, true) || $desc === '') {
            throw new RuntimeException('Choose a category and describe the problem.');
        }
        $img = save_upload('image', 'maintenance');
        db_exec('INSERT INTO maintenance_requests (student_id, category, description, image_path) VALUES (?, ?, ?, ?)',
            'ssss', [$me['student_id'], $cat, $desc, $img]);
        flash('Request submitted.');
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
    }
    redirect('student-maintenance.php');
}

$rows = db_all('SELECT * FROM maintenance_requests WHERE student_id = ? ORDER BY id DESC', 's', [$me['student_id']]);

layout_start('Maintenance', 'student', 'student-maintenance.php');
?>
<h2>Maintenance requests</h2>
<form class="panel form" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <label for="m-cat">Category
    <select id="m-cat" name="category">
      <?php foreach (MAINT_CATEGORIES as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
    </select></label>
  <label for="m-img">Photo (optional, JPG, PNG or WebP, up to 2 MB)<input id="m-img" name="image" type="file" accept="image/jpeg,image/png,image/webp"></label>
  <label class="full" for="m-desc">What needs fixing<textarea id="m-desc" name="description" required placeholder="Describe the problem and where it is"></textarea></label>
  <div><button class="btn" type="submit">Submit request</button></div>
</form>

<div class="tablebox"><table>
  <thead><tr><th>Date</th><th>Category</th><th>Details</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= fdate($r['created_at']) ?></td>
      <td><?= e($r['category']) ?></td>
      <td><?= e($r['description']) ?>
        <?php if ($r['image_path']): ?><br><a href="../<?= e($r['image_path']) ?>" target="_blank" rel="noopener">View photo</a><?php endif; ?></td>
      <td><?= pill($r['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="4" class="empty">No requests yet.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php layout_end();
