<?php
$name = isset($_GET['name']) ? $_GET['name'] : '';
if (empty($name)) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدخال المعلومات - <?php echo htmlspecialchars($name); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>🎨 معلومات <?php echo htmlspecialchars($name); ?></h1>
    </header>
    
    <main class="container">
        <form id="friendForm" enctype="multipart/form-data" action="save_info.php" method="POST">
            <input type="hidden" name="friend_name" value="<?php echo htmlspecialchars($name); ?>">
            
            <div class="form-group">
                <label>لون الشعر:</label>
                <input type="text" name="hair_color" required>
            </div>
            
            <div class="form-group">
                <label>لون العيون:</label>
                <input type="text" name="eye_color" required>
            </div>
            
            <div class="form-group">
                <label>لونك المفضل:</label>
                <input type="color" name="favorite_color" required>
            </div>
            
            <div class="form-group">
                <label>لون البشرة:</label>
                <input type="text" name="skin_tone" required>
            </div>
            
            <div class="form-group">
                <label>وصف تسريحة الشعر:</label>
                <textarea name="hair_style" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label>وصف اللباس:</label>
                <textarea name="clothing" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label>رفع صورتك:</label>
                <input type="file" name="photo" accept="image/*" required>
            </div>
            
            <button type="submit">حفظ المعلومات</button>
        </form>
    </main>

    <script src="script.js"></script>
</body>
</html>