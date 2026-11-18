<?php
// Include at the top of every admin page. Redirects to the login page when not signed in.
require_once __DIR__ . '/../../includes/helpers.php';

if (empty($_SESSION['admin_id'])) {
    redirect('admin-login.php');
}
$me = ['name' => 'Provost office'];
