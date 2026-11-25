<?php
declare(strict_types=1);

require_once __DIR__ . '/config/reader_auth.php';
require __DIR__ . '/config/database.php';

// Chi chap nhan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Phương thức không được hỗ trợ.');
}

// Bat buoc User dang nhap
$userId = reader_id();

if ($userId === null) {
    header('Location: login.php');
    exit;
}

// Kiem tra CSRF
if (!reader_csrf_valid()) {
    http_response_code(403);
    exit('Phiên biểu mẫu không hợp lệ.');
}

// Lay du lieu an toan
$postId = filter_var(
    $_POST['post_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$content = $_POST['content'] ?? '';

if (
    !is_int($postId) ||
    !is_string($content)
) {
    http_response_code(400);
    exit('Dữ liệu bình luận không hợp lệ.');
}

$content = trim($content);

// Kiem tra bai viet da xuat ban
$stmt = $pdo->prepare(
    "SELECT id, slug
     FROM posts
     WHERE id = ?
       AND status = 'published'
     LIMIT 1"
);

$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    exit('Không tìm thấy bài viết.');
}

// URL quay ve bai viet
$articleUrl = 'post.php?slug='
    . rawurlencode((string) $post['slug']);

function redirectComment(
    string $url,
    string $result
): void {
    header(
        'Location: ' . $url
        . '&comment=' . $result
        . '#binh-luan'
    );
    exit;
}

// Kiem tra do dai
$length = function_exists('mb_strlen')
    ? mb_strlen($content, 'UTF-8')
    : strlen($content);

if ($length < 5 || $length > 1000) {
    redirectComment($articleUrl, 'invalid');
}

// Gioi han tan suat binh luan
$now = time();

$lastComment = (int) (
    $_SESSION['reader_last_comment'] ?? 0
);

if ($now - $lastComment < 15) {
    redirectComment($articleUrl, 'slow');
}

// Kiem tra tai khoan con hop le
$userStmt = $pdo->prepare(
    "SELECT id
     FROM users
     WHERE id = ?
       AND role = 'user'
     LIMIT 1"
);

$userStmt->execute([$userId]);

if (!$userStmt->fetch()) {
    unset(
        $_SESSION['reader_id'],
        $_SESSION['reader_name']
    );

    header('Location: login.php');
    exit;
}

// Luu binh luan cho duyet
$insert = $pdo->prepare(
    "INSERT INTO comments
        (post_id, user_id, content, status)
     VALUES
        (?, ?, ?, 'pending')"
);

$insert->execute([
    $postId,
    $userId,
    $content
]);

$_SESSION['reader_last_comment'] = $now;

// Quay lai bai viet
redirectComment($articleUrl, 'pending');
