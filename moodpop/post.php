<?php
declare(strict_types=1);

// KET NOI DATABASE VA TAI KHOAN NGUOI DOC
require_once __DIR__ . '/config/reader_auth.php';
require __DIR__ . '/config/database.php';

// HAM HIEN THI DU LIEU AN TOAN
function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

// LAY SLUG TU DUONG DAN
$slug = $_GET['slug'] ?? '';
$post = null;

// KIEM TRA SLUG
if (
    is_string($slug)
    && strlen($slug) <= 240
    && preg_match(
        '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        $slug
    )
) {

    // CHI LAY BAI VIET DA XUAT BAN
    $sql = "
        SELECT
            p.id,
            p.title,
            p.excerpt,
            p.content,
            p.thumbnail,
            p.published_at,
            p.created_at,
            c.name AS category_name,
            c.slug AS category_slug,
            u.display_name AS author_name
        FROM posts p
        INNER JOIN categories c
            ON p.category_id = c.id
        INNER JOIN users u
            ON p.author_id = u.id
        WHERE p.slug = ?
          AND p.status = 'published'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$slug]);

    $post = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// NEU KHONG TIM THAY BAI VIET
if ($post === null) {
    http_response_code(404);
}

// TIEU DE TRANG
$pageTitle = $post
    ? (string) $post['title'] . ' - MoodPop'
    : 'Không tìm thấy bài viết - MoodPop';

// NGAY DANG
$publishedDate = '';

if ($post !== null) {

    $timestamp = strtotime(
        (string) (
            $post['published_at']
            ?: $post['created_at']
        )
    );

    $publishedDate = $timestamp !== false
        ? date('d/m/Y', $timestamp)
        : 'Chưa xác định';
}

// =====================================
// LAY BINH LUAN DA DUOC DUYET
// =====================================

$comments = [];

if ($post !== null) {

    $commentStmt = $pdo->prepare(
        "SELECT
            c.id,
            c.content,
            c.created_at,
            u.display_name
         FROM comments c
         INNER JOIN users u
            ON c.user_id = u.id
         WHERE c.post_id = ?
           AND c.status = 'approved'
         ORDER BY c.created_at DESC, c.id DESC"
    );

    $commentStmt->execute([
        (int) $post['id']
    ]);

    $comments = $commentStmt->fetchAll(
        PDO::FETCH_ASSOC
    );
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

    <title><?= e($pageTitle) ?></title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >
</head>

<body>

<!-- =========================
     HEADER MOODPOP
========================= -->

<header class="header">

    <div class="container nav-container">

        <!-- LOGO -->
        <a href="index.php" class="logo">

            <img
                src="assets/image/logo-moodpop.png"
                alt="MoodPop"
                class="site-logo"
            >

        </a>

        <!-- MENU -->
        <nav aria-label="Điều hướng chính">

            <a href="index.php">
                Trang chủ
            </a>

            <a href="news.php">
                Tin tức
            </a>

            <a href="index.php#chuyen-muc">
                Chuyên mục
            </a>

            <!-- TAI KHOAN -->
            <?php if (reader_id() !== null): ?>

                <details class="account-menu">

                    <summary class="account-trigger">

                        <span
                            class="account-avatar"
                            aria-hidden="true"
                        >
                            👤
                        </span>

                        <span class="account-name">
                            <?= reader_e(reader_name()) ?>
                        </span>

                        <span
                            class="account-arrow"
                            aria-hidden="true"
                        >
                            ▾
                        </span>

                    </summary>

                    <div class="account-dropdown">

                        <div class="account-dropdown-title">
                            Tài khoản MoodPop
                        </div>

                        <a
                            href="profile.php"
                            class="account-dropdown-link"
                        >
                            👤 Thông tin cá nhân
                        </a>

                        <!-- DANG XUAT -->
                        <form
                            action="logout.php"
                            method="POST"
                            class="account-logout-form"
                        >

                            <input
                                type="hidden"
                                name="reader_csrf"
                                value="<?= reader_e(
                                    reader_csrf_token()
                                ) ?>"
                            >

                            <button
                                type="submit"
                                class="account-logout-btn"
                            >
                                ↪ Đăng xuất
                            </button>

                        </form>

                    </div>

                </details>

            <?php else: ?>

                <a href="login.php">
                    Đăng nhập
                </a>

                <a href="register.php">
                    Đăng ký
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>

