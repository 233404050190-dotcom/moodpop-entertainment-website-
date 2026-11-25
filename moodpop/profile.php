<?php
declare(strict_types=1);

require_once __DIR__ . '/config/reader_auth.php';
require __DIR__ . '/config/database.php';

// =====================================
// KIEM TRA DANG NHAP
// =====================================

$userId = reader_id();

if ($userId === null) {
    header('Location: login.php');
    exit;
}

// =====================================
// LAY THONG TIN NGUOI DUNG
// =====================================

$stmt = $pdo->prepare(
    "SELECT
        id,
        display_name,
        email,
        password_hash
     FROM users
     WHERE id = ?
       AND role = 'user'
     LIMIT 1"
);

$stmt->execute([$userId]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {

    unset(
        $_SESSION['reader_id'],
        $_SESSION['reader_name']
    );

    header('Location: login.php');
    exit;
}

// =====================================
// KHOI TAO CAC BIEN
// =====================================

$errors = [];
$success = false;

$name = (string) $user['display_name'];
$email = (string) $user['email'];

// Khoi tao truoc de khong bi loi
// Possible undefined variable

$changePassword = false;
$emailChanged = false;

$currentPassword = '';
$newPassword = '';
$confirmPassword = '';

// =====================================
// XU LY CAP NHAT TAI KHOAN
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------------------------------
    // LAY DU LIEU TU FORM
    // ---------------------------------

    $rawName = $_POST['display_name'] ?? '';
    $rawEmail = $_POST['email'] ?? '';

    $rawCurrentPassword =
        $_POST['current_password'] ?? '';

    $rawNewPassword =
        $_POST['new_password'] ?? '';

    $rawConfirmPassword =
        $_POST['confirm_password'] ?? '';

    $name = is_string($rawName)
        ? trim($rawName)
        : '';

    $email = is_string($rawEmail)
        ? strtolower(trim($rawEmail))
        : '';

    // ---------------------------------
    // KIEM TRA CSRF
    // ---------------------------------

    if (!reader_csrf_valid()) {

        $errors[] =
            'Phiên biểu mẫu không hợp lệ. '
            . 'Vui lòng tải lại trang.';
    }

    // ---------------------------------
    // KIEM TRA TEN HIEN THI
    // ---------------------------------

    $nameLength = function_exists('mb_strlen')
        ? mb_strlen($name, 'UTF-8')
        : strlen($name);

    if (
        $nameLength < 2 ||
        $nameLength > 70
    ) {

        $errors[] =
            'Tên hiển thị phải từ 2 đến 70 ký tự.';
    }

    // ---------------------------------
    // KIEM TRA EMAIL
    // ---------------------------------

    if (
        strlen($email) > 190 ||
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] = 'Email không hợp lệ.';
    }

    // ---------------------------------
    // KIEM TRA DU LIEU MAT KHAU
    // ---------------------------------

    if (
        !is_string($rawCurrentPassword) ||
        !is_string($rawNewPassword) ||
        !is_string($rawConfirmPassword)
    ) {

        $errors[] =
            'Dữ liệu mật khẩu không hợp lệ.';

    } else {

        $currentPassword = $rawCurrentPassword;
        $newPassword = $rawNewPassword;
        $confirmPassword = $rawConfirmPassword;

        // Kiem tra email co thay doi khong

        $emailChanged = (
            $email !== strtolower(
                (string) $user['email']
            )
        );

        // Kiem tra co muon doi mat khau khong

        $changePassword = (
            $newPassword !== ''
        );

        // ---------------------------------
        // DOI EMAIL HOAC MAT KHAU
        // PHAI XAC NHAN MAT KHAU CU
        // ---------------------------------

        if ($emailChanged || $changePassword) {

            if (
                !password_verify(
                    $currentPassword,
                    (string) $user['password_hash']
                )
            ) {

                $errors[] =
                    'Mật khẩu hiện tại không đúng.';
            }
        }

        // ---------------------------------
        // KIEM TRA MAT KHAU MOI
        // ---------------------------------

        if ($changePassword) {

            if (
                strlen($newPassword) < 8 ||
                strlen($newPassword) > 72
            ) {

                $errors[] =
                    'Mật khẩu mới phải từ 8 đến 72 byte.';
            }

            if (
                $newPassword !== $confirmPassword
            ) {

                $errors[] =
                    'Nhập lại mật khẩu mới không khớp.';
            }

        } elseif ($confirmPassword !== '') {

            $errors[] =
                'Hãy nhập mật khẩu mới '
                . 'trước khi xác nhận.';
        }
    }

    // =====================================
    // CAP NHAT DATABASE
    // =====================================

    if (!$errors) {

        try {

            // ---------------------------------
            // TRUONG HOP CO DOI MAT KHAU
            // ---------------------------------

            if ($changePassword) {

                $newHash = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

                $update = $pdo->prepare(
                    "UPDATE users
                     SET
                        display_name = ?,
                        email = ?,
                        password_hash = ?
                     WHERE id = ?
                       AND role = 'user'"
                );

                $update->execute([
                    $name,
                    $email,
                    $newHash,
                    $userId
                ]);

                // Cap nhat hash trong bien user

                $user['password_hash'] = $newHash;

            } else {

                // ---------------------------------
                // CHI CAP NHAT TEN / EMAIL
                // ---------------------------------

                $update = $pdo->prepare(
                    "UPDATE users
                     SET
                        display_name = ?,
                        email = ?
                     WHERE id = ?
                       AND role = 'user'"
                );

                $update->execute([
                    $name,
                    $email,
                    $userId
                ]);
            }

            // ---------------------------------
            // CAP NHAT THONG TIN SESSION
            // ---------------------------------

            $_SESSION['reader_name'] = $name;

            // ---------------------------------
            // CAP NHAT DU LIEU HIEN THI
            // ---------------------------------

            $user['display_name'] = $name;
            $user['email'] = $email;

            $success = true;

        } catch (PDOException $ex) {

            // Email trung voi tai khoan khac

            if (
                (string) $ex->getCode() === '23000'
            ) {

                $errors[] =
                    'Email này đã được sử dụng.';

            } else {

                error_log($ex->getMessage());

                $errors[] =
                    'Không thể cập nhật thông tin. '
                    . 'Vui lòng thử lại.';
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

    <title>
        Thông tin cá nhân - MoodPop
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >
</head>

<body class="profile-page">

<main class="profile-container">

    <!-- =====================================
         HEADER
    ===================================== -->

    <div class="profile-header">

        <a href="index.php">

            <img
                src="assets/image/logo-moodpop.png"
                alt="MoodPop"
                class="profile-logo"
            >

        </a>

        <a
            href="index.php"
            class="profile-back"
        >
            ← Về trang chủ
        </a>

    </div>

    <!-- =====================================
         THONG TIN CA NHAN
    ===================================== -->

    <div class="profile-card">

        <div class="profile-intro">

            <div
                class="profile-avatar"
                aria-hidden="true"
            >
                👤
            </div>

            <h1>Thông tin cá nhân</h1>

            <p>
                Quản lý tài khoản MoodPop của bạn.
            </p>

        </div>

        <!-- =================================
             THONG BAO THANH CONG
        ================================= -->

        <?php if ($success): ?>

            <div
                class="reader-alert-success"
                role="status"
            >
                Cập nhật thông tin thành công!
            </div>

        <?php endif; ?>

        <!-- =================================
             THONG BAO LOI
        ================================= -->

        <?php if ($errors): ?>

            <div
                class="reader-alert-error"
                role="alert"
            >

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?= reader_e($error) ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <!-- =================================
             FORM CAP NHAT TAI KHOAN
        ================================= -->

        <form method="POST">

            <!-- CSRF -->

            <input
                type="hidden"
                name="reader_csrf"
                value="<?= reader_e(
                    reader_csrf_token()
                ) ?>"
            >

            <h2>Thông tin tài khoản</h2>

            <!-- TEN HIEN THI -->

            <label for="display_name">
                Tên hiển thị
            </label>

            <input
                type="text"
                name="display_name"
                id="display_name"
                maxlength="70"
                value="<?= reader_e($name) ?>"
                autocomplete="name"
                required
            >

            <!-- EMAIL -->

            <label for="email">
                Email
            </label>

            <input
                type="email"
                name="email"
                id="email"
                maxlength="190"
                value="<?= reader_e($email) ?>"
                autocomplete="email"
                required
            >

            <p class="profile-hint">
                Khi đổi email, bạn cần nhập
                mật khẩu hiện tại để xác nhận.
            </p>

            <div class="profile-divider"></div>

            <h2>Đổi mật khẩu</h2>

            <p class="profile-hint">
                Để trống mật khẩu mới nếu
                không muốn thay đổi.
            </p>

            <!-- MAT KHAU HIEN TAI -->

            <label for="current_password">
                Mật khẩu hiện tại
            </label>

            <input
                type="password"
                name="current_password"
                id="current_password"
                autocomplete="current-password"
            >

            <!-- MAT KHAU MOI -->

            <label for="new_password">
                Mật khẩu mới
            </label>

            <input
                type="password"
                name="new_password"
                id="new_password"
                minlength="8"
                autocomplete="new-password"
            >

            <!-- XAC NHAN MAT KHAU -->

            <label for="confirm_password">
                Nhập lại mật khẩu mới
            </label>

            <input
                type="password"
                name="confirm_password"
                id="confirm_password"
                autocomplete="new-password"
            >

            <!-- NUT LUU -->

            <button
                type="submit"
                class="profile-save-btn"
            >
                Lưu thay đổi
            </button>

        </form>

    </div>

</main>

</body>
</html>
