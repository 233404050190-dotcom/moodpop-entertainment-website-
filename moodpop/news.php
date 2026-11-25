<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/reader_auth.php';

function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

// Doc tham so tim kiem
$q = $_GET['q'] ?? '';
$q = is_string($q) ? trim($q) : '';
$q = substr($q, 0, 150);

// Loc theo slug chuyen muc
$category = $_GET['category'] ?? '';
$category = is_string($category) ? $category : '';

// Chi chap nhan slug hop le truoc khi tao dieu kien truy van
if (
    $category !== '' &&
    !preg_match('/^[a-z0-9-]{1,160}$/', $category)
) {
    $category = '';
}

// Lay trang hien tai
$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);

$page = ($page && $page > 0) ? $page : 1;
$perPage = 9;

// Lay danh muc cho bo loc
$categories = $pdo->query(
    'SELECT name, slug
     FROM categories
     ORDER BY id ASC'
)->fetchAll();

// =====================================
// DIEU KIEN LOC
// =====================================

$where = ["p.status = 'published'"];
$params = [];

if ($q !== '') {
    $where[] = '(p.title LIKE :keyword1
                 OR p.excerpt LIKE :keyword2)';

    $params[':keyword1'] = '%' . $q . '%';
    $params[':keyword2'] = '%' . $q . '%';
}

if ($category !== '') {
    $where[] = 'c.slug = :category';
    $params[':category'] = $category;
}

$whereSql = implode(' AND ', $where);

$fromSql = "
    FROM posts p
    INNER JOIN categories c
        ON p.category_id = c.id
";

// =====================================
// DEM SO LUONG BAI VIET
// =====================================

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) $fromSql WHERE $whereSql"
);

$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

// =====================================
// TRUY VAN DANH SACH BAI VIET
// =====================================

$sql = "
    SELECT
        p.id,
        p.title,
        p.slug,
        p.excerpt,
        p.thumbnail,
        p.published_at,
        c.name AS category_name
    $fromSql
    WHERE $whereSql
    ORDER BY p.published_at DESC, p.id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$posts = $stmt->fetchAll();

// =====================================
// TAO DUONG DAN PHAN TRANG
// =====================================

function newsPageUrl(
    int $page,
    string $q,
    string $category
): string {
    return 'news.php?' . http_build_query([
        'q' => $q,
        'category' => $category,
        'page' => $page
    ]);
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

    <title>Tin tức giải trí - MoodPop</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >
</head>

<body>

<header class="header">

    <div class="container nav-container">

        <a href="index.php" class="logo">

            <img
                src="assets/image/logo-moodpop.png"
                alt="MoodPop"
                class="site-logo"
            >

        </a>

        <nav aria-label="Điều hướng chính">

            <a href="index.php">
                Trang chủ
            </a>

            <a href="news.php" class="active">
                Tin tức
            </a>

            <a href="index.php#chuyen-muc">
                Chuyên mục
            </a>

            <a href="index.php#gioi-thieu">
                Giới thiệu
            </a>

            <!-- TAI KHOAN NGUOI DOC -->

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

<main class="container news-page">

    <div class="section-title">

        <span>MOODPOP DAILY</span>

        <?php
        $selectedCategoryName = '';

        foreach ($categories as $item) {
            if ($item['slug'] === $category) {
                $selectedCategoryName = $item['name'];
                break;
            }
        }
        ?>

        <h1>
            <?= $selectedCategoryName !== ''
                ? 'Tin tức ' . e($selectedCategoryName)
                : 'Tin tức giải trí' ?>
        </h1>

        <p>
            Khám phá phim ảnh, âm nhạc,
            showbiz và truyền hình.
        </p>

    </div>

    <!-- FORM TIM KIEM VA LOC CHUYEN MUC -->

    <form method="GET" class="news-filter">

        <input
            type="search"
            name="q"
            placeholder="Tìm kiếm bài viết..."
            value="<?= e($q) ?>"
        >

        <select name="category">

            <option value="">
                Tất cả chuyên mục
            </option>

            <?php foreach ($categories as $cat): ?>

                <option
                    value="<?= e($cat['slug']) ?>"
                    <?= $category === $cat['slug']
                        ? 'selected'
                        : '' ?>
                >
                    <?= e($cat['name']) ?>
                </option>

            <?php endforeach; ?>

        </select>

        <button type="submit" class="btn">
            Tìm kiếm
        </button>

    </form>

    <p class="news-count">
        Tìm thấy <?= $total ?> bài viết.
    </p>

    <?php if (!$posts): ?>

        <div class="news-empty">

            <h2>
                Chưa tìm thấy bài viết phù hợp
            </h2>

            <p>
                Hãy thử từ khóa hoặc chuyên mục khác.
            </p>

        </div>

    <?php else: ?>

        <div class="news-grid">

            <?php foreach ($posts as $post): ?>

                <article class="news-card">

                    <?php if (!empty($post['thumbnail'])): ?>

                        <img
                            src="<?= e($post['thumbnail']) ?>"
                            alt="<?= e($post['title']) ?>"
                            class="news-thumbnail"
                            loading="lazy"
                        >

                    <?php else: ?>

                        <div class="news-cover">
                            📰
                        </div>

                    <?php endif; ?>

                    <div class="news-card-body">

                        <span class="news-category">
                            <?= e($post['category_name']) ?>
                        </span>

                        <h3>

                            <a href="post.php?<?= http_build_query([
                                'slug' => $post['slug']
                            ]) ?>">

                                <?= e($post['title']) ?>

                            </a>

                        </h3>

                        <p>
                            <?= e(
                                $post['excerpt']
                                ?: 'Đọc bài viết mới trên MoodPop.'
                            ) ?>
                        </p>

                        <a
                            class="read-more"
                            href="post.php?<?= http_build_query([
                                'slug' => $post['slug']
                            ]) ?>"
                        >
                            Đọc tiếp →
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <!-- PHAN TRANG -->

    <?php if ($totalPages > 1): ?>

        <nav
            class="news-pagination"
            aria-label="Phân trang"
        >

            <?php for (
                $i = 1;
                $i <= $totalPages;
                $i++
            ): ?>

                <a
                    class="<?= $i === $page
                        ? 'current'
                        : '' ?>"
                    href="<?= e(newsPageUrl(
                        $i,
                        $q,
                        $category
                    )) ?>"
                >
                    <?= $i ?>
                </a>

            <?php endfor; ?>

        </nav>

    <?php endif; ?>

</main>

<footer class="footer">

    <p>
        &copy; <?= date('Y') ?> MoodPop.
    </p>

</footer>

</body>
</html>