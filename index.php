<?php
session_start();

// ========== إعدادات الموقع ==========
$site_title = "معرض الرسومات الشخصية";
$colors = [
    'primary' => '#2c3e50',     // كحلي
    'secondary' => '#d4af37',   // ذهبي
    'accent' => '#34495e',      // كحلي غامق
    'background' => '#f8f9fa',
    'text' => '#2c3e50'
];

// ========== معالجة النماذج ==========
$message = '';
$error = '';

// معالجة تسجيل الدخول للإدارة
if (isset($_POST['admin_login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin'] = true;
        $_SESSION['username'] = 'admin';
        header("Location: ".$_SERVER['PHP_SELF']."?view=admin");
        exit;
    } else {
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة";
    }
}

// معالجة تسجيل الطالبات الجدد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $wants_drawing = isset($_POST['wants_drawing']) ? 1 : 0;
    
    // تسجيل الطالبة الجديدة
    $student_data = [
        'name' => $name,
        'phone' => $phone,
        'wants_drawing' => $wants_drawing,
        'drawing_completed' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // حفظ في ملف JSON
    $data_file = 'students_data.json';
    $existing_data = [];
    
    if (file_exists($data_file)) {
        $existing_data = json_decode(file_get_contents($data_file), true) ?? [];
    }
    
    // إضافة ID للطالبة
    $student_id = count($existing_data) + 1;
    $student_data['id'] = $student_id;
    
    $existing_data[] = $student_data;
    
    if (file_put_contents($data_file, json_encode($existing_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        if ($wants_drawing) {
            // إذا كانت تريد رسمة، نوجهها مباشرة لنموذج المعلومات
            $_SESSION['student_id'] = $student_id;
            $_SESSION['student_name'] = $name;
            $message = "تم التسجيل بنجاح! يرجى إكمال معلومات الرسمة";
            header("Location: ".$_SERVER['PHP_SELF']."?view=drawing_info&id=".$student_id);
            exit;
        } else {
            $message = "تم التسجيل بنجاح! يمكنك متابعة المعرض";
            header("Location: ".$_SERVER['PHP_SELF']."?view=gallery");
            exit;
        }
    } else {
        $error = "حدث خطأ في التسجيل";
    }
}

// معالجة معلومات الرسمة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_drawing_info'])) {
    $student_id = $_POST['student_id'];
    $hair_color = $_POST['hair_color'];
    $eye_color = $_POST['eye_color'];
    $favorite_color = $_POST['favorite_color'];
    $skin_tone = $_POST['skin_tone'];
    $hair_style = $_POST['hair_style'];
    $clothing = $_POST['clothing'];
    $additional_notes = $_POST['additional_notes'];
    
    // معالجة رفع الصورة
    $photo_path = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_filename = 'student_' . $student_id . '_' . time() . '.' . $file_extension;
        $photo_path = $upload_dir . $new_filename;
        
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            $error = "خطأ في رفع الصورة";
        }
    }
    
    // تحديث المعلومات في ملف JSON
    $data_file = 'students_data.json';
    $students_data = [];
    
    if (file_exists($data_file)) {
        $students_data = json_decode(file_get_contents($data_file), true) ?? [];
    }
    
    // البحث عن الطالبة وتحديث معلوماتها
    foreach ($students_data as &$student) {
        if ($student['id'] == $student_id) {
            $student['hair_color'] = $hair_color;
            $student['eye_color'] = $eye_color;
            $student['favorite_color'] = $favorite_color;
            $student['skin_tone'] = $skin_tone;
            $student['hair_style'] = $hair_style;
            $student['clothing'] = $clothing;
            $student['additional_notes'] = $additional_notes;
            $student['photo_path'] = $photo_path;
            $student['info_completed'] = 1;
            break;
        }
    }
    
    if (file_put_contents($data_file, json_encode($students_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        $message = "تم حفظ معلومات الرسمة بنجاح! سيتم إعلامك عند اكتمال الرسمة";
        header("Location: ".$_SERVER['PHP_SELF']."?view=success&id=".$student_id);
        exit;
    } else {
        $error = "حدث خطأ في حفظ المعلومات";
    }
}

// معالجة تحديث حالة الرسمة من قبل الإدارة
if (isset($_POST['update_drawing_status'])) {
    $student_id = $_POST['student_id'];
    $drawing_completed = $_POST['drawing_completed'];
    $drawing_path = '';
    
    // معالجة رفع ملف الرسمة
    if (isset($_FILES['drawing_file']) && $_FILES['drawing_file']['error'] === 0) {
        $upload_dir = 'drawings/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['drawing_file']['name'], PATHINFO_EXTENSION);
        $new_filename = 'drawing_' . $student_id . '.' . $file_extension;
        $drawing_path = $upload_dir . $new_filename;
        
        if (!move_uploaded_file($_FILES['drawing_file']['tmp_name'], $drawing_path)) {
            $error = "خطأ في رفع ملف الرسمة";
        }
    }
    
    // تحديث المعلومات في ملف JSON
    $data_file = 'students_data.json';
    $students_data = [];
    
    if (file_exists($data_file)) {
        $students_data = json_decode(file_get_contents($data_file), true) ?? [];
    }
    
    foreach ($students_data as &$student) {
        if ($student['id'] == $student_id) {
            $student['drawing_completed'] = $drawing_completed;
            $student['drawing_path'] = $drawing_path;
            break;
        }
    }
    
    if (file_put_contents($data_file, json_encode($students_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        $message = "تم تحديث حالة الرسمة بنجاح";
    } else {
        $error = "حدث خطأ في تحديث الحالة";
    }
}

// تسجيل الخروج
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// جلب البيانات من ملف JSON
$students_data = [];
$data_file = 'students_data.json';
if (file_exists($data_file)) {
    $students_data = json_decode(file_get_contents($data_file), true) ?? [];
}

// معالجة البحث
$search = '';
$friends = [];
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    foreach ($students_data as $student) {
        if (isset($student['wants_drawing']) && $student['wants_drawing'] && 
            stripos($student['name'], $search) !== false) {
            $friends[] = $student;
        }
    }
} elseif (isset($_GET['view']) && $_GET['view'] === 'gallery') {
    foreach ($students_data as $student) {
        if (isset($student['wants_drawing']) && $student['wants_drawing']) {
            $friends[] = $student;
        }
    }
}

// جلب معلومات طالبة محددة
$current_student = [];
if (isset($_GET['id'])) {
    foreach ($students_data as $student) {
        if ($student['id'] == $_GET['id']) {
            $current_student = $student;
            break;
        }
    }
}

// جلب جميع الطالبات للإدارة
$all_students = $students_data;

// تحديد الصفحة الحالية
$current_view = isset($_GET['view']) ? $_GET['view'] : 'home';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <style>
        /* ========== CSS Styles ========== */
        :root {
            --primary: <?php echo $colors['primary']; ?>;
            --secondary: <?php echo $colors['secondary']; ?>;
            --accent: <?php echo $colors['accent']; ?>;
            --background: <?php echo $colors['background']; ?>;
            --text: <?php echo $colors['text']; ?>;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--background);
            min-height: 100vh;
            line-height: 1.6;
            color: var(--text);
        }

        .main-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }

        .main-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 40%, rgba(212, 175, 55, 0.1) 100%);
            pointer-events: none;
        }

        .main-header h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            position: relative;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .main-header .subtitle {
            font-size: 1.1rem;
            opacity: 0.95;
            position: relative;
            font-weight: 300;
        }

        .graduation-badge {
            background: var(--secondary);
            color: var(--primary);
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: bold;
            margin-top: 1rem;
            display: inline-block;
            box-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .nav-container {
            background: white;
            padding: 1rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e8e8e8;
        }

        .navigation {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-btn {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 600;
            border: 2px solid var(--primary);
        }

        .nav-btn:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 62, 80, 0.2);
        }

        .nav-btn.active {
            background: var(--secondary);
            border-color: var(--secondary);
            color: var(--primary);
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.1);
            margin-bottom: 1.5rem;
            border: 1px solid #e8e8e8;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(44, 62, 80, 0.15);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--accent);
            font-size: 1rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary);
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }

        .checkbox-group input {
            width: auto;
            transform: scale(1.2);
        }

        .checkbox-group label {
            margin: 0;
            font-weight: 500;
        }

        .btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 62, 80, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary) 0%, #e6c34e 100%);
        }

        .message {
            background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
            color: #155724;
            padding: 1.25rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            text-align: center;
            border: 2px solid #c3e6cb;
            font-weight: 500;
        }

        .error {
            background: linear-gradient(135deg, #ffebee 0%, #f8d7da 100%);
            color: #721c24;
            padding: 1.25rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            text-align: center;
            border: 2px solid #f5c6cb;
            font-weight: 500;
        }

        .search-box {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.1);
            border: 1px solid #e8e8e8;
        }

        .search-box form {
            display: flex;
            gap: 12px;
        }

        .search-box input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }

        .art-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e8e8e8;
            position: relative;
        }

        .art-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(44, 62, 80, 0.2);
        }

        .art-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .art-card:hover img {
            transform: scale(1.05);
        }

        .no-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: 500;
        }

        .art-card-content {
            padding: 1.5rem;
        }

        .art-card h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.4rem;
            text-align: center;
            font-weight: 700;
        }

        .art-details {
            color: #555;
        }

        .art-details p {
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .art-details strong {
            color: var(--accent);
            font-weight: 600;
        }

        .color-box {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            vertical-align: middle;
            margin-right: 8px;
            border: 2px solid #e0e0e0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.1);
        }

        .admin-table th,
        .admin-table td {
            padding: 1.25rem;
            text-align: right;
            border-bottom: 1px solid #e8e8e8;
        }

        .admin-table th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }

        .admin-table tr:hover {
            background: #f8f9fa;
        }

        .status-complete {
            color: #28a745;
            font-weight: 600;
            background: #e8f5e8;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
        }

        .status-incomplete {
            color: #dc3545;
            font-weight: 600;
            background: #ffebee;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
        }

        .login-container {
            max-width: 450px;
            margin: 2rem auto;
        }

        .drawing-status {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 700;
            display: inline-block;
            margin: 1rem 0;
            text-align: center;
            width: 100%;
        }

        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border: 2px solid #ffeaa7;
        }

        .status-completed {
            background: linear-gradient(135deg, #d1edff 0%, #a8d8ff 100%);
            color: #004085;
            border: 2px solid #a8d8ff;
        }

        .student-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            border-right: 4px solid var(--primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .artwork-display {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.1);
            border: 2px solid #e8e8e8;
        }

        .artwork-placeholder {
            width: 100%;
            height: 300px;
            background: linear-gradient(45deg, #f5f5f5 25%, transparent 25%), 
                        linear-gradient(-45deg, #f5f5f5 25%, transparent 25%), 
                        linear-gradient(45deg, transparent 75%, #f5f5f5 75%), 
                        linear-gradient(-45deg, transparent 75%, #f5f5f5 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            border: 2px dashed #bdc3c7;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            font-size: 1.2rem;
            margin: 1.5rem 0;
            transition: all 0.3s ease;
        }

        .artwork-placeholder:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .completed-drawing {
            border: 3px solid var(--secondary);
            box-shadow: 0 0 25px rgba(212, 175, 55, 0.4);
        }

        .completed-drawing .artwork-placeholder {
            border-color: var(--secondary);
            color: var(--secondary);
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
        }

        .welcome-steps {
            display: flex;
            justify-content: space-around;
            margin: 3rem 0;
            text-align: center;
            gap: 1.5rem;
        }

        .step {
            flex: 1;
            padding: 2rem 1.5rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.1);
            border: 2px solid #e8e8e8;
            transition: all 0.3s ease;
        }

        .step:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(44, 62, 80, 0.15);
            border-color: var(--primary);
        }

        .step-number {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-weight: bold;
            font-size: 1.3rem;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.3);
        }

        /* ========== Responsive Design ========== */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .gallery {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .search-box form {
                flex-direction: column;
            }
            
            .navigation {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-btn {
                width: 100%;
                text-align: center;
            }
            
            .main-header h1 {
                font-size: 1.6rem;
            }
            
            .art-card img {
                height: 200px;
            }
            
            .admin-table {
                font-size: 0.9rem;
            }
            
            .admin-table th,
            .admin-table td {
                padding: 0.75rem 0.5rem;
            }
            
            .welcome-steps {
                flex-direction: column;
                gap: 1rem;
            }
            
            .step {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0.75rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .main-header {
                padding: 1.5rem 1rem;
            }
            
            .main-header h1 {
                font-size: 1.4rem;
            }
            
            .btn {
                padding: 0.875rem 1.5rem;
                font-size: 1rem;
            }
        }

        .admin-panel {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 1px solid #e8e8e8;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--accent);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <h1>سر التعاون</h1>
       
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- شريط التنقل -->
        <div class="nav-container">
            <div class="navigation">
                <?php if (!isset($_SESSION['admin'])): ?>
                    <a href="?view=home" class="nav-btn <?php echo $current_view === 'home' ? 'active' : ''; ?>">الرئيسية</a>
                    <a href="?view=register" class="nav-btn <?php echo $current_view === 'register' ? 'active' : ''; ?>">إنشاء حساب</a>
                    <a href="?view=gallery" class="nav-btn <?php echo $current_view === 'gallery' ? 'active' : ''; ?>">المعرض</a>
                    <a href="?view=admin_login" class="nav-btn <?php echo $current_view === 'admin_login' ? 'active' : ''; ?>">دخول الإدارة</a>
                <?php else: ?>
                    <a href="?view=admin" class="nav-btn <?php echo $current_view === 'admin' ? 'active' : ''; ?>">لوحة الإدارة</a>
                    <a href="?view=gallery" class="nav-btn <?php echo $current_view === 'gallery' ? 'active' : ''; ?>">المعرض</a>
                    <a href="?logout=1" class="nav-btn">تسجيل الخروج</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- محتوى الصفحات -->
        <?php if ($current_view === 'home'): ?>
            <!-- الصفحة الرئيسية -->
            <div class="card">
                <h2 style="text-align: center; color: var(--primary); margin-bottom: 2rem; font-size: 2rem;">
                    أهلاً وسهلاً في معرض  ! 🎨
                </h2>
                <p style="text-align: center; font-size: 1.2rem; margin-bottom: 2rem; line-height: 1.8;">
                    فكرت الموقع اني اوفر وقت في جمع معلومات كل وحده فيكم وتكون محفوظه واقدر اشوفه واعدله وحتى يمديكم على راحتكم تحطون الي تبونه وحبيت اني سويها لكم مفاجاه بس احتاج معلوماتكم 
                </p>
                
                <div class="welcome-steps">
                    <div class="step">
                        <div class="step-number">١</div>
                        <h3 style="color: var(--primary); margin-bottom: 1rem;">سجلي معلوماتك</h3>
                        <p>أنشئي حساب جديد واختاري إذا كنتِ تبياني أرسم لكِ ولا لا</p>
                    </div>
                    <div class="step">
                        <div class="step-number">٢</div>
                        <h3 style="color: var(--primary); margin-bottom: 1rem;">اكتبي المواصفات</h3>
                        <p>إذا قررتي إنك تبيين رسمة، حطي كل المعلومات اللي تساعدني</p>
                    </div>
                    <div class="step">
                        <div class="step-number">٣</div>
                        <h3 style="color: var(--primary); margin-bottom: 1rem;">استلمي الرسمة</h3>
                        <p>برسلك رسالة على الجوال لما أكمل رسمتك وتقدرين تشوفينها في المعرض</p>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 3rem;">
                    <a href="?view=register" class="btn" style="font-size: 1.3rem; padding: 1.25rem 3rem;">يلا نبدأ! 🚀</a>
                </div>
            </div>

        <?php elseif ($current_view === 'register'): ?>
            <!-- صفحة إنشاء حساب -->
            <div class="card login-container">
                <h2 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">أنشئي حسابك</h2>
                <p style="text-align: center; color: #666; margin-bottom: 2rem;">
                    سجلي معلوماتك الأساسية عشان نبدأ. إذا كنتِ تبياني أرسم لكِ، راح ندخلك على صفحة معلومات الرسمة مباشرة!
                </p>
                <form method="POST">
                    <input type="hidden" name="register" value="1">
                    <div class="form-group">
                        <label for="name">اسمك الكريم:</label>
                        <input type="text" id="name" name="name" required placeholder="اكتبي اسمك هنا...">
                    </div>
                    <div class="form-group">
                        <label for="phone">رقم جوالك:</label>
                        <input type="tel" id="phone" name="phone" required placeholder="عشان نرسل لكِ إشعار لما نخلص الرسمة">
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="wants_drawing" name="wants_drawing" value="1" checked>
                            <label for="wants_drawing">أبي أطلب رسمة شخصية! 🎨</label>
                        </div>
                    </div>
                    <button type="submit" class="btn" style="width: 100%;">أكمل التسجيل</button>
                </form>
                <p style="text-align: center; margin-top: 2rem;">
                    <a href="?view=gallery" style="color: var(--primary); text-decoration: none; font-weight: 600;">
                        أو شوفي المعرض أول 👀
                    </a>
                </p>
            </div>

        <?php elseif ($current_view === 'drawing_info' && isset($current_student)): ?>
            <!-- صفحة إدخال معلومات الرسمة -->
            <div class="card">
                <h2 style="text-align: center; color: var(--primary); margin-bottom: 1.5rem;">
                    ياهلا <?php echo htmlspecialchars($current_student['name']); ?>! 🌟
                </h2>
                <p style="text-align: center; margin-bottom: 2rem; color: #666; font-size: 1.1rem;">
                    الحين حطينا المعلومات اللي راح تساعدني أرسملك رسمة تعبر عن شخصيتك!
                </p>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="save_drawing_info" value="1">
                    <input type="hidden" name="student_id" value="<?php echo $current_student['id']; ?>">
                    
                    <div class="dashboard-grid">
                        <div>
                            <h3 style="color: var(--primary); margin-bottom: 1.5rem; border-right: 4px solid var(--secondary); padding-right: 1rem;">معلوماتك الشخصية</h3>
                            
                            <div class="form-group">
                                <label>لون شعرك:</label>
                                <input type="text" name="hair_color" required placeholder="مثلاً: أسود، بني، أشقر، أصهب...">
                            </div>
                            
                            <div class="form-group">
                                <label>لون عيونك:</label>
                                <input type="text" name="eye_color" required placeholder="مثلاً: بني، أسود، أخضر، أزرق...">
                            </div>
                            
                            <div class="form-group">
                                <label>لونك المفضل:</label>
                                <input type="color" name="favorite_color" value="#2c3e50" required style="height: 60px; border-radius: 10px;">
                            </div>
                            
                            <div class="form-group">
                                <label>لون بشرتك:</label>
                                <input type="text" name="skin_tone" required placeholder="مثلاً: فاتح، قمحي، زيتوني، غامق...">
                            </div>
                            
                            <div class="form-group">
                                <label>صورتك (إذا تبين):</label>
                                <input type="file" name="photo" accept="image/*">
                                <small style="color: #666; display: block; margin-top: 0.5rem;">ما يلزم، بس إذا حطيته بيكون أحلى وأدق!</small>
                            </div>
                        </div>
                        
                        <div>
                            <h3 style="color: var(--primary); margin-bottom: 1.5rem; border-right: 4px solid var(--secondary); padding-right: 1rem;">تفاصيل الرسمة</h3>
                            
                            <div class="form-group">
                                <label>شكل الشعر اللي تبينه:</label>
                                <textarea name="hair_style" rows="3" required placeholder="مثلاً: طويل ومموج، قصير ومستقيم، كيرلي، ضفاير..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>اللبس اللي تحبينه:</label>
                                <textarea name="clothing" rows="3" required placeholder="مثلاً: فستان، جينس وتيشيرت، عباية، لبس تقليدي..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>أي إضافات أو أفكار ثانية:</label>
                                <textarea name="additional_notes" rows="3" placeholder="إذا عندك أي أفكار خاصة أو تفاصيل إضافية تبينها في الرسمة..."></textarea>
                            </div>
                            
                            <div style="background: #e8f4f8; padding: 1.5rem; border-radius: 12px; margin-top: 2rem;">
                                <h4 style="color: var(--accent); margin-bottom: 0.5rem;">💡 نصيحة:</h4>
                                <p style="color: #2c3e50; margin: 0; line-height: 1.6;">
                                    كل ما كانت المعلومات أدق، بتكون الرسمة أقرب لشخصيتك الحقيقية!
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%; margin-top: 2rem; font-size: 1.2rem;">
                        حفظ المعلومات وطلب الرسمة 🎨
                    </button>
                </form>
            </div>

        <?php elseif ($current_view === 'success' && isset($current_student)): ?>
            <!-- صفحة النجاح -->
            <div class="card" style="text-align: center;">
                <div style="font-size: 5rem; margin-bottom: 1.5rem;">🎉</div>
                <h2 style="color: var(--primary); margin-bottom: 1.5rem; font-size: 2rem;">ما شاء الله! تم بنجاح</h2>
                <p style="font-size: 1.3rem; margin-bottom: 1.5rem; line-height: 1.8;">
                    عزيزتي <strong><?php echo htmlspecialchars($current_student['name']); ?></strong>، 
                    تم حفظ معلوماتك وطلب الرسمة بنجاح! 
                </p>
                <p style="margin-bottom: 2rem; color: #666; font-size: 1.1rem; line-height: 1.7;">
                    راح أبدأ بالرسمة على طول وأبذل كل جهدي عشان تطلع زي ما تتمنين. 
                    أول ما أنتهي راح أرسل لكِ رسالة على الجوال وتقدرين تشوفينها في المعرض!
                </p>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 2rem;">
                    <a href="?view=gallery" class="btn">شوفي المعرض 🖼️</a>
                    <a href="?view=home" class="btn btn-secondary">الرئيسية 🏠</a>
                </div>
            </div>

        <?php elseif ($current_view === 'admin_login'): ?>
            <!-- صفحة تسجيل الدخول للإدارة -->
            <div class="card login-container">
                <h2 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">دخول الإدارة</h2>
                <p style="text-align: center; color: #666; margin-bottom: 2rem;">
                    هذي الصفحة خاصة بي (صاحبة المشروع) عشان أدار الطلبات والرسومات
                </p>
                <form method="POST">
                    <input type="hidden" name="admin_login" value="1">
                    <div class="form-group">
                        <label for="username">اسم المستخدم:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">كلمة المرور:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn" style="width: 100%;">دخول الإدارة</button>
                </form>
            </div>

        <?php elseif ($current_view === 'admin' && isset($_SESSION['admin'])): ?>
            <!-- لوحة الإدارة -->
            <div class="card">
                <h2 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">لوحة الإدارة - مشروع التخرج</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($all_students); ?></div>
                        <div class="stat-label">إجمالي المسجلات</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                                $drawing_requests = 0;
                                foreach ($all_students as $student) {
                                    if (isset($student['wants_drawing']) && $student['wants_drawing']) $drawing_requests++;
                                }
                                echo $drawing_requests;
                            ?>
                        </div>
                        <div class="stat-label">طلبات الرسم</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                                $completed_info = 0;
                                foreach ($all_students as $student) {
                                    if (isset($student['info_completed']) && $student['info_completed']) $completed_info++;
                                }
                                echo $completed_info;
                            ?>
                        </div>
                        <div class="stat-label">مكتملة المعلومات</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <?php 
                                $completed_drawings = 0;
                                foreach ($all_students as $student) {
                                    if (isset($student['drawing_completed']) && $student['drawing_completed']) $completed_drawings++;
                                }
                                echo $completed_drawings;
                            ?>
                        </div>
                        <div class="stat-label">الرسومات المكتملة</div>
                    </div>
                </div>
                
                <div class="card">
                    <h3 style="color: var(--primary); margin-bottom: 1.5rem;">قائمة الطالبات والطلبات</h3>
                    <div style="overflow-x: auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>رقم الجوال</th>
                                    <th>طلب رسمة</th>
                                    <th>المعلومات</th>
                                    <th>حالة الرسمة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['phone'] ?? 'غير محدد'); ?></td>
                                        <td>
                                            <?php echo (isset($student['wants_drawing']) && $student['wants_drawing']) ? '✅ نعم' : '❌ لا'; ?>
                                        </td>
                                        <td>
                                            <span class="<?php echo (isset($student['info_completed']) && $student['info_completed']) ? 'status-complete' : 'status-incomplete'; ?>">
                                                <?php echo (isset($student['info_completed']) && $student['info_completed']) ? 'مكتملة' : 'غير مكتملة'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="<?php echo (isset($student['drawing_completed']) && $student['drawing_completed']) ? 'status-complete' : 'status-incomplete'; ?>">
                                                <?php echo (isset($student['drawing_completed']) && $student['drawing_completed']) ? 'مكتملة' : 'قيد الانتظار'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?view=student_details&id=<?php echo $student['id']; ?>" class="btn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">عرض/تعديل</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($current_view === 'student_details' && isset($_SESSION['admin']) && isset($current_student)): ?>
            <!-- تفاصيل الطالبة في لوحة الإدارة -->
            <div class="card">
                <h2 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">
                    تفاصيل الطالبة: <?php echo htmlspecialchars($current_student['name']); ?>
                </h2>
                
                <div class="dashboard-grid">
                    <div>
                        <h3 style="color: var(--primary); margin-bottom: 1.5rem; border-right: 4px solid var(--secondary); padding-right: 1rem;">المعلومات الأساسية</h3>
                        
                        <div class="student-info-card">
                            <p><strong>📞 رقم الجوال:</strong> <?php echo htmlspecialchars($current_student['phone'] ?? 'غير محدد'); ?></p>
                            <p><strong>🎨 طلب رسمة:</strong> <?php echo (isset($current_student['wants_drawing']) && $current_student['wants_drawing']) ? '✅ نعم' : '❌ لا'; ?></p>
                            <p><strong>📅 تاريخ التسجيل:</strong> <?php echo date('Y-m-d', strtotime($current_student['created_at'])); ?></p>
                        </div>
                        
                        <?php if (isset($current_student['wants_drawing']) && $current_student['wants_drawing'] && isset($current_student['info_completed']) && $current_student['info_completed']): ?>
                            <div class="student-info-card">
                                <h4 style="color: var(--accent); margin-bottom: 1rem;">معلومات الرسمة:</h4>
                                <p><strong>👱‍♀️ لون الشعر:</strong> <?php echo htmlspecialchars($current_student['hair_color']); ?></p>
                                <p><strong>👁️ لون العيون:</strong> <?php echo htmlspecialchars($current_student['eye_color']); ?></p>
                                <p><strong>🎨 اللون المفضل:</strong> 
                                    <span class="color-box" style="background-color: <?php echo htmlspecialchars($current_student['favorite_color']); ?>"></span>
                                    <?php echo htmlspecialchars($current_student['favorite_color']); ?>
                                </p>
                                <p><strong>🌟 لون البشرة:</strong> <?php echo htmlspecialchars($current_student['skin_tone']); ?></p>
                                <p><strong>💇 تسريحة الشعر:</strong> <?php echo htmlspecialchars($current_student['hair_style']); ?></p>
                                <p><strong>👗 اللباس:</strong> <?php echo htmlspecialchars($current_student['clothing']); ?></p>
                                <?php if (!empty($current_student['additional_notes'])): ?>
                                    <p><strong>💬 ملاحظات إضافية:</strong> <?php echo htmlspecialchars($current_student['additional_notes']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <h3 style="color: var(--primary); margin-bottom: 1.5rem; border-right: 4px solid var(--secondary); padding-right: 1rem;">إدارة الرسمة</h3>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="update_drawing_status" value="1">
                            <input type="hidden" name="student_id" value="<?php echo $current_student['id']; ?>">
                            
                            <div class="form-group">
                                <label>حالة الرسمة:</label>
                                <select name="drawing_completed" required style="padding: 1rem; font-size: 1rem;">
                                    <option value="0" <?php echo (isset($current_student['drawing_completed']) && $current_student['drawing_completed'] == 0) ? 'selected' : ''; ?>>🟡 قيد العمل</option>
                                    <option value="1" <?php echo (isset($current_student['drawing_completed']) && $current_student['drawing_completed'] == 1) ? 'selected' : ''; ?>>✅ مكتملة</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>رفع ملف الرسمة النهائية:</label>
                                <input type="file" name="drawing_file" accept="image/*" style="padding: 1rem; border: 2px dashed #bdc3c7;">
                                <?php if (!empty($current_student['drawing_path'])): ?>
                                    <p style="color: #27ae60; margin-top: 0.5rem; font-weight: 600;">
                                        ✅ تم رفع رسمة مسبقاً: 
                                        <a href="<?php echo $current_student['drawing_path']; ?>" target="_blank" style="color: var(--primary);">عرض الرسمة</a>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">حفظ التغييرات</button>
                        </form>
                        
                        <?php if (isset($current_student['drawing_completed']) && $current_student['drawing_completed']): ?>
                            <div class="message" style="margin-top: 1.5rem;">
                                ✅ تم إرسال إشعار للطالبة باكتمال الرسمة
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($current_view === 'gallery'): ?>
            <!-- معرض الرسومات -->
            <div class="card">
                <h2 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">🎨 معرض الرسمات</h2>
                <p style="text-align: center; color: #666; margin-bottom: 2rem; font-size: 1.1rem;">
                    هذا المعرض يضم كل الرسمات الشخصية اللي طلبتها الصديقات. كل رسمة بتكون مبنية على المعلومات الشخصية لكل وحدة!
                </p>
                
                <div class="search-box">
                    <form method="GET">
                        <input type="hidden" name="view" value="gallery">
                        <input type="text" name="search" placeholder="ابحثي باسم الصديقة..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn">بحث</button>
                    </form>
                </div>

                <div class="gallery">
                    <?php if (count($friends) > 0): ?>
                        <?php foreach ($friends as $friend): ?>
                            <div class="art-card <?php echo (isset($friend['drawing_completed']) && $friend['drawing_completed']) ? 'completed-drawing' : ''; ?>">
                                <?php if (!empty($friend['photo_path']) && file_exists($friend['photo_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($friend['photo_path']); ?>" alt="<?php echo htmlspecialchars($friend['name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">🖼️ صورة <?php echo htmlspecialchars($friend['name']); ?></div>
                                <?php endif; ?>
                                
                                <div class="art-card-content">
                                    <h3><?php echo htmlspecialchars($friend['name']); ?></h3>
                                    
                                    <div class="drawing-status <?php echo (isset($friend['drawing_completed']) && $friend['drawing_completed']) ? 'status-completed' : 'status-pending'; ?>">
                                        <?php echo (isset($friend['drawing_completed']) && $friend['drawing_completed']) ? '✅ الرسمة مكتملة' : '🟡 جاري العمل على الرسمة'; ?>
                                    </div>
                                    
                                    <?php if (isset($friend['info_completed']) && $friend['info_completed']): ?>
                                        <div class="art-details">
                                            <p><strong>لون الشعر:</strong> <?php echo htmlspecialchars($friend['hair_color']); ?></p>
                                            <p><strong>لون العيون:</strong> <?php echo htmlspecialchars($friend['eye_color']); ?></p>
                                            <p><strong>اللون المفضل:</strong> 
                                                <span class="color-box" style="background-color: <?php echo htmlspecialchars($friend['favorite_color']); ?>"></span>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($friend['drawing_completed']) && $friend['drawing_completed'] && !empty($friend['drawing_path'])): ?>
                                        <div style="margin-top: 1.5rem;">
                                            <a href="<?php echo $friend['drawing_path']; ?>" target="_blank" class="btn" style="width: 100%;">👀 شوفي الرسمة النهائية</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="artwork-placeholder">
                                            <?php echo (isset($friend['drawing_completed']) && $friend['drawing_completed']) ? 'الرسمة جاهزة! 🎉' : 'جاري إنشاء الرسمة... ⏳'; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data" style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--accent); background: white; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <div style="font-size: 4rem; margin-bottom: 1rem;">😊</div>
                            <h3 style="color: var(--primary); margin-bottom: 1rem;">ما في رسومات لعرضها الحين</h3>
                            <p style="color: #666; margin-bottom: 2rem;"><?php echo $search ? 'ما حصلنا نتائج للبحث' : 'يمكنكِ تكوني أول وحدة تطلبين رسمة!'; ?></p>
                            <a href="?view=register" class="btn">اطلبي رسمة!</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- صفحة غير موجودة -->
            <div class="card" style="text-align: center;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🤔</div>
                <h2 style="color: var(--primary); margin-bottom: 1rem;">آسفة! ما حصلنا الصفحة اللي تبيها</h2>
                <p style="color: #666; margin-bottom: 2rem;">يمكن تكوني دخلت رابط خطأ أو الصفحة انتقلت</p>
                <a href="?view=home" class="btn">العودة للرئيسية</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // ========== JavaScript ==========
        document.addEventListener('DOMContentLoaded', function() {
            console.log('موقع معرض الرسومات جاهز!');
            
            // إضافة تأثيرات للكروت
            const cards = document.querySelectorAll('.art-card, .card, .step');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // تحسين تجربة المستخدم على الأجهزة المحمولة
            if (window.innerWidth <= 768) {
                document.body.style.fontSize = '15px';
            }
            
            // التحقق من صحة الملف قبل الرفع
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(fileInput => {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        if (file.size > maxSize) {
                            alert('حجم الملف كبير جداً. الحد الأقصى 5MB');
                            this.value = '';
                        }
                        
                        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        if (!validTypes.includes(file.type)) {
                            alert('نوع الملف غير مدعوم. يرجى رفع صورة (JPEG, PNG, GIF, WebP)');
                            this.value = '';
                        }
                    }
                });
            });
            
            // إضافة تأثيرات للزر عند الضغط
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });

            // تحسين تجربة النماذج
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = 'جاري المعالجة... ⏳';
                        submitBtn.disabled = true;
                    }
                });
            });
        });
    </script>
</body>
</html>