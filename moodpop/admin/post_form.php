<?php
declare(strict_types=1);

require __DIR__ . '/../config/auth.php';
require_admin();

require __DIR__ . '/../config/database.php';

// ====================================
// HAM HO TRO
// ====================================

function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function posted(string $key): string
{
    $value = $_POST[$key] ?? '';

    return is_string($value)
        ? trim($value)
        : '';
}

function lengthOf(string $text): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($text, 'UTF-8')
        : strlen($text);
}

function oldHtmlToText(string $html): string
{
    $html = preg_replace(
        '~<br\s*/?>|</(?:p|div|li|h[1-6])\s*>~i',
        "\n",
        $html
    ) ?? $html;

    return trim(
        html_entity_decode(
            strip_tags($html),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        )
    );
}

// ====================================
// XOA ANH DAI DIEN CU
// ====================================

function removeThumbnail(string $path): void
{
    if (!preg_match(
        '~^assets/uploads/posts/[a-f0-9]{32}[.](?:jpg|png|webp)$~',
        $path
    )) {
        return;
    }

    $absolute = dirname(__DIR__) . '/' . $path;

    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

// ====================================
// TAI ANH DAI DIEN
// ====================================

function saveThumbnail(?array $file): ?string
{
    if (
        $file === null ||
        ($file['error'] ?? null) === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if (($file['error'] ?? null) !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'Tải ảnh đại diện thất bại.'
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
            'Tệp ảnh đại diện không hợp lệ.'
        );
    }

    if ($size < 1 || $size > 2 * 1024 * 1024) {
        throw new RuntimeException(
            'Ảnh đại diện không được vượt quá 2 MB.'
        );
    }

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
            'Chỉ chấp nhận ảnh JPG, PNG hoặc WebP hợp lệ.'
        );
    }

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

    $relative = 'assets/uploads/posts/'
        . bin2hex(random_bytes(16))
        . '.' . $allowed[$mime];

    $destination = dirname(__DIR__) . '/' . $relative;

    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException(
            'Không lưu được ảnh đại diện.'
        );
    }

    return $relative;
}

// ====================================
// KIEM TRA DATABASE
// ====================================

$hasFormat = (bool) $pdo->query(
    "SHOW COLUMNS FROM posts LIKE 'content_format'"
)->fetch();

$categories = $pdo->query(
    'SELECT id, name
     FROM categories
     ORDER BY id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

// ====================================
// THEM HOAC CHINH SUA BAI VIET
// ====================================

$isEdit = isset($_GET['id']);

$id = $isEdit
    ? filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    )
    : 0;

if ($isEdit && (!is_int($id) || $id < 1)) {
    http_response_code(400);
    exit('ID bài viết không hợp lệ.');
}

$post = [
    'title' => '',
    'slug' => '',
    'category_id' => '',
    'excerpt' => '',
    'content' => '',
    'thumbnail' => null,
    'status' => 'draft',
    'content_format' => 'plain'
];

$existing = null;

// ====================================
// LAY BAI VIET KHI CHINH SUA
// ====================================

if ($isEdit) {

    $stmt = $pdo->prepare(
        'SELECT *
         FROM posts
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([$id]);

    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        http_response_code(404);
        exit('Không tìm thấy bài viết.');
    }

    $post = $existing;

    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST' &&
        ($post['content_format'] ?? 'plain') === 'html'
    ) {
        $post['content'] = oldHtmlToText(
            (string) $post['content']
        );
    }
}

$errors = [];

