<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/../includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    if ($action === 'profile') {
        $phone = post('phone');
        $email = post('email');
        if ($phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Enter a phone number and a valid email address.', 'err');
        } else {
            db_exec('UPDATE students SET phone = ?, email = ? WHERE student_id = ?', 'sss', [$phone, $email, $me['student_id']]);
            flash('Profile saved.');
        }
    } elseif ($action === 'password') {
        $new = (string)($_POST['new_password'] ?? '');
        if (!password_verify((string)($_POST['current_password'] ?? ''), $me['password_hash'])) {
            flash('Your current password is wrong.', 'err');
        } elseif (strlen($new) < 8) {
            flash('The new password must be at least 8 characters.', 'err');
        } elseif ($new !== (string)($_POST['confirm_password'] ?? '')) {
            flash('The two new passwords do not match.', 'err');
        } else {
            db_exec('UPDATE students SET password_hash = ? WHERE student_id = ?', 'ss', [password_hash($new, PASSWORD_DEFAULT), $me['student_id']]);
            flash('Password changed.');
        }
    }
    redirect('student-profile.php');
}

layout_start('Profile', 'student', 'student-profile.php');
?>
<h2>Profile</h2>
<form class="panel form" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="profile">
  <label for="p-name">Name<input id="p-name" value="<?= e($me['name']) ?>" disabled></label>
  <label for="p-dept">Department<input id="p-dept" value="<?= e($me['department'] . ' · ' . $me['batch']) ?>" disabled></label>
  <label for="p-room">Room<input id="p-room" value="<?= e($me['room_no'] ?: 'Not allocated') ?>" disabled></label>
  <label for="p-phone">Phone<input id="p-phone" name="phone" value="<?= e($me['phone']) ?>" required></label>
  <label for="p-email">Email<input id="p-email" name="email" type="email" value="<?= e($me['email']) ?>" required></label>
  <div><button class="btn" type="submit">Save changes</button></div>
</form>

<form class="panel form" method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="password">
  <div class="full"><h3>Change password</h3></div>
  <label for="cp">Current password<input id="cp" name="current_password" type="password" autocomplete="current-password" required></label>
  <label for="np">New password<input id="np" name="new_password" type="password" minlength="8" autocomplete="new-password" required></label>
  <label for="cf">Repeat new password<input id="cf" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required></label>
  <div><button class="btn" type="submit">Change password</button></div>
</form>
<?php layout_end();
