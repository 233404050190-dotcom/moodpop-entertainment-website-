<?php
declare(strict_types=1);

require_once __DIR__ . '/config/reader_auth.php';
require __DIR__ . '/config/database.php';

// Neu da dang nhap thi ve trang chu
if (reader_id() !== null) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nameValue = $_POST['display_name'] ?? '';
    $emailValue = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $name = is_string($nameValue)
        ? trim($nameValue) : '';

    $email = is_string($emailValue)
        ? strtolower(trim($emailValue)) : '';

    // Kiem tra CSRF
    if (!reader_csrf_valid()) {
        $errors[] = 'Biểu mẫu không hợp lệ.';
    }

    // Kiem tra ten
    $nameLength = function_exists('mb_strlen')
        ? mb_strlen($name, 'UTF-8')
        : strlen($name);

    if ($nameLength < 2 || $nameLength > 70) {
        $errors[] = 'Tên phải từ 2 đến 70 ký tự.';
    }

    // Kiem tra email
    if (
        strlen($email) > 190 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = 'Email không hợp lệ.';
    }

    // Kiem tra mat khau
    if (
        !is_string($password) ||
        strlen($password) < 8 ||
        strlen($password) > 72
    ) {
        $errors[] = 'Mật khẩu phải từ 8 đến 72 byte.';
    }

    if (
        !is_string($confirm) ||
        $password !== $confirm
    ) {
        $errors[] = 'Xác nhận mật khẩu không khớp.';
    }

    // Luu vao MySQL
    if (!$errors) {

        try {
            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO users
                    (display_name, email, password_hash, role)
                 VALUES
                    (?, ?, ?, 'user')"
            );

            $stmt->execute([
                $name,
                $email,
                $hash
            ]);

            // Dang ky thanh cong
            header('Location: login.php?registered=1');
            exit;

        } catch (PDOException $ex) {

            if ($ex->getCode() === '23000') {
                $errors[] = 'Email này đã được đăng ký.';
            } else {
                error_log($ex->getMessage());
                $errors[] = 'Không thể tạo tài khoản.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Đăng ký - MoodPop</title>

    <link rel="stylesheet"
          href="assets/css/style.css">
</head>

<body class="reader-auth-page">

<main class="reader-card">

    <a href="index.php">
        <img
            src="assets/image/logo-moodpop.png"
            alt="MoodPop"
            class="reader-logo"
        >
    </a>

    <h1>Tạo tài khoản MoodPop</h1>

    <p class="reader-subtitle">
        Tham gia cộng đồng yêu thích giải trí.
    </p>

    <?php if ($errors): ?>
        <div class="reader-alert-error" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= reader_e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input
            type="hidden"
            name="reader_csrf"
            value="<?= reader_e(reader_csrf_token()) ?>"
        >

        <label for="display_name">Tên hiển thị</label>

        <input
            type="text"
            id="display_name"
            name="display_name"
            maxlength="70"
            value="<?= reader_e($name) ?>"
            autocomplete="name"
            required
        >

        <label for="email">Email</label>

        <input
            type="email"
            id="email"
            name="email"
            maxlength="190"
            value="<?= reader_e($email) ?>"
            autocomplete="email"
            required
        >

        <label for="password">Mật khẩu</label>

        <input
            type="password"
            id="password"
            name="password"
            minlength="8"
            autocomplete="new-password"
            required
        >

        <p class="reader-hint">
            Sử dụng ít nhất 8 ký tự.
        </p>

        <label for="confirm_password">
            Nhập lại mật khẩu
        </label>

        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            autocomplete="new-password"
            required
        >

        <button type="submit" class="reader-btn">
            Đăng ký
        </button>

    </form>

    <p class="reader-footer-text">
        Đã có tài khoản?
        <a href="login.php">Đăng nhập</a>
    </p>

    <p class="reader-footer-text">
        <a href="index.php">← Về trang chủ</a>
    </p>

</main>

</body>
</html>
