<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

const NOTICE_CATEGORIES = ['General', 'Academic', 'Fees', 'Maintenance', 'Mess'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'add') {
        $cat = in_array(post('category'), NOTICE_CATEGORIES, true) ? post('category') : 'General';
        if (post('title') === '' || post('body') === '') {
            flash('Enter a title and a message.', 'err');
        } else {
            db_exec('INSERT INTO notices (title, body, category) VALUES (?, ?, ?)', 'sss', [mb_substr(post('title'), 0, 150), post('body'), $cat]);
            flash('Notice posted.');
        }
    } elseif (post('action') === 'delete') {
        db_exec('DELETE FROM notices WHERE id = ?', 'i', [(int)post('id')]);
        flash('Notice deleted.');
    }
    redirect('admin-notices.php');
}

$notices = db_all('SELECT * FROM notices ORDER BY created_at DESC, id DESC');

layout_start('Notices', 'admin', 'admin-notices.php');
?>
<h2>Notices</h2>
<form class="panel form" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <label for="n-t">Title<input id="n-t" name="title" maxlength="150" required></label>
  <label for="n-c">Category
    <select id="n-c" name="category"><?php foreach (NOTICE_CATEGORIES as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></label>
  <label class="full" for="n-b">Message<textarea id="n-b" name="body" required></textarea></label>
  <div><button class="btn" type="submit">Post notice</button></div>
</form>

<div class="tablebox"><table>
  <thead><tr><th>Date</th><th>Category</th><th>Title</th><th>Message</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($notices as $n): ?>
    <tr>
      <td><?= fdate($n['created_at']) ?></td>
      <td><?= e($n['category']) ?></td>
      <td><b><?= e($n['title']) ?></b></td>
      <td><?= e($n['body']) ?></td>
      <td><form method="post" data-confirm="Delete this notice?"><?= csrf_field() ?>
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
        <button class="btn small danger" type="submit">Delete</button></form></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$notices): ?><tr><td colspan="5" class="empty">No notices.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php layout_end();
