<?php
declare(strict_types=1);

require __DIR__ . '/../config/auth.php';
require_admin();

require __DIR__ . '/../config/database.php';

// Ham ma hoa khi hien thi HTML
function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Tu khoa tim kiem
$q = $_GET['q'] ?? '';
$q = is_string($q) ? trim($q) : '';
$q = substr($q, 0, 150);

// Loc trang thai
$status = $_GET['status'] ?? 'all';

if (!is_string($status) ||
    !in_array($status, ['all', 'draft', 'published'], true)
) {
    $status = 'all';
}

// So trang
$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);

$page = ($page !== false && $page !== null && $page > 0)
    ? $page : 1;

$perPage = 8;

// Xay dung dieu kien tim kiem
$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(p.title LIKE :q1
                 OR p.excerpt LIKE :q2)";

    $params[':q1'] = '%' . $q . '%';
    $params[':q2'] = '%' . $q . '%';
}

if ($status !== 'all') {
    $where[] = "p.status = :status";
    $params[':status'] = $status;
}

$whereSQL = $where
    ? ' WHERE ' . implode(' AND ', $where)
    : '';

// Dem so bai viet
$countSQL = "SELECT COUNT(*)
             FROM posts p" . $whereSQL;

$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);

$total = (int) $countStmt->fetchColumn();

$totalPages = max(
    1,
    (int) ceil($total / $perPage)
);

$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Lay danh sach bai viet
$sql = "SELECT
            p.id,
            p.title,
            p.slug,
            p.thumbnail,
            p.status,
            p.created_at,
            c.name AS category_name,
            u.display_name AS author_name
        FROM posts p
        JOIN categories c
            ON p.category_id = c.id
        JOIN users u
            ON p.author_id = u.id"
        . $whereSQL .
       " ORDER BY p.id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$posts = $stmt->fetchAll();

// Thong bao ket qua
$notice = $_GET['notice'] ?? '';
$messages = [
    'created' => 'Thêm bài viết thành công!',
    'saved' => 'Cập nhật bài viết thành công!',
    'deleted' => 'Xóa bài viết thành công!'
];

$message = is_string($notice)
    ? ($messages[$notice] ?? '')
    : '';

function page_url(
    int $number,
    string $q,
    string $status
): string {
    return 'posts.php?' . http_build_query([
        'q' => $q,
        'status' => $status,
        'page' => $number
    ]);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Quản lý bài viết - MoodPop</title>

    <link rel="stylesheet"
          href="../assets/css/admin.css">
</head>

<body class="dashboard-page">


<header class="admin-header">

    <div class="posts-brand">

        <a href="dashboard.php">
           
<img
    src="../assets/image/logo-moodpop.png"
    alt="Logo MoodPop"
    class="posts-logo"
    width="155"
    style="width:155px; max-width:155px; height:auto; display:block;"
>

        </a>

        <span class="posts-title">
            Quản lý bài viết
        </span>

    </div>

    <a href="dashboard.php" class="admin-link">
        Về Dashboard
    </a>

</header>


<main class="dashboard">

    <div class="page-heading">
        <div>
            <h1>Danh sách bài viết</h1>
            <p>Quản lý nội dung tin tức MoodPop.</p>
        </div>

        <a href="post_form.php" class="admin-link">
            + Thêm bài viết
        </a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert-success" role="status">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <!-- Tim kiem va loc -->
    <form method="GET" class="filter-form">

        <input
            type="search"
            name="q"
            placeholder="Tìm theo tiêu đề..."
            value="<?= e($q) ?>"
        >

        <select name="status">
            <option value="all"
                <?= $status === 'all' ? 'selected' : '' ?>>
                Tất cả
            </option>

            <option value="draft"
                <?= $status === 'draft' ? 'selected' : '' ?>>
                Bản nháp
            </option>

            <option value="published"
                <?= $status === 'published' ? 'selected' : '' ?>>
                Đã xuất bản
            </option>
        </select>

        <button type="submit" class="btn-small">
            Tìm kiếm
        </button>

    </form>

    <p>Tìm thấy <?= $total ?> bài viết.</p>

    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Chuyên mục</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($posts as $index => $post): ?>
                <tr>
                    
<td>
    <?= $offset + $index + 1 ?>
</td>

                    
<td>
    <?php if (!empty($post['thumbnail'])): ?>

        <img
            src="../<?= e($post['thumbnail']) ?>"
            alt="Ảnh đại diện bài viết"
            class="admin-thumbnail"
        >

    <?php else: ?>

        <div class="admin-thumbnail-empty">
            Chưa có ảnh
        </div>

    <?php endif; ?>
</td>


                    <td><?= e($post['title']) ?></td>

                    <td>
                        <?= e($post['category_name']) ?>
                    </td>

                    <td>
                        <?php if ($post['status'] === 'published'): ?>
                            <span class="status-published">
                                Đã xuất bản
                            </span>
                        <?php else: ?>
                            <span class="status-draft">
                                Bản nháp
                            </span>
                        <?php endif; ?>
                    </td>

                    <td><?= e($post['created_at']) ?></td>

                    <td>
                        <div class="table-actions">

                            <a
                                href="post_form.php?id=<?= (int) $post['id'] ?>"
                                class="edit-link"
                            >
                                Sửa
                            </a>

                            <form
                                method="POST"
                                action="post_delete.php"
                                onsubmit="return confirm('Xóa bài viết này và toàn bộ bình luận liên quan?');"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $post['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="delete-link"
                                >
                                    Xóa
                                </button>
                            </form>

                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$posts): ?>
                <tr>
                    <td colspan="7" class="empty-cell">
                        Chưa có bài viết phù hợp.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phan trang -->
    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Phân trang">

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a
                    href="<?= e(page_url($i, $q, $status)) ?>"
                    class="<?= $page === $i ? 'current' : '' ?>"
                >
                    <?= $i ?>
                </a>
            <?php endfor; ?>

        </nav>
    <?php endif; ?>

</main>

</body>
</html>
