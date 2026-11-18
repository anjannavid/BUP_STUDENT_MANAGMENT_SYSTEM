<?php
require_once __DIR__ . '/../includes/layout.php';

if (!empty($_SESSION['student_id'])) {
    redirect('student-dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row = db_one('SELECT student_id, password_hash FROM students WHERE student_id = ?', 's', [post('student_id')]);
    if ($row && password_verify((string)($_POST['password'] ?? ''), $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['student_id'] = $row['student_id'];
        redirect('student-dashboard.php');
    }
    $error = 'Wrong student ID or password.';
}

layout_start('Student sign in', 'student', '', false);
?>
<div class="login">
  <form class="panel" method="post" autocomplete="on">
    <?= csrf_field() ?>
    <div><p class="label">Student portal</p><h2 style="margin-top:6px">Sign in</h2></div>
    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
    <label for="student_id">Student ID<input id="student_id" name="student_id" autocomplete="username" required value="<?= e($_POST['student_id'] ?? '') ?>"></label>
    <label for="password">Password<input id="password" name="password" type="password" autocomplete="current-password" required></label>
    <button class="btn" type="submit">Sign in</button>
    <p class="hint">Demo login: <span class="mono">25549011140 / 12345678</span></p>
  </form>
</div>
<?php layout_end();
