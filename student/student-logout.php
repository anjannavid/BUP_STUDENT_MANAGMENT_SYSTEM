<?php
require_once __DIR__ . '/../includes/helpers.php';
csrf_check();
unset($_SESSION['student_id']);
session_regenerate_id(true);
redirect('student-login.php');
