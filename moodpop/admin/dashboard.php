<?php
declare(strict_types=1);

require __DIR__ . '/../config/auth.php';

// Chi Admin moi duoc truy cap
require_admin();

require __DIR__ . '/../config/database.php';

// =====================================
// THONG KE DASHBOARD
// =====================================

// Dem bai viet da xuat ban
$totalPosts = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM posts
     WHERE status = 'published'"
)->fetchColumn();

// Dem binh luan cho duyet
$pendingComments = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM comments
     WHERE status = 'pending'"
)->fetchColumn();

// Tong so bai viet
$allPosts = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM posts"
)->fetchColumn();

// Tong so nguoi dung
$totalUsers = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM users
     WHERE role = 'user'"
)->fetchColumn();

// Ten Admin
$name = htmlspecialchars(
    (string) ($_SESSION['display_name'] ?? 'Admin'),
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard - MoodPop</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >
</head>

<body class="dashboard-page">

<!-- HEADER ADMIN -->
<header class="admin-header">

    <div class="admin-brand">

        <a href="dashboard.php">
            <img
                src="../assets/image/logo-moodpop.png"
                alt="Logo MoodPop"
                class="dashboard-logo"
            >
        </a>

        <span>QUẢN TRỊ</span>

    </div>

    <!-- DANG XUAT -->
    <form action="logout.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                csrf_token(),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <button
            type="submit"
            class="logout-btn"
        >
            Đăng xuất
        </button>

    </form>

</header>

<!-- NOI DUNG DASHBOARD -->
<main class="dashboard">

    <h1>Xin chào, <?= $name ?>! 👋</h1>

    <p>
        Chào mừng đến với hệ thống quản trị
        tin tức giải trí MoodPop.
    </p>

    <!-- CAC CHUC NANG QUAN LY -->
    <section class="dashboard-management">

        <h2>Chức năng quản lý</h2>

        <div class="dashboard-actions">

            <a
                href="posts.php"
                class="admin-link"
            >
                📝 Quản lý bài viết
            </a>

            <a
                href="comments.php"
                class="admin-link"
            >
                💬 Duyệt bình luận

                <?php if ($pendingComments > 0): ?>
                    (<?= $pendingComments ?>)
                <?php endif; ?>
            </a>

        </div>

    </section>

    <!-- THONG KE -->
    <section class="dashboard-statistics">

        <h2>Thống kê tổng quan</h2>

        <div class="stats-grid">

            <!-- TONG BAI VIET -->
            <div class="stat-card">
                <p>Tổng số bài viết</p>
                <strong><?= $allPosts ?></strong>
            </div>

            <!-- BAI DA XUAT BAN -->
            <div class="stat-card">
                <p>Bài viết đã xuất bản</p>
                <strong><?= $totalPosts ?></strong>
            </div>

            <!-- BINH LUAN CHO DUYET -->
            <div class="stat-card">
                <p>Bình luận chờ duyệt</p>
                <strong><?= $pendingComments ?></strong>

                <a
                    href="comments.php?status=pending"
                    class="stat-link"
                >
                    Xem bình luận →
                </a>
            </div>

            <!-- NGUOI DUNG -->
            <div class="stat-card">
                <p>Người dùng đã đăng ký</p>
                <strong><?= $totalUsers ?></strong>
            </div>

        </div>

    </section>

</main>

</body>
</html>
