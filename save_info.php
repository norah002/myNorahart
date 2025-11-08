<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $friend_name = $_POST['friend_name'];
    $hair_color = $_POST['hair_color'];
    $eye_color = $_POST['eye_color'];
    $favorite_color = $_POST['favorite_color'];
    $skin_tone = $_POST['skin_tone'];
    $hair_style = $_POST['hair_style'];
    $clothing = $_POST['clothing'];
    
    // إنشاء مجلد لحفظ الصور إذا لم يكن موجوداً
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // معالجة رفع الصورة
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_filename = $friend_name . '_' . time() . '.' . $file_extension;
        $photo_path = $upload_dir . $new_filename;
        
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            die('خطأ في رفع الصورة');
        }
    }
    
    // حفظ المعلومات في قاعدة البيانات
    $stmt = $pdo->prepare("INSERT INTO friends_info (name, hair_color, eye_color, favorite_color, skin_tone, hair_style, clothing, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$friend_name, $hair_color, $eye_color, $favorite_color, $skin_tone, $hair_style, $clothing, $photo_path]);
    
    header("Location: view.php");
    exit;
}
?>