<!-- =========================
     NOI DUNG CHINH
========================= -->

<main class="article-container">

    <?php if ($post === null): ?>

        <!-- BAI VIET KHONG TON TAI -->
        <div class="article-not-found">

            <h1>Không tìm thấy bài viết</h1>

            <p>
                Bài viết không tồn tại
                hoặc chưa được xuất bản.
            </p>

            <a href="news.php" class="btn">
                Quay lại tin tức
            </a>

        </div>

    <?php else: ?>

        <!-- CHI TIET BAI VIET -->
        <article class="article-detail">

            <!-- QUAY LAI -->
            <a href="news.php" class="article-back">
                ← Quay lại tin tức
            </a>

            <!-- CHUYEN MUC -->
            <a
                class="article-category"
                href="news.php?category=<?= rawurlencode(
                    (string) $post['category_slug']
                ) ?>"
            >
                <?= e(
                    (string) $post['category_name']
                ) ?>
            </a>

            <!-- TIEU DE -->
            <h1>
                <?= e((string) $post['title']) ?>
            </h1>

            <!-- THONG TIN BAI VIET -->
            <div class="article-meta">

                <span>
                    Tác giả:
                    <?= e(
                        (string) $post['author_name']
                    ) ?>
                </span>

                <span>
                    Ngày đăng:
                    <?= e($publishedDate) ?>
                </span>

            </div>

            <!-- ANH DAI DIEN -->
            <?php if (!empty($post['thumbnail'])): ?>

                <img
                    src="<?= e(
                        (string) $post['thumbnail']
                    ) ?>"
                    alt="<?= e(
                        (string) $post['title']
                    ) ?>"
                    class="article-thumbnail"
                >

            <?php endif; ?>

            <!-- MO TA NGAN -->
            <?php if (!empty($post['excerpt'])): ?>

                <p class="article-excerpt">
                    <?= e(
                        (string) $post['excerpt']
                    ) ?>
                </p>

            <?php endif; ?>

            <!-- =========================
                 NOI DUNG VA ANH CHEN
            ========================= -->

            <div class="article-content">

                <?php

                // Tach van ban va dau danh dau anh
                $parts = preg_split(
                    '/(\[\[image:[a-f0-9]{32}\.(?:jpg|png|webp)\]\])/',
                    (string) $post['content'],
                    -1,
                    PREG_SPLIT_DELIM_CAPTURE
                ) ?: [];

                foreach ($parts as $part):

                    // KIEM TRA DAU DANH DAU ANH
                    if (preg_match(
                        '/^\[\[image:([a-f0-9]{32}\.(?:jpg|png|webp))\]\]$/',
                        $part,
                        $match
                    )):

                        $filename = $match[1];

                        // KIEM TRA FILE ANH TON TAI
                        $absolute = __DIR__
                            . '/assets/uploads/posts/'
                            . $filename;

                        if (is_file($absolute)):
                ?>

                    <!-- ANH TRONG NOI DUNG -->
                    <figure class="article-inline-photo">

                        <img
                            src="assets/uploads/posts/<?= e(
                                $filename
                            ) ?>"
                            alt="Hình minh họa bài viết"
                            loading="lazy"
                        >

                    </figure>

                <?php
                        endif;

                    else:

                        // VAN BAN THUONG
                        // KHONG THUC THI HTML
                        echo nl2br(e($part));

                    endif;

                endforeach;

                ?>

            </div>

