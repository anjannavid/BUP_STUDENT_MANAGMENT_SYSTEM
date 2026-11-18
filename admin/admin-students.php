<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/get_students.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    if ($action === 'add') {
        $name = post('name'); $dept = post('department'); $batch = post('batch'); $phone = post('phone');
        $sid = post('student_id');
        if ($name === '' || $dept === '' || $batch === '' || $phone === '') {
            flash('Fill in name, department, batch and phone.', 'err');
        } else {
            if ($sid === '') {
                $prefix = 's' . date('Y');
                $max = db_val('SELECT MAX(student_id) FROM students WHERE student_id LIKE ?', 's', [$prefix . '%']);
                $sid = $prefix . str_pad((string)($max ? (int)substr($max, strlen($prefix)) + 1 : 1), 3, '0', STR_PAD_LEFT);
            }
            if (!preg_match('/^[A-Za-z0-9_-]{3,20}$/', $sid)) {
                flash('The student ID can use letters, numbers, dash and underscore (3 to 20 characters).', 'err');
            } else {
                $email = filter_var(post('email'), FILTER_VALIDATE_EMAIL) ?: $sid . '@example.edu';
                try {
                    db_exec('INSERT INTO students (student_id, name, department, batch, phone, email, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)',
                        'sssssss', [$sid, $name, $dept, $batch, $phone, $email, password_hash('student123', PASSWORD_DEFAULT)]);
                    flash("Added $name as $sid. Starting password: student123");
                } catch (mysqli_sql_exception $ex) {
                    if ((int)$ex->getCode() === 1062) {
                        flash('That student ID already exists.', 'err');
                    } else {
                        throw $ex;
                    }
                }
            }
        }
    } elseif ($action === 'reset') {
        db_exec('UPDATE students SET password_hash = ? WHERE student_id = ?', 'ss', [password_hash('student123', PASSWORD_DEFAULT), post('student_id')]);
        flash('Password reset to student123.');
    } elseif ($action === 'delete') {
        db_exec('DELETE FROM students WHERE student_id = ?', 's', [post('student_id')]);
        flash('Resident removed, with their fees, requests and meal records.');
    }
    redirect('admin-students.php');
}

$q = trim((string)($_GET['q'] ?? ''));
$filter = (string)($_GET['filter'] ?? 'all');
if (!in_array($filter, ['all', 'due', 'noroom'], true)) {
    $filter = 'all';
}
$students = get_students($q, $filter);

layout_start('Students', 'admin', 'admin-students.php');
?>
<div class="head"><h2>Student register</h2><span class="muted"><?= count($students) ?> shown</span></div>

<form class="filters" method="get">
  <label for="q" style="flex:2 1 240px">Search<input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="Name, ID, department or room"></label>
  <label for="flt">Show
    <select id="flt" name="filter" data-autosubmit>
      <option value="all"<?= $filter === 'all' ? ' selected' : '' ?>>All residents</option>
      <option value="due"<?= $filter === 'due' ? ' selected' : '' ?>>With unpaid dues</option>
      <option value="noroom"<?= $filter === 'noroom' ? ' selected' : '' ?>>Without a room</option>
    </select></label>
  <div style="align-self:end"><button class="btn" type="submit">Search</button></div>
</form>

<div class="tablebox"><table>
  <thead><tr><th>ID</th><th>Name</th><th>Department</th><th>Room</th><th class="num">Dues</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($students as $s): ?>
    <tr>
      <td class="mono"><?= e($s['student_id']) ?></td>
      <td><b><?= e($s['name']) ?></b><br><span class="muted"><?= e($s['phone']) ?></span></td>
      <td><?= e($s['department']) ?> · <?= e($s['batch']) ?></td>
      <td><?= $s['room_no'] ? e($s['room_no']) : '<span class="pill warn">Unallocated</span>' ?></td>
      <td class="num"><?= (int)$s['dues'] ? money($s['dues']) : '<span class="muted">Clear</span>' ?></td>
      <td><div class="row-actions">
        <form method="post" class="inline" data-confirm="Reset this password to student123?"><?= csrf_field() ?>
          <input type="hidden" name="action" value="reset"><input type="hidden" name="student_id" value="<?= e($s['student_id']) ?>">
          <button class="btn small ghost" type="submit">Reset password</button></form>
        <form method="post" class="inline" data-confirm="Remove <?= e($s['name']) ?> and all their records?"><?= csrf_field() ?>
          <input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="<?= e($s['student_id']) ?>">
          <button class="btn small danger" type="submit">Remove</button></form>
      </div></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$students): ?><tr><td colspan="6" class="empty">No residents match.</td></tr><?php endif; ?>
  </tbody>
</table></div>

<form class="panel form" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div class="full"><h3>Register a new resident</h3></div>
  <label for="s-id">Student ID (optional)<input id="s-id" name="student_id" placeholder="Leave blank to generate"></label>
  <label for="s-name">Full name<input id="s-name" name="name" required></label>
  <label for="s-dept">Department<input id="s-dept" name="department" required></label>
  <label for="s-batch">Batch<input id="s-batch" name="batch" placeholder="Batch 14" required></label>
  <label for="s-phone">Phone<input id="s-phone" name="phone" required></label>
  <label for="s-email">Email (optional)<input id="s-email" name="email" type="email"></label>
  <div><button class="btn" type="submit">Add resident</button></div>
  <p class="muted full" style="font-size:.85rem">New residents get the starting password <span class="mono">student123</span> and can change it on their profile page.</p>
</form>
<?php layout_end();
