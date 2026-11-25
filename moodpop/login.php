<?php
declare(strict_types=1);

require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/database.php';

// Da dang nhap thi chuyen den Dashboard
if (admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Kiem tra CSRF
    if (!csrf_valid()) {
        $error = 'Phiên biểu mẫu không hợp lệ. Hãy tải lại trang và thử lại.';
    } else {

        $emailInput = $_POST['email'] ?? '';
        $passwordInput = $_POST['password'] ?? '';

        $email = is_string($emailInput)
            ? strtolower(trim($emailInput))
            : '';

        $password = is_string($passwordInput)
            ? $passwordInput
            : '';

        // Kiem tra du lieu dau vao
        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 190
            || $password === ''
            || strlen($password) > 4096
        ) {
            $error = 'Email hoặc mật khẩu không đúng.';

        } else {

            // Lay IP truc tiep tu server
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

            if (
                !is_string($ipAddress)
                || filter_var($ipAddress, FILTER_VALIDATE_IP) === false
            ) {
                $ipAddress = 'unknown';
            }

            // Don cac ban ghi cu hon 30 ngay
            $pdo->exec(
                'DELETE FROM admin_login_attempts
                 WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'
            );

            // Dem so lan dang nhap sai trong 15 phut
            $limitStmt = $pdo->prepare(
                'SELECT
                    SUM(
                        CASE
                            WHEN email = :email_count THEN 1
                            ELSE 0
                        END
                    ) AS email_failures,

                    SUM(
                        CASE
                            WHEN ip_address = :ip_count THEN 1
                            ELSE 0
                        END
                    ) AS ip_failures

                 FROM admin_login_attempts

                 WHERE attempted_at >=
                    DATE_SUB(NOW(), INTERVAL 15 MINUTE)

                   AND (
                       email = :email_filter
                       OR ip_address = :ip_filter
                   )'
            );

            $limitStmt->execute([
                'email_count' => $email,
                'ip_count' => $ipAddress,
                'email_filter' => $email,
                'ip_filter' => $ipAddress
            ]);

            $limits = $limitStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $failedByEmail = (int) (
                $limits['email_failures'] ?? 0
            );

            $failedByIp = (int) (
                $limits['ip_failures'] ?? 0
            );

            // Gioi han 5 lan/email va 20 lan/IP
            if (
                $failedByEmail >= 5
                || $failedByIp >= 20
            ) {
                $error = 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau 15 phút.';

            } else {

                // Tim tai khoan
                $stmt = $pdo->prepare(
                    'SELECT
                        id,
                        display_name,
                        password_hash,
                        role
                     FROM users
                     WHERE email = ?
                     LIMIT 1'
                );

                $stmt->execute([$email]);

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Xac thuc tai khoan va quyen Admin
                if (
                    $user
                    && password_verify(
                        $password,
                        $user['password_hash']
                    )
                    && ($user['role'] ?? '') === 'admin'
                ) {

                    // Xoa lich su dang nhap sai cua email nay
                    $clearAttempts = $pdo->prepare(
                        'DELETE FROM admin_login_attempts
                         WHERE email = ?'
                    );

                    $clearAttempts->execute([$email]);

                    // Chong Session Fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['display_name'] =
                        $user['display_name'];
                    $_SESSION['role'] = $user['role'];

                    // Tao CSRF token moi
                    $_SESSION['csrf_token'] =
                        bin2hex(random_bytes(32));

                    header('Location: dashboard.php');
                    exit;

                } else {

                    // Ghi nhan lan dang nhap that bai
                    $recordAttempt = $pdo->prepare(
                        'INSERT INTO admin_login_attempts
                            (email, ip_address)
                         VALUES (?, ?)'
                    );

                    $recordAttempt->execute([
                        $email,
                        $ipAddress
                    ]);

                    // Khong tiet lo email co ton tai hay khong
                    $error = 'Email hoặc mật khẩu không đúng.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Đăng nhập quản trị - MoodPop</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >
</head>

<body class="auth-page">

<main class="auth-card">

    <img
        src="../assets/image/logo-moodpop.png"
        alt="MoodPop"
        class="auth-logo"
    >

    <h1>Chào mừng trở lại!</h1>

    <p class="subtitle">
        Đăng nhập để quản lý nội dung MoodPop.
    </p>

    <?php if ($error !== ''): ?>
        <div class="alert-error" role="alert">
            <?= htmlspecialchars(
                $error,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                csrf_token(),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ) ?>"
        >

        <label for="email">Email quản trị</label>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="Nhập email"
            value="<?= htmlspecialchars(
                $email,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ) ?>"
            autocomplete="username"
            maxlength="190"
            required
        >

        <label for="password">Mật khẩu</label>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Nhập mật khẩu"
            autocomplete="current-password"
            required
        >

        <button type="submit" class="btn-primary">
            Đăng nhập
        </button>

    </form>

    <a href="../index.php" class="back-link">
        Quay lại trang chủ MoodPop
    </a>

</main>

</body>
</html>
