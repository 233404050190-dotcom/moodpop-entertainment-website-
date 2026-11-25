<?php
declare(strict_types=1);

require __DIR__ . '/../config/auth.php';

// Chi chap nhan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Phuong thuc khong duoc phep.');
}

// Kiem tra CSRF
if (!csrf_valid()) {
    http_response_code(403);
    exit('Yeu cau khong hop le.');
}

// Xoa thong tin session
$_SESSION = [];

// Xoa cookie session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite']
    ]);
}

session_destroy();

// Chuyen ve trang dang nhap
header('Location: login.php');
exit;
