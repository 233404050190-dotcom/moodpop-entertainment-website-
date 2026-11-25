<?php
declare(strict_types=1);

require __DIR__ . '/config/database.php';

$sql = "SELECT name, slug
        FROM categories
        ORDER BY id ASC";

$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kiểm tra kết nối MoodPop</title>
</head>
<body>

<h1>Kết nối MySQL thành công!</h1>

<h2>Danh sách chuyên mục:</h2>

<ul>
    <?php foreach ($categories as $category): ?>
        <li>
            <?= htmlspecialchars(
                $category['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </li>
    <?php endforeach; ?>
</ul>

</body>
</html>
