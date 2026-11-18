<?php
require_once __DIR__ . '/../includes/layout.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('admin-dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row = db_one('SELECT id, password_hash FROM admins WHERE username = ?', 's', [post('username')]);
    if ($row && password_verify((string)($_POST['password'] ?? ''), $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$row['id'];
        redirect('admin-dashboard.php');
    }
    $error = 'Wrong username or password.';
}

layout_start('Provost office sign in', 'admin', '', false);
?>
<div class="login">
  <form class="panel" method="post">
    <?= csrf_field() ?>
    <div><p class="label">Provost office</p><h2 style="margin-top:6px">Sign in</h2></div>
    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
    <label for="username">Username<input id="username" name="username" autocomplete="username" required></label>
    <label for="password">Password<input id="password" name="password" type="password" autocomplete="current-password" required></label>
    <button class="btn" type="submit">Sign in</button>
    <p class="hint">Demo login: <span class="mono">admin / admin123</span></p>
  </form>
</div>
<?php layout_end();
