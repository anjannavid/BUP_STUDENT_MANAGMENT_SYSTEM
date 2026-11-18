<?php
// Include at the top of every student page. Redirects to the login page when not signed in.
require_once __DIR__ . '/../../includes/helpers.php';

$in_backend = basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'backend';
$login_url = ($in_backend ? '../' : '') . 'student-login.php';

if (empty($_SESSION['student_id'])) {
    redirect($login_url);
}

$me = db_one('SELECT * FROM students WHERE student_id = ?', 's', [$_SESSION['student_id']]);
if (!$me) {
    unset($_SESSION['student_id']);
    redirect($login_url);
}
