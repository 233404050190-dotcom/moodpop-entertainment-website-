<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/reader_auth.php';

// =====================================
// HAM HIEN THI AN TOAN
// =====================================

function homeEsc(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function homeImage(?string $path): ?string
{
    $path = trim($path ?? '');

    if (
        $path === '' ||
        !preg_match(
            '~^assets/[a-zA-Z0-9/_.-]+$~',
            $path
        )
    ) {
        return null;
    }

    return $path;
}

// =====================================
// LAY 6 BAI VIET MOI NHAT TU MYSQL
// =====================================

// Chi lay bai da xuat ban
// Bai moi nhat xep dau

$latestPosts = $pdo->query(
    "SELECT
        p.id,
        p.title,
        p.slug,
        p.excerpt,
        p.thumbnail,
        p.published_at,
        c.name AS category_name,
        c.slug AS category_slug
     FROM posts AS p
     LEFT JOIN categories AS c
        ON c.id = p.category_id
     WHERE p.status = 'published'
     ORDER BY
        p.published_at DESC,
        p.id DESC
     LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

// =====================================
// SLIDER LAY 3 BAI VIET MOI NHAT
// =====================================

$sliderPosts = array_slice(
    $latestPosts,
    0,
    3
);

// =====================================
// LAY CHUYEN MUC
// =====================================

$categories = $pdo->query(
    'SELECT name, slug
     FROM categories
     ORDER BY id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

// =====================================
// KIEM TRA TRANG THAI DANG NHAP
// =====================================

$isLoggedIn = reader_id() !== null;
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
        MoodPop - Chạm đúng mood, bắt trọn xu hướng!
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css?v=home13"
    >

    <style>

        /* =====================================
           FONT CHU TOAN BO TRANG CHU
        ===================================== */

        .mp-home,
        .mp-home * {
            font-family: "Segoe UI", Arial, sans-serif !important;
        }

        .mp-home {
            margin: 0;
            background: #ffffff;
            color: #171717;
        }

        .mp-home * {
            box-sizing: border-box;
        }

        .mp-container {
            width: min(1200px, calc(100% - 48px));
            margin: 0 auto;
        }

        /* =====================================
           HEADER
        ===================================== */

        .mp-site-header {
            background: #ffffff;
            border-bottom: 1px solid #eeeeee;
        }

        .mp-nav-wrap {
            min-height: 110px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 26px;
        }

        .mp-brand img {
            display: block;
            width: 180px;
            max-width: 100%;
            height: auto;
        }

        .mp-menu {
            display: flex;
            flex-wrap: wrap;

            align-items: center;
            justify-content: flex-end;

            gap: 26px;
        }

        .mp-menu a {
            color: #171717;
            text-decoration: none;

            font-size: 17px;
            font-weight: 600;
        }

        .mp-menu a:hover,
        .mp-menu a[aria-current="page"] {
            color: #6d28d9;
        }

        /* DANG XUAT: GUI POST KEM CSRF TOKEN */

        .mp-menu .mp-logout-form {
            display: inline-flex;
            margin: 0;
        }

        .mp-menu .mp-logout-btn {
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
            color: #171717;
            font-family: inherit;
            font-size: 17px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .mp-menu .mp-logout-btn:hover {
            color: #6d28d9;
        }

        /* =====================================
           HERO: SLOGAN + SLIDER
        ===================================== */

        .mp-hero {
            display: grid;

            grid-template-columns:
                minmax(0, 1fr)
                minmax(0, 1fr);

            align-items: center;

            gap: 52px;
            padding: 70px 0 75px;
        }

        .mp-eyebrow {
            display: inline-block;

            padding: 13px 20px;
            border-radius: 100px;

            background: #ffe4e6;
            color: #be123c;

            font-size: 14px;
            font-weight: 800;
        }

        .mp-hero-title {
            margin: 28px 0 20px;

            font-size: clamp(35px, 4.4vw, 60px);
            font-weight: 700;
            line-height: 1.2;

            letter-spacing: -0.5px;
        }

        .mp-hero-title span {
            color: #6d28d9;
        }

        .mp-hero-intro {
            max-width: 530px;
            margin: 0 0 30px;

            color: #64748b;

            font-size: 18px;
            font-weight: 400;
            line-height: 1.75;
        }

        .mp-primary-btn {
            display: inline-block;

            padding: 16px 26px;

            background: #6d28d9;
            color: #ffffff !important;

            border-radius: 12px;

            text-decoration: none;
            font-size: 16px;
            font-weight: 700;
        }

        .mp-primary-btn:hover {
            background: #5b21b6;
        }

        /* =====================================
           SLIDER 3 BAI VIET MOI NHAT
        ===================================== */

        .mp-slider {
            position: relative;

            min-width: 0;
            min-height: 430px;

            overflow: hidden;
            border-radius: 22px;

            background: #ede9fe;

            box-shadow:
                0 15px 35px rgba(55, 25, 90, 0.12);
        }

        .mp-slide {
            position: absolute;
            inset: 0;

            opacity: 0;
            visibility: hidden;
            pointer-events: none;

            transition:
                opacity 0.5s ease,
                visibility 0.5s ease;
        }

        .mp-slide.is-active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            z-index: 1;
        }

        .mp-slide-link {
            display: block;

            width: 100%;
            height: 100%;

            text-decoration: none;
            color: #ffffff;
        }

        .mp-slide-media {
            width: 100%;
            height: 100%;

            background: linear-gradient(
                135deg,
                #a78bfa,
                #fb7185
            );
        }

        .mp-slide-media img {
            display: block;

            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        /* NOI DUNG HIEN THI TREN ANH */

        .mp-slide-content {
            position: absolute;
            inset: auto 0 0;

            padding: 85px 28px 74px;

            background: linear-gradient(
                transparent,
                rgba(17, 12, 34, 0.9)
            );
        }

        .mp-slide-category {
            display: inline-block;

            margin-bottom: 9px;
            padding: 6px 11px;

            border-radius: 20px;

            background: #fdf2f8;
            color: #7e22ce;

            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        /* TIEU DE SLIDER - GIOI HAN 2 DONG */

        .mp-slide-content h2 {
            margin: 0;

            color: #ffffff;

            font-size: clamp(20px, 2vw, 27px);
            font-weight: 700;
            line-height: 1.4;

            display: -webkit-box;

            -webkit-line-clamp: 2;
            line-clamp: 2;

            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* MO TA SLIDER - GIOI HAN 2 DONG */

        .mp-slide-content p {
            margin: 10px 0 0;

            color: #e2e8f0;

            font-size: 14px;
            line-height: 1.5;

            display: -webkit-box;

            -webkit-line-clamp: 2;
            line-clamp: 2;

            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* NUT CHUYEN SLIDER */

        .mp-slider-actions {
            position: absolute;
            z-index: 3;

            right: 22px;
            bottom: 20px;
            left: 22px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 10px;
        }

        .mp-slide-controls,
        .mp-slider-dots {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mp-slider-btn {
            width: 36px;
            height: 36px;

            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 50%;

            background: rgba(0, 0, 0, 0.3);
            color: #ffffff;

            cursor: pointer;
            font-size: 18px;
        }

        .mp-slider-btn:hover {
            background: #6d28d9;
        }

        /* DAU TRON SLIDER */

        .mp-slider-dot {
            width: 9px;
            height: 9px;

            padding: 0;
            border: 0;
            border-radius: 50%;

            background: rgba(255, 255, 255, 0.5);

            cursor: pointer;
        }

        .mp-slider-dot.is-active {
            width: 24px;
            border-radius: 10px;
            background: #ffffff;
        }

        .mp-slider-empty {
            height: 430px;
            padding: 35px;

            display: flex;
            align-items: center;
            justify-content: center;

            text-align: center;

            color: #6d28d9;
            font-weight: 700;
        }

        /* =====================================
           TIN TUC GIAI TRI MOI
        ===================================== */

        .mp-news {
            padding: 16px 0 72px;
        }

        .mp-section-kicker {
            color: #6d28d9;

            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .mp-section-title {
            margin: 10px 0 8px;

            font-size: 30px;
            font-weight: 700;
        }

        .mp-section-desc {
            color: #64748b;
            margin: 0 0 28px;
        }

        /* 6 BAI - 3 COT VA 2 HANG */

        .mp-post-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 24px;
        }

        .mp-post-card {
            min-width: 0;

            overflow: hidden;

            border: 1px solid #eee7f5;
            border-radius: 16px;

            background: #ffffff;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .mp-post-card:hover {
            transform: translateY(-4px);

            box-shadow:
                0 12px 28px rgba(67, 30, 110, 0.09);
        }

        /* ANH DAI DIEN BAI VIET */

        .mp-card-photo {
            display: block;

            width: 100%;
            aspect-ratio: 16 / 9;

            overflow: hidden;

            background: linear-gradient(
                135deg,
                #c4b5fd,
                #fecdd3
            );
        }

        .mp-card-photo img {
            display: block;

            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .mp-card-body {
            padding: 20px;
        }

        .mp-card-category {
            color: #6d28d9;

            text-decoration: none;

            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        /* TIEU DE BAI VIET */

        .mp-card-title {
            margin: 10px 0;

            font-size: 18px;
            font-weight: 700;
            line-height: 1.45;
        }

        .mp-card-title a {
            color: #171717;
            text-decoration: none;
        }

        .mp-card-title a:hover {
            color: #6d28d9;
        }

        /* MO TA BAI VIET - GIOI HAN 3 DONG */

        .mp-card-excerpt {
            min-height: 68px;

            color: #64748b;

            font-size: 14px;
            font-weight: 400;
            line-height: 1.65;

            display: -webkit-box;

            -webkit-line-clamp: 3;
            line-clamp: 3;

            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* DOC TIEP */

        .mp-read-more {
            display: inline-block;
            margin-top: 10px;

            color: #6d28d9;
            text-decoration: none;

            font-size: 14px;
            font-weight: 600;
        }

        .mp-no-posts {
            border: 1px dashed #c4b5fd;
            border-radius: 15px;

            padding: 30px;

            color: #6d28d9;
        }

        /* XEM TAT CA TIN TUC */

        .mp-all-posts {
            margin-top: 30px;
            text-align: center;
        }

        .mp-all-posts a {
            color: #6d28d9;

            font-size: 14px;
            font-weight: 600;
        }

        /* =====================================
           CHUYEN MUC
        ===================================== */

        .mp-category-area {
            padding: 0 0 70px;
        }

        .mp-category-list {
            display: flex;
            flex-wrap: wrap;

            gap: 12px;
            margin-top: 20px;
        }

        .mp-category-list a {
            display: inline-block;

            padding: 12px 20px;

            border: 1px solid #e9d5ff;
            border-radius: 14px;

            background: #faf5ff;

            color: #6d28d9;
            text-decoration: none;
            font-weight: 700;
        }

        .mp-category-list a:hover {
            background: #ede9fe;
        }

        /* =====================================
           FOOTER
        ===================================== */

        .mp-footer {
            border-top: 1px solid #eeeeee;

            padding: 28px 0;

            text-align: center;
            color: #64748b;
        }

        /* =====================================
           RESPONSIVE TABLET
        ===================================== */

        @media (max-width: 950px) {

            .mp-nav-wrap {
                padding: 16px 0;
                flex-wrap: wrap;
            }

            .mp-menu {
                gap: 16px;
            }

            .mp-hero {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 48px 0;
            }

            .mp-slider {
                min-height: 400px;
            }

            .mp-post-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        /* =====================================
           RESPONSIVE DIEN THOAI
        ===================================== */

        @media (max-width: 620px) {

            .mp-container {
                width: min(
                    1200px,
                    calc(100% - 28px)
                );
            }

            .mp-brand img {
                width: 145px;
            }

            .mp-menu a,
            .mp-menu .mp-logout-btn {
                font-size: 14px;
            }

            .mp-hero-title {
                font-size: 38px;
            }

            .mp-hero-intro {
                font-size: 16px;
            }

            .mp-slider,
            .mp-slider-empty {
                min-height: 350px;
                height: 350px;
            }

            .mp-slide-content {
                padding: 65px 18px 68px;
            }

            .mp-post-grid {
                grid-template-columns: 1fr;
            }
        }

        /* HAN CHE HIEU UNG CHUYEN DONG */

        @media (prefers-reduced-motion: reduce) {

            .mp-slide,
            .mp-post-card {
                transition: none;
            }
        }

    </style>
</head>

<body class="mp-home">

<!-- =====================================
     HEADER MOODPOP
===================================== -->

<header class="mp-site-header">

    <div class="mp-container mp-nav-wrap">

        <!-- LOGO -->

        <a
            class="mp-brand"
            href="index.php"
            aria-label="MoodPop - Trang chủ"
        >

            <img
                src="assets/image/logo-moodpop.png"
                alt="MoodPop"
            >

        </a>

        <!-- MENU -->

        <nav
            class="mp-menu"
            aria-label="Menu chính"
        >

            <a
                href="index.php"
                aria-current="page"
            >
                Trang chủ
            </a>

            <a href="news.php">
                Tin tức
            </a>

            <a href="#chuyen-muc">
                Chuyên mục
            </a>

            <a href="#gioi-thieu">
                Giới thiệu
            </a>

            <!-- NEU DA DANG NHAP -->

            <?php if ($isLoggedIn): ?>

                <a href="profile.php">
                    Tài khoản
                </a>

                <form
                    action="logout.php"
                    method="POST"
                    class="mp-logout-form"
                >
                    <input
                        type="hidden"
                        name="reader_csrf"
                        value="<?= homeEsc(reader_csrf_token()) ?>"
                    >

                    <button
                        type="submit"
                        class="mp-logout-btn"
                    >
                        Đăng xuất
                    </button>
                </form>

            <?php else: ?>

                <!-- NEU CHUA DANG NHAP -->

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

<main>

    <!-- =====================================
         HERO: SLOGAN + SLIDER 3 BAI
    ===================================== -->

    <section
        class="mp-container mp-hero"
        aria-label="Tin tức nổi bật mới nhất"
    >

        <!-- BEN TRAI: SLOGAN -->

        <div class="mp-hero-copy">

            <span class="mp-eyebrow">
                TIN TỨC GIẢI TRÍ
            </span>

            <h1 class="mp-hero-title">
                Chạm đúng mood,
                <br>
                <span>bắt trọn xu hướng!</span>
            </h1>

            <p class="mp-hero-intro">
                Cập nhật những câu chuyện thú vị
                về phim ảnh, âm nhạc, showbiz
                và truyền hình cùng MoodPop.
            </p>

            <a
                class="mp-primary-btn"
                href="#tin-moi"
            >
                Khám phá ngay
            </a>

        </div>

        <!-- BEN PHAI: SLIDER -->

        <div
            class="mp-slider"
            data-home-slider
            aria-roledescription="carousel"
            aria-label="3 bài viết mới nhất"
        >

            <?php if (!$sliderPosts): ?>

                <!-- CHUA CO BAI VIET -->

                <div class="mp-slider-empty">
                    Chưa có bài viết xuất bản
                    để hiển thị.
                </div>

            <?php else: ?>

                <!-- HIEN THI 3 BAI MOI NHAT -->

                <?php foreach (
                    $sliderPosts as $i => $article
                ): ?>

                    <?php
                    $slideImage = homeImage(
                        $article['thumbnail'] ?? null
                    );
                    ?>

                    <article
                        class="mp-slide <?= $i === 0
                            ? 'is-active'
                            : '' ?>"
                        data-home-slide
                        aria-hidden="<?= $i === 0
                            ? 'false'
                            : 'true' ?>"
                    >

                        <a
                            class="mp-slide-link"
                            href="post.php?slug=<?= rawurlencode(
                                (string) $article['slug']
                            ) ?>"
                            tabindex="<?= $i === 0
                                ? '0'
                                : '-1' ?>"
                        >

                            <!-- ANH BAI VIET -->

                            <div class="mp-slide-media">

                                <?php if ($slideImage): ?>

                                    <img
                                        src="<?= homeEsc(
                                            $slideImage
                                        ) ?>"
                                        alt="<?= homeEsc(
                                            (string) $article['title']
                                        ) ?>"
                                    >

                                <?php endif; ?>

                            </div>

                            <!-- THONG TIN BAI VIET -->

                            <div class="mp-slide-content">

                                <span class="mp-slide-category">

                                    <?= homeEsc(
                                        $article['category_name']
                                            ?? 'Tin tức'
                                    ) ?>

                                </span>

                                <h2>

                                    <?= homeEsc(
                                        (string) $article['title']
                                    ) ?>

                                </h2>

                                <?php if (
                                    !empty($article['excerpt'])
                                ): ?>

                                    <p>

                                        <?= homeEsc(
                                            (string) $article['excerpt']
                                        ) ?>

                                    </p>

                                <?php endif; ?>

                            </div>

                        </a>

                    </article>

                <?php endforeach; ?>

                <!-- NUT CHUYEN SLIDER -->

                <?php if (
                    count($sliderPosts) > 1
                ): ?>

                    <div class="mp-slider-actions">

                        <!-- DAU TRON -->

                        <div
                            class="mp-slider-dots"
                            aria-label="Chọn bài viết"
                        >

                            <?php foreach (
                                $sliderPosts as $i => $_
                            ): ?>

                                <button
                                    type="button"
                                    class="mp-slider-dot <?= $i === 0
                                        ? 'is-active'
                                        : '' ?>"
                                    data-home-dot="<?= $i ?>"
                                    aria-label="Hiển thị bài <?= $i + 1 ?>"
                                    aria-pressed="<?= $i === 0
                                        ? 'true'
                                        : 'false' ?>"
                                ></button>

                            <?php endforeach; ?>

                        </div>

                        <!-- MUI TEN TRAI PHAI -->

                        <div class="mp-slide-controls">

                            <button
                                type="button"
                                class="mp-slider-btn"
                                data-home-prev
                                aria-label="Bài trước"
                            >
                                &#10094;
                            </button>

                            <button
                                type="button"
                                class="mp-slider-btn"
                                data-home-next
                                aria-label="Bài tiếp"
                            >
                                &#10095;
                            </button>

                        </div>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>

    </section>

    <!-- =====================================
         TIN TUC GIAI TRI MOI: 6 BAI
    ===================================== -->

    <section
        class="mp-container mp-news"
        id="tin-moi"
    >

        <span class="mp-section-kicker">
            MOODPOP DAILY
        </span>

        <h2 class="mp-section-title">
            Tin tức giải trí mới
        </h2>

        <p class="mp-section-desc">
            Cập nhật những câu chuyện mới nhất
            trong thế giới giải trí.
        </p>

        <?php if (!$latestPosts): ?>

            <p class="mp-no-posts">
                Chưa có bài viết được xuất bản.
            </p>

        <?php else: ?>

            <div class="mp-post-grid">

                <?php foreach (
                    $latestPosts as $article
                ): ?>

                    <?php
                    $cardImage = homeImage(
                        $article['thumbnail'] ?? null
                    );
                    ?>

                    <article class="mp-post-card">

                        <!-- ANH DAI DIEN -->

                        <a
                            class="mp-card-photo"
                            href="post.php?slug=<?= rawurlencode(
                                (string) $article['slug']
                            ) ?>"
                        >

                            <?php if ($cardImage): ?>

                                <img
                                    loading="lazy"
                                    src="<?= homeEsc(
                                        $cardImage
                                    ) ?>"
                                    alt="<?= homeEsc(
                                        (string) $article['title']
                                    ) ?>"
                                >

                            <?php endif; ?>

                        </a>

                        <div class="mp-card-body">

                            <!-- TEN CHUYEN MUC -->

                            <a
                                class="mp-card-category"
                                href="news.php?category=<?= rawurlencode(
                                    (string) (
                                        $article['category_slug']
                                        ?? ''
                                    )
                                ) ?>"
                            >

                                <?= homeEsc(
                                    $article['category_name']
                                        ?? 'Tin tức'
                                ) ?>

                            </a>

                            <!-- TIEU DE BAI VIET -->

                            <h3 class="mp-card-title">

                                <a
                                    href="post.php?slug=<?= rawurlencode(
                                        (string) $article['slug']
                                    ) ?>"
                                >

                                    <?= homeEsc(
                                        (string) $article['title']
                                    ) ?>

                                </a>

                            </h3>

                            <!-- MO TA NGAN -->

                            <p class="mp-card-excerpt">

                                <?= homeEsc(
                                    (string) (
                                        $article['excerpt']
                                        ?? ''
                                    )
                                ) ?>

                            </p>

                            <!-- NUT DOC TIEP -->

                            <a
                                class="mp-read-more"
                                href="post.php?slug=<?= rawurlencode(
                                    (string) $article['slug']
                                ) ?>"
                            >
                                Đọc tiếp &rarr;
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <!-- XEM TAT CA TIN TUC -->

        <div class="mp-all-posts">

            <a href="news.php">
                Xem tất cả tin tức &rarr;
            </a>

        </div>

    </section>

    <!-- =====================================
         CHUYEN MUC
    ===================================== -->

    <section
        class="mp-container mp-category-area"
        id="chuyen-muc"
    >

        <span class="mp-section-kicker">
            KHÁM PHÁ
        </span>

        <h2 class="mp-section-title">
            Chuyên mục
        </h2>

        <div class="mp-category-list">

            <?php foreach (
                $categories as $category
            ): ?>

                <a
                    href="news.php?category=<?= rawurlencode(
                        (string) $category['slug']
                    ) ?>"
                >

                    <?= homeEsc(
                        (string) $category['name']
                    ) ?>

                </a>

            <?php endforeach; ?>

        </div>

    </section>

</main>

<!-- =====================================
     FOOTER
===================================== -->

<footer
    class="mp-footer"
    id="gioi-thieu"
>

    <div class="mp-container">

        MoodPop &mdash;
        Chạm đúng mood, bắt trọn xu hướng!

    </div>

</footer>

<!-- =====================================
     JAVASCRIPT SLIDER TU DONG
===================================== -->

<script>

document.addEventListener('DOMContentLoaded', () => {

    // =================================
    // LAY SLIDER
    // =================================

    const slider = document.querySelector(
        '[data-home-slider]'
    );

    if (!slider) {
        return;
    }

    const slides = Array.from(
        slider.querySelectorAll(
            '[data-home-slide]'
        )
    );

    const dots = Array.from(
        slider.querySelectorAll(
            '[data-home-dot]'
        )
    );

    // Neu chi co 1 bai thi khong can chuyen

    if (slides.length < 2) {
        return;
    }

    const prefersLessMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;

    let index = 0;
    let timer = null;

    // =================================
    // HIEN THI SLIDE
    // =================================

    function showSlide(nextIndex) {

        index = (
            nextIndex + slides.length
        ) % slides.length;

        slides.forEach((slide, i) => {

            const active = i === index;

            slide.classList.toggle(
                'is-active',
                active
            );

            slide.setAttribute(
                'aria-hidden',
                String(!active)
            );

            const link = slide.querySelector('a');

            if (link) {
                link.tabIndex = active ? 0 : -1;
            }

        });

        // CAP NHAT DAU TRON

        dots.forEach((dot, i) => {

            const active = i === index;

            dot.classList.toggle(
                'is-active',
                active
            );

            dot.setAttribute(
                'aria-pressed',
                String(active)
            );

        });
    }

    // =================================
    // DUNG SLIDER
    // =================================

    function stop() {

        if (timer !== null) {
            window.clearInterval(timer);
        }

        timer = null;
    }

    // =================================
    // TU DONG CHUYEN MOI 5 GIAY
    // =================================

    function start() {

        stop();

        if (prefersLessMotion) {
            return;
        }

        timer = window.setInterval(() => {

            if (!document.hidden) {
                showSlide(index + 1);
            }

        }, 5000);
    }

    // =================================
    // NUT BAI TRUOC
    // =================================

    slider.querySelector(
        '[data-home-prev]'
    )?.addEventListener('click', () => {

        showSlide(index - 1);
        start();

    });

    // =================================
    // NUT BAI TIEP
    // =================================

    slider.querySelector(
        '[data-home-next]'
    )?.addEventListener('click', () => {

        showSlide(index + 1);
        start();

    });

    // =================================
    // BAM DAU TRON
    // =================================

    dots.forEach((dot, i) => {

        dot.addEventListener('click', () => {

            showSlide(i);
            start();

        });

    });

    // =================================
    // TAM DUNG KHI RE CHUOT VAO
    // =================================

    slider.addEventListener(
        'mouseenter',
        stop
    );

    slider.addEventListener(
        'mouseleave',
        start
    );

    // =================================
    // TAM DUNG KHI DUNG BAN PHIM
    // =================================

    slider.addEventListener(
        'focusin',
        stop
    );

    slider.addEventListener(
        'focusout',
        event => {

            if (
                !slider.contains(
                    event.relatedTarget
                )
            ) {
                start();
            }

        }
    );

    // =================================
    // DUNG KHI CHUYEN TAB
    // =================================

    document.addEventListener(
        'visibilitychange',
        () => {

            if (document.hidden) {
                stop();
            } else {
                start();
            }

        }
    );

    // =================================
    // BAT DAU SLIDER
    // =================================

    start();

});

</script>

</body>
</html>