<!-- KHU VUC BINH LUAN -->
<section class="comment-section" id="binh-luan">

    <div class="comment-heading">
        <h2>
            Bình luận
            (<?= count($comments) ?>)
        </h2>

        <p>
            Cùng chia sẻ cảm nhận của bạn
            về bài viết này.
        </p>
    </div>

    <!-- THONG BAO SAU KHI GUI -->
    <?php if (
        ($_GET['comment'] ?? '') === 'pending'
    ): ?>

        <div class="comment-success" role="status">
            Bình luận đã được gửi!
            Nội dung sẽ xuất hiện sau khi Admin duyệt.
        </div>

    <?php elseif (
        ($_GET['comment'] ?? '') === 'invalid'
    ): ?>

        <div class="comment-error" role="alert">
            Bình luận phải từ 5 đến 1000 ký tự.
        </div>

    <?php elseif (
        ($_GET['comment'] ?? '') === 'slow'
    ): ?>

        <div class="comment-error" role="alert">
            Bạn gửi bình luận quá nhanh.
            Hãy thử lại sau ít giây.
        </div>

    <?php endif; ?>

    <!-- FORM GUI BINH LUAN -->
    <?php if (reader_id() !== null): ?>

        <form
            method="POST"
            action="comment_submit.php"
            class="comment-form"
        >

            <input
                type="hidden"
                name="reader_csrf"
                value="<?= reader_e(
                    reader_csrf_token()
                ) ?>"
            >

            <input
                type="hidden"
                name="post_id"
                value="<?= (int) $post['id'] ?>"
            >

            <label for="comment-content">
                Viết bình luận của bạn
            </label>

            <textarea
                name="content"
                id="comment-content"
                rows="4"
                maxlength="1000"
                minlength="5"
                placeholder="Bạn nghĩ gì về bài viết này?"
                required
            ></textarea>

            <p class="comment-note">
                Bình luận sẽ được kiểm duyệt
                trước khi hiển thị công khai.
            </p>

            <button
                type="submit"
                class="comment-submit"
            >
                Gửi bình luận
            </button>

        </form>

    <?php else: ?>

        <div class="comment-login-box">
            <p>
                Bạn cần đăng nhập để
                tham gia bình luận.
            </p>

            <a href="login.php">
                Đăng nhập
            </a>

            <a href="register.php">
                Tạo tài khoản
            </a>
        </div>

    <?php endif; ?>

    <!-- DANH SACH BINH LUAN -->
    <div class="comment-list">

        <h3>Bình luận của độc giả</h3>

        <?php if (!$comments): ?>

            <p class="comment-empty">
                Chưa có bình luận được duyệt.
                Hãy là người đầu tiên chia sẻ cảm nhận!
            </p>

        <?php else: ?>

            <?php foreach ($comments as $comment): ?>

                <div class="comment-item">

                    <div class="comment-avatar"
                         aria-hidden="true">
                        👤
                    </div>

                    <div class="comment-item-content">

                        <div class="comment-author">
                            <?= e(
                                (string) $comment['display_name']
                            ) ?>
                        </div>

                        <time class="comment-date">
                            <?= e(
                                date(
                                    'd/m/Y H:i',
                                    strtotime(
                                        $comment['created_at']
                                    )
                                )
                            ) ?>
                        </time>

                        <p class="comment-text">
                            <?= nl2br(e(
                                (string) $comment['content']
                            )) ?>
                        </p>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</section>

            <!-- CUOI BAI VIET -->
            <div class="article-bottom">

                <a href="news.php">
                    ← Xem thêm tin tức MoodPop
                </a>

            </div>

        </article>

    <?php endif; ?>

</main>

<!-- =========================
     FOOTER
========================= -->

<footer class="footer">

    <p>
        &copy; <?= date('Y') ?> MoodPop.
        Tin tức giải trí mỗi ngày.
    </p>

</footer>

</body>
</html>
