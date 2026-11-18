<?php
// Saves a complaint from student-complaints.php, then returns to that page.
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../student-complaints.php');
}
csrf_check();

$subject = mb_substr(post('subject'), 0, 150);
$body = post('body');
if ($subject === '' || $body === '') {
    flash('Enter a subject and the details of your complaint.', 'err');
} else {
    db_exec('INSERT INTO complaints (student_id, subject, body) VALUES (?, ?, ?)', 'sss', [$me['student_id'], $subject, $body]);
    flash('Sent to management.');
}
redirect('../student-complaints.php');
