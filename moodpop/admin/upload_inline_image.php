<?php
declare(strict_types=1);

require __DIR__ . '/../config/auth.php';
require_admin();

// Tra ket qua JSON cho JavaScript
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function reply(
    int $status,
    bool $ok,
    string $message,
    ?string $filename = null
): void {

    http_response_code($status);

    $result = ['ok' => $ok];

    if ($ok) {
        $result['filename'] = $filename;
    } else {
        $result['error'] = $message;
    }

    echo json_encode(
        $result,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// Chi nhan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    reply(
        405,
        false,
        'Chỉ nhận yêu cầu POST.'
    );
}

// Kiem tra CSRF
if (!csrf_valid()) {

    reply(
        403,
        false,
        'Phiên biểu mẫu hết hạn hoặc không hợp lệ.'
    );
}

try {

    $file = $_FILES['image'] ?? null;

    if (
        !is_array($file) ||
        ($file['error'] ?? null) !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException(
            'Không nhận được tệp ảnh.'
        );
    }

    $tmp = $file['tmp_name'] ?? null;
    $size = $file['size'] ?? null;

    if (
        !is_string($tmp) ||
        !is_int($size) ||
        !is_uploaded_file($tmp)
    ) {
        throw new RuntimeException(
            'Ảnh không hợp lệ.'
        );
    }

    // Gioi han 2 MB
    if (
        $size < 1 ||
        $size > 2 * 1024 * 1024
    ) {
        throw new RuntimeException(
            'Ảnh phải có dung lượng tối đa 2 MB.'
        );
    }

    // Kiem tra loai anh that
    $mime = (new finfo(FILEINFO_MIME_TYPE))
        ->file($tmp);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    $info = @getimagesize($tmp);

    if (
        !isset($allowed[$mime]) ||
        $info === false ||
        $info[0] < 1 ||
        $info[1] < 1 ||
        $info[0] > 5000 ||
        $info[1] > 5000
    ) {
        throw new RuntimeException(
            'Chỉ hỗ trợ ảnh JPG, PNG hoặc WebP hợp lệ.'
        );
    }

    // Tao thu muc luu anh
    $folder = dirname(__DIR__)
        . '/assets/uploads/posts';

    if (
        !is_dir($folder) &&
        !mkdir($folder, 0755, true) &&
        !is_dir($folder)
    ) {
        throw new RuntimeException(
            'Không tạo được thư mục lưu ảnh.'
        );
    }

    // Tao ten file ngau nhien
    $filename = bin2hex(random_bytes(16))
        . '.' . $allowed[$mime];

    $destination = $folder . '/' . $filename;

    // Luu tep anh
    if (!move_uploaded_file($tmp, $destination)) {

        throw new RuntimeException(
            'Không thể lưu ảnh vào máy chủ.'
        );
    }

    // Tra ten file ve JavaScript
    reply(
        200,
        true,
        '',
        $filename
    );

} catch (RuntimeException $ex) {

    reply(
        400,
        false,
        $ex->getMessage()
    );

} catch (Throwable $ex) {

    error_log($ex->getMessage());

    reply(
        500,
        false,
        'Máy chủ gặp lỗi khi xử lý ảnh.'
    );
}
