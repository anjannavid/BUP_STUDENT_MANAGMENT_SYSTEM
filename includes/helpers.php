<?php
// Shared helpers: session, escaping, CSRF, flash messages, small DB wrappers, uploads.
date_default_timezone_set('Asia/Dhaka');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/../db_connection.php';

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function today(): string
{
    return date('Y-m-d');
}

function money($n): string
{
    return '৳' . number_format((float)$n);
}

function fdate(?string $d): string
{
    return $d ? date('j M Y', strtotime($d)) : '';
}

function post(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $ok = $_SERVER['REQUEST_METHOD'] === 'POST'
        && hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''));
    if (!$ok) {
        http_response_code(400);
        exit('Invalid request. Go back, refresh the page and try again.');
    }
}

/* ---------- flash messages ---------- */
function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function flash_render(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        $out .= '<div class="flash ' . ($type === 'err' ? 'err' : '') . '" role="status">' . e($msg) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---------- database wrappers (prepared statements) ---------- */
function db_all(string $sql, string $types = '', array $params = []): array
{
    global $conn;
    $st = $conn->prepare($sql);
    if ($types !== '') {
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
}

function db_one(string $sql, string $types = '', array $params = []): ?array
{
    $rows = db_all($sql, $types, $params);
    return $rows[0] ?? null;
}

function db_val(string $sql, string $types = '', array $params = [])
{
    $row = db_one($sql, $types, $params);
    return $row ? array_values($row)[0] : null;
}

function db_exec(string $sql, string $types = '', array $params = []): int
{
    global $conn;
    $st = $conn->prepare($sql);
    if ($types !== '') {
        $st->bind_param($types, ...$params);
    }
    $st->execute();
    $n = $st->affected_rows;
    $st->close();
    return $n;
}

/* ---------- display ---------- */
function pill(string $status): string
{
    $map = [
        'Pending' => 'warn', 'In progress' => 'info', 'Resolved' => 'good',
        'Approved' => 'good', 'Rejected' => 'bad', 'Open' => 'warn',
        'Replied' => 'good', 'Paid' => 'good', 'Due' => 'bad',
    ];
    return '<span class="pill ' . ($map[$status] ?? 'info') . '">' . e($status) . '</span>';
}

/* ---------- image uploads (JPG, PNG, WebP up to 2 MB) ---------- */
function save_upload(string $field, string $subdir): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The file could not be uploaded.');
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('The image must be 2 MB or smaller.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) {
        throw new RuntimeException('Only JPG, PNG or WebP images are allowed.');
    }
    $dir = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('The file could not be saved.');
    }
    return 'uploads/' . $subdir . '/' . $name;
}
