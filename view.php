<?php
include 'db_connect.php';

// معالجة البحث
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $stmt = $pdo->prepare("SELECT * FROM friends_info WHERE name LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM friends_info ORDER BY created_at DESC");
}
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض المعلومات - معرض الرسومات</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>🎨 عرض معلومات الصديقات</h1>
    </header>
    
    <main class="container">
        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="ابحث بالاسم..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">بحث</button>
            </form>
        </div>

        <div class="gallery">
            <?php if (count($friends) > 0): ?>
                <?php foreach ($friends as $friend): ?>
                    <div class="friend-card">
                        <?php if (!empty($friend['photo_path'])): ?>
                            <img src="<?php echo htmlspecialchars($friend['photo_path']); ?>" alt="<?php echo htmlspecialchars($friend['name']); ?>">
                        <?php else: ?>
                            <div class="no-image">لا توجد صورة</div>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($friend['name']); ?></h3>
                        <div class="details">
                            <p><strong>لون الشعر:</strong> <?php echo htmlspecialchars($friend['hair_color']); ?></p>
                            <p><strong>لون العيون:</strong> <?php echo htmlspecialchars($friend['eye_color']); ?></p>
                            <p><strong>اللون المفضل:</strong> <span class="color-box" style="background-color: <?php echo htmlspecialchars($friend['favorite_color']); ?>"></span></p>
                            <p><strong>لون البشرة:</strong> <?php echo htmlspecialchars($friend['skin_tone']); ?></p>
                            <p><strong>تسريحة الشعر:</strong> <?php echo htmlspecialchars($friend['hair_style']); ?></p>
                            <p><strong>اللباس:</strong> <?php echo htmlspecialchars($friend['clothing']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data">لا توجد بيانات</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>