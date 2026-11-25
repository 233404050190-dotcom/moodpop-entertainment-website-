<?php
declare(strict_types=1);

require __DIR__ . '/../config/auth.php';
require_admin();

require __DIR__ . '/../config/database.php';

function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

// ====================================
// XU LY DUYET BINH LUAN
// ====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid()) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ.');
    }

    $id = filter_var(
        $_POST['id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $action = $_POST['action'] ?? null;

    $allowed = [
        'approved',
        'rejected',
        'pending'
    ];

    if (
        !is_int($id) ||
        !is_string($action) ||
        !in_array($action, $allowed, true)
    ) {
        http_response_code(400);
        exit('Dữ liệu duyệt bình luận không hợp lệ.');
    }

    $update = $pdo->prepare(
        'UPDATE comments
         SET status = ?
         WHERE id = ?'
    );

    $update->execute([
        $action,
        $id
    ]);

    header('Location: comments.php?updated=1');
    exit;
}

// ====================================
// BO LOC BINH LUAN
// ====================================

$filter = $_GET['status'] ?? 'pending';

if (
    !is_string($filter) ||
    !in_array(
        $filter,
        ['pending', 'approved', 'rejected', 'all'],
        true
    )
) {
    $filter = 'pending';
}

// Dem binh luan cho duyet
$pendingCount = (int) $pdo->query(
    "SELECT COUNT(*)
     FROM comments
     WHERE status = 'pending'"
)->fetchColumn();

// Lay danh sach
$whereSql = $filter === 'all'
    ? ''
    : 'WHERE c.status = :status';

$sql = "
    SELECT
        c.id,
        c.content,
        c.status,
        c.created_at,
        u.display_name,
        p.title AS post_title,
        p.slug AS post_slug
    FROM comments c
    INNER JOIN users u
        ON c.user_id = u.id
    INNER JOIN posts p
        ON c.post_id = p.id
    $whereSql
    ORDER BY c.created_at DESC, c.id DESC
    LIMIT 100
";

$stmt = $pdo->prepare($sql);

if ($filter !== 'all') {
    $stmt->bindValue(
        ':status',
        $filter,
        PDO::PARAM_STR
    );
}

$stmt->execute();

$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'pending' => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối'
];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Duyệt bình luận - MoodPop</title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >
</head>

<body class="dashboard-page">

<header class="admin-header">

    <div class="posts-brand">

        <a href="dashboard.php">
            <img
                src="../assets/image/logo-moodpop.png"
                alt="MoodPop"
                class="posts-logo"
                width="155"
            >
        </a>

        <span class="posts-title">
            Quản lý bình luận
        </span>

    </div>

    <a href="dashboard.php" class="admin-link">
        Về Dashboard
    </a>

</header>

<main class="dashboard">

    <div class="page-heading">
        <div>
            <h1>Duyệt bình luận</h1>

            <p>
                Quản lý nội dung bình luận
                của độc giả MoodPop.
            </p>
        </div>
    </div>

    <div class="review-summary">
        <strong><?= $pendingCount ?></strong>
        <span>Bình luận đang chờ duyệt</span>
    </div>

    <?php if (isset($_GET['updated'])): ?>

        <div class="alert-success">
            Đã cập nhật trạng thái bình luận.
        </div>

    <?php endif; ?>

    <!-- BO LOC -->
    <nav class="review-filters"
         aria-label="Lọc bình luận">

        <a href="comments.php?status=pending"
           class="<?= $filter === 'pending'
               ? 'active' : '' ?>">
            Chờ duyệt
        </a>

        <a href="comments.php?status=approved"
           class="<?= $filter === 'approved'
               ? 'active' : '' ?>">
            Đã duyệt
        </a>

        <a href="comments.php?status=rejected"
           class="<?= $filter === 'rejected'
               ? 'active' : '' ?>">
            Từ chối
        </a>

        <a href="comments.php?status=all"
           class="<?= $filter === 'all'
               ? 'active' : '' ?>">
            Tất cả
        </a>

    </nav>

    <?php if (!$comments): ?>

        <div class="review-empty">
            Không có bình luận trong nhóm này.
        </div>

    <?php else: ?>

        <div class="review-list">

            <?php foreach ($comments as $comment): ?>

                <article class="review-card">

                    <div class="review-card-top">

                        <div>
                            <strong>
                                👤 <?= e(
                                    (string) $comment['display_name']
                                ) ?>
                            </strong>

                            <p class="review-date">
                                <?= e(
                                    (string) $comment['created_at']
                                ) ?>
                            </p>
                        </div>

                        <span class="review-status">
                            <?= e(
                                $statusLabels[$comment['status']]
                                ?? $comment['status']
                            ) ?>
                        </span>

                    </div>

                    <p class="review-post-title">
                        Bài viết:
                        <strong>
                            <?= e(
                                (string) $comment['post_title']
                            ) ?>
                        </strong>
                    </p>

                    <div class="review-content">
                        <?= nl2br(e(
                            (string) $comment['content']
                        )) ?>
                    </div>

                    <!-- NUT KIEM DUYET -->
                    <form
                        method="POST"
                        class="review-actions"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrf_token()) ?>"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $comment['id'] ?>"
                        >

                        <?php if (
                            $comment['status'] !== 'approved'
                        ): ?>

                            <button
                                type="submit"
                                name="action"
                                value="approved"
                                class="review-approve"
                            >
                                Duyệt
                            </button>

                        <?php endif; ?>

                        <?php if (
                            $comment['status'] !== 'rejected'
                        ): ?>

                            <button
                                type="submit"
                                name="action"
                                value="rejected"
                                class="review-reject"
                            >
                                Từ chối
                            </button>

                        <?php endif; ?>

                        <?php if (
                            $comment['status'] !== 'pending'
                        ): ?>

                            <button
                                type="submit"
                                name="action"
                                value="pending"
                                class="review-pending"
                            >
                                Chờ duyệt
                            </button>

                        <?php endif; ?>

                    </form>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

</body>
</html>