// ====================================
// XU LY LUU BAI VIET
// ====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // KIEM TRA CSRF

    if (!csrf_valid()) {
        $errors[] =
            'Phiên biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
    }

    foreach (
        [
            'title',
            'slug',
            'category_id',
            'excerpt',
            'content',
            'status'
        ] as $key
    ) {
        $post[$key] = posted($key);
    }

    $post['content_format'] = 'plain';

    // KIEM TRA TIEU DE

    if (
        lengthOf($post['title']) < 5 ||
        lengthOf($post['title']) > 200
    ) {
        $errors[] =
            'Tiêu đề phải từ 5 đến 200 ký tự.';
    }

    // KIEM TRA SLUG

    if (
        strlen($post['slug']) > 240 ||
        !preg_match(
            '~^[a-z0-9]+(?:-[a-z0-9]+)*$~',
            $post['slug']
        )
    ) {
        $errors[] =
            'Slug chỉ gồm chữ thường, số và dấu gạch ngang.';
    }

    // KIEM TRA CHUYEN MUC

    $categoryId = filter_var(
        $post['category_id'],
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1
            ]
        ]
    );

    $validIds = array_map(
        'intval',
        array_column($categories, 'id')
    );

    if (
        !is_int($categoryId) ||
        !in_array($categoryId, $validIds, true)
    ) {
        $errors[] =
            'Vui lòng chọn chuyên mục hợp lệ.';
    }

    // KIEM TRA MO TA

    if (lengthOf($post['excerpt']) > 500) {
        $errors[] =
            'Mô tả ngắn tối đa 500 ký tự.';
    }

    // KIEM TRA TRANG THAI

    if (!in_array(
        $post['status'],
        ['draft', 'published'],
        true
    )) {
        $errors[] =
            'Trạng thái bài viết không hợp lệ.';
    }

    // ====================================
    // KIEM TRA ANH CHEN TRONG NOI DUNG
    // ====================================

    // Anh duoc luu duoi dang:
    // [[image:tenfile.jpg]]

    $prefix = chr(91) . chr(91) . 'image:';
    $suffix = chr(93) . chr(93);

    $markerPattern =
        '~' . preg_quote($prefix, '~')
        . '([a-f0-9]{32}[.](?:jpg|png|webp))'
        . preg_quote($suffix, '~')
        . '~';

    // Khong tinh ten anh vao so ky tu

    $onlyText = preg_replace(
        $markerPattern,
        '',
        $post['content']
    );

    if (
        $onlyText === null ||
        lengthOf(trim($onlyText)) < 30 ||
        lengthOf(trim($onlyText)) > 100000 ||
        strlen($post['content']) > 200000
    ) {
        $errors[] =
            'Nội dung chữ phải từ 30 đến 100000 ký tự.';
    }

    // Dem so luong anh

    preg_match_all(
        $markerPattern,
        $post['content'],
        $imageMatches
    );

    if (count($imageMatches[1]) > 10) {
        $errors[] =
            'Mỗi bài viết chỉ được chèn tối đa 10 ảnh.';
    }

    // Kiem tra anh co ton tai

    foreach (
        array_unique($imageMatches[1]) as $filename
    ) {

        $path = dirname(__DIR__)
            . '/assets/uploads/posts/'
            . $filename;

        if (!is_file($path)) {
            $errors[] =
                'Có ảnh trong nội dung không còn tồn tại trên máy chủ.';
            break;
        }
    }

    // ====================================
    // KIEM TRA SLUG TRUNG LAP
    // ====================================

    if (!$errors) {

        $check = $pdo->prepare(
            'SELECT id
             FROM posts
             WHERE slug = ?
               AND id <> ?
             LIMIT 1'
        );

        $check->execute([
            $post['slug'],
            (int) $id
        ]);

        if ($check->fetchColumn()) {
            $errors[] =
                'Slug đã tồn tại. Hãy chọn Slug khác.';
        }
    }

    // ====================================
    // LUU MYSQL
    // ====================================

    if (!$errors) {

        $newThumbnail = null;

        try {

            $upload = $_FILES['thumbnail'] ?? null;

            if (
                $upload !== null &&
                !is_array($upload)
            ) {
                throw new RuntimeException(
                    'Dữ liệu ảnh đại diện không hợp lệ.'
                );
            }

            $newThumbnail = saveThumbnail($upload);

            // Khong chon anh moi thi giu anh cu

            $thumbnailPath = $newThumbnail
                ?? ($existing['thumbnail'] ?? null);

            $excerpt = $post['excerpt'] !== ''
                ? $post['excerpt']
                : null;

            // NGAY XUAT BAN

            $publishedAt = null;

            if ($post['status'] === 'published') {

                $publishedAt = (
                    $isEdit &&
                    ($existing['status'] ?? '') === 'published' &&
                    !empty($existing['published_at'])
                )
                    ? $existing['published_at']
                    : date('Y-m-d H:i:s');
            }

            // ====================================
            // CHINH SUA BAI VIET
            // ====================================

            if ($isEdit) {

                $formatSql = $hasFormat
                    ? ", content_format = 'plain'"
                    : '';

                $sql = '
                    UPDATE posts
                    SET
                        title = ?,
                        slug = ?,
                        category_id = ?,
                        excerpt = ?,
                        content = ?,
                        thumbnail = ?,
                        status = ?,
                        published_at = ?
                ' . $formatSql . '
                    WHERE id = ?
                ';

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $post['title'],
                    $post['slug'],
                    $categoryId,
                    $excerpt,
                    $post['content'],
                    $thumbnailPath,
                    $post['status'],
                    $publishedAt,
                    $id
                ]);

                if (
                    $newThumbnail !== null &&
                    !empty($existing['thumbnail'])
                ) {
                    removeThumbnail(
                        (string) $existing['thumbnail']
                    );
                }

                header(
                    'Location: posts.php?notice=saved'
                );

            } else {

                // ====================================
                // THEM BAI VIET
                // ====================================

                $extraCol = $hasFormat
                    ? ', content_format'
                    : '';

                $extraVal = $hasFormat
                    ? ", 'plain'"
                    : '';

                $sql = '
                    INSERT INTO posts (
                        author_id,
                        category_id,
                        title,
                        slug,
                        excerpt,
                        content,
                        thumbnail,
                        status,
                        published_at
                ' . $extraCol . ')
                    VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?
                ' . $extraVal . ')
                ';

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    (int) $_SESSION['user_id'],
                    $categoryId,
                    $post['title'],
                    $post['slug'],
                    $excerpt,
                    $post['content'],
                    $thumbnailPath,
                    $post['status'],
                    $publishedAt
                ]);

                header(
                    'Location: posts.php?notice=created'
                );
            }

            exit;

        } catch (PDOException $ex) {

            if ($newThumbnail !== null) {
                removeThumbnail($newThumbnail);
            }

            error_log($ex->getMessage());

            $errors[] =
                'Không thể lưu bài viết vào MySQL.';

        } catch (RuntimeException $ex) {

            if ($newThumbnail !== null) {
                removeThumbnail($newThumbnail);
            }

            $errors[] = $ex->getMessage();
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
        <?= $isEdit ? 'Chỉnh sửa' : 'Thêm' ?>
        bài viết - MoodPop
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css?v=12"
    >

    <style>
        /* AN O CHON ANH TRONG NOI DUNG */

        #inline-image-picker {
            display: none !important;
        }

        /* AN DONG TRANG THAI NEU DANG RONG */

        #inline-image-status:empty {
            display: none !important;
        }

        /* GIAO DIEN SOAN THAO */

        .mp-editor-holder {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 16px;
            margin: 12px 0;
            background: #FAF9FF;
            border: 1px solid #DDD6E8;
            border-radius: 12px;
        }

        .post-form .mp-editor-text {
            display: block;
            box-sizing: border-box;
            width: 100%;
            min-height: 92px;
            padding: 14px;
            background: #FFFFFF;
            border: 1px solid #DDD6E8;
            border-radius: 9px;
            font: inherit;
            line-height: 1.7;
            resize: vertical;
        }

        .post-form .mp-editor-text:focus {
            outline: 2px solid #6D28D9;
            outline-offset: 1px;
        }

        /* ANH TRONG NOI DUNG */

        .mp-image {
            padding: 12px;
            background: white;
            border: 1px solid #E9E5F2;
            border-radius: 12px;
        }

        .mp-image img {
            display: block;
            max-width: 100%;
            max-height: 380px;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 9px;
        }

        .mp-remove {
            margin-top: 10px;
            padding: 8px 12px;
            background: #FFF1F2;
            color: #BE123C;
            border: 1px solid #FB7185;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .mp-uploading {
            padding: 16px;
            text-align: center;
            border: 1px dashed #6D28D9;
            color: #6D28D9;
            background: #F5F3FF;
            border-radius: 10px;
        }

        #inline-image-status {
            color: #6D28D9;
            font-size: 14px;
            margin: 8px 0 16px;
        }

        .inline-editor-toolbar {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        @media (max-width: 600px) {
            .inline-editor-toolbar button {
                width: 100%;
            }
        }
    </style>

</head>

<body class="dashboard-page">

<!-- HEADER -->

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
            <?= $isEdit
                ? 'Chỉnh sửa bài viết'
                : 'Thêm bài viết' ?>
        </span>

    </div>

    <a href="posts.php" class="admin-link">
        Danh sách bài viết
    </a>

</header>

<!-- FORM BAI VIET -->

<main class="dashboard">

    <h1>
        <?= $isEdit
            ? 'Chỉnh sửa bài viết'
            : 'Thêm bài viết mới' ?>
    </h1>

    <?php if ($errors): ?>

        <div class="alert-error" role="alert">
            <ul>

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?= e($error) ?>
                    </li>

                <?php endforeach; ?>

            </ul>
        </div>

    <?php endif; ?>

    <form
        method="POST"
        action="<?= $isEdit
            ? 'post_form.php?id=' . (int) $id
            : 'post_form.php' ?>"
        enctype="multipart/form-data"
        class="post-form"
    >

        <!-- CSRF -->

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <!-- TIEU DE -->

        <label for="title">
            Tiêu đề bài viết *
        </label>

        <input
            type="text"
            id="title"
            name="title"
            maxlength="200"
            required
            value="<?= e((string) $post['title']) ?>"
        >

        <!-- SLUG -->

        <label for="slug">
            Đường dẫn định danh (Slug) *
        </label>

        <input
            type="text"
            id="slug"
            name="slug"
            maxlength="240"
            required
            value="<?= e((string) $post['slug']) ?>"
        >

        <p class="field-note">
            Slug tự tạo từ tiêu đề,
            vẫn có thể chỉnh sửa thủ công.
        </p>

        <!-- CHUYEN MUC -->

        <label for="category_id">
            Chuyên mục *
        </label>

        <select
            name="category_id"
            id="category_id"
            required
        >

            <option value="">
                -- Chọn chuyên mục --
            </option>

            <?php foreach ($categories as $category): ?>

                <option
                    value="<?= (int) $category['id'] ?>"
                    <?= (string) $post['category_id'] ===
                        (string) $category['id']
                        ? 'selected'
                        : '' ?>
                >
                    <?= e((string) $category['name']) ?>
                </option>

            <?php endforeach; ?>

        </select>

        <!-- MO TA -->

        <label for="excerpt">
            Mô tả ngắn
        </label>

        <textarea
            name="excerpt"
            id="excerpt"
            rows="3"
            maxlength="500"
        ><?= e((string) ($post['excerpt'] ?? '')) ?></textarea>

        <!-- NOI DUNG -->

        <label for="inline-editor">
            Nội dung bài viết *
        </label>

        <p class="field-note">
            Nhập văn bản và nhấn Ctrl + V để dán ảnh.
            Sau mỗi ảnh sẽ có ô nhập mới bên dưới.
        </p>

        <!-- HAI NUT THAO TAC -->

        <div class="inline-editor-toolbar">

            <button
                type="button"
                id="insert-inline-image"
                class="btn-small"
            >
                + Chèn ảnh từ máy tính
            </button>

            <button
                type="button"
                id="paste-inline-image"
                class="btn-small"
            >
                Dán ảnh từ clipboard
            </button>

            <!-- AN HOAN TOAN INPUT CHON FILE -->

            <input
                type="file"
                id="inline-image-picker"
                accept=".jpg,.jpeg,.png,.webp"
                multiple
                hidden
                style="display:none !important"
                tabindex="-1"
                aria-hidden="true"
            >

        </div>

        <!-- VUNG SOAN THAO -->

        <div
            id="inline-editor"
            class="mp-editor-holder"
            style="display:none"
            aria-label="Nội dung bài viết"
        ></div>

        <!-- DU PHONG NEU JAVASCRIPT BI LOI -->

        <textarea
            name="content"
            id="content"
            rows="12"
            placeholder="Nhập nội dung bài viết..."
        ><?= e((string) $post['content']) ?></textarea>

        <!-- CHI HIEN KHI CO LOI HOAC THANH CONG -->

        <p
            id="inline-image-status"
            class="field-note"
            aria-live="polite"
        ></p>

        <!-- ANH DAI DIEN -->

        <label for="thumbnail">
            Ảnh đại diện bài viết
        </label>

        <?php if (!empty($post['thumbnail'])): ?>

            <div class="thumbnail-preview">

                <p>Ảnh hiện tại:</p>

                <img
                    src="../<?= e(
                        (string) $post['thumbnail']
                    ) ?>"
                    alt="Ảnh đại diện hiện tại"
                >

            </div>

        <?php endif; ?>

        <input
            type="file"
            name="thumbnail"
            id="thumbnail"
            accept=".jpg,.jpeg,.png,.webp"
        >

        <p class="field-note">
            Ảnh đại diện chỉ có 1 tấm, tối đa 2 MB.
            Ảnh trong nội dung tối đa 10 tấm.
        </p>

        <!-- TRANG THAI -->

        <label for="status">
            Trạng thái bài viết
        </label>

        <select
            id="status"
            name="status"
        >

            <option
                value="draft"
                <?= $post['status'] === 'draft'
                    ? 'selected'
                    : '' ?>
            >
                Bản nháp
            </option>

            <option
                value="published"
                <?= $post['status'] === 'published'
                    ? 'selected'
                    : '' ?>
            >
                Xuất bản
            </option>

        </select>

        <!-- LUU -->

        <button
            type="submit"
            id="save-post"
            class="btn-primary"
        >
            <?= $isEdit
                ? 'Lưu thay đổi'
                : 'Lưu bài viết' ?>
        </button>

    </form>

</main>

<!-- TU DONG TAO SLUG -->

<script>
document.addEventListener('DOMContentLoaded', () => {

    const title = document.getElementById('title');
    const slug = document.getElementById('slug');

    let manuallyEdited = slug.value.trim() !== '';

    function makeSlug(value) {
        return value
            .toLowerCase()
            .replace(/đ/g, 'd')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    title.addEventListener('input', () => {

        if (!manuallyEdited) {
            slug.value = makeSlug(title.value);
        }

    });

    slug.addEventListener('focus', () => {

        if (!slug.value.trim()) {
            slug.value = makeSlug(title.value);
        }

    });

    slug.addEventListener('input', () => {

        manuallyEdited = slug.value.trim() !== '';

    });

});
</script>

<!-- CHI TAI MOT FILE JAVASCRIPT EDITOR -->

<script src="../assets/js/article-editor.js?v=12"></script>

</body>
</html>
