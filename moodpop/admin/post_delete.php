<?php
declare(strict_types=1);

require __DIR__ . '/../config/auth.php';
require_admin();

require __DIR__ . '/../config/database.php';

// Chi chap nhan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Phuong thuc khong hop le.');
}

// Chong CSRF
if (!csrf_valid()) {
    http_response_code(403);
    exit('Yeu cau khong hop le.');
}

// Kiem tra ID
$id = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false) {
    http_response_code(400);
    exit('ID bai viet khong hop le.');
}

// Xoa bai viet
$stmt = $pdo->prepare(
    'DELETE FROM posts WHERE id = ?'
);

$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    exit('Bai viet khong ton tai.');
}

header('Location: posts.php?notice=deleted');
exit;
