
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('ليس لديك صلاحية لإضافة مستخدمين.');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !is_string($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('طلب غير صالح. ارجع إلى النموذج وحاول مرة أخرى.');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($fullName === '' || $username === '' || $password === '') {
        $message = 'يرجى إكمال جميع الحقول المطلوبة.';
    } elseif (mb_strlen($fullName) > 150) {
        $message = 'الاسم الكامل طويل جدًا.';
    } elseif (!preg_match('/\A[a-zA-Z0-9_.-]{3,100}\z/', $username)) {
        $message = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل، وبالإنجليزية والأرقام فقط، ويمكن استخدام النقطة والشرطة.';
    } elseif (strlen($password) < 12) {
        $message = 'كلمة المرور يجب ألا تقل عن 12 حرفًا.';
    } elseif (!in_array($role, ['admin', 'staff'], true)) {
        $message = 'نوع الحساب غير صحيح.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id FROM users WHERE username = :username LIMIT 1'
            );
            $stmt->execute(['username' => $username]);

            if ($stmt->fetch()) {
                $message = 'اسم المستخدم موجود بالفعل. اختر اسمًا آخر.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password, full_name, role)
                     VALUES (:username, :password, :full_name, :role)'
                );

                $stmt->execute([
                    'username' => $username,
                    'password' => $passwordHash,
                    'full_name' => $fullName,
                    'role' => $role
                ]);

                $message = 'تم إنشاء المستخدم بنجاح.';
                $messageType = 'success';
            }
        } catch (PDOException $e) {
            $message = 'تعذر إنشاء الحساب. تحقق من البيانات وحاول مرة أخرى.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إضافة مستخدم | أملاك العقارية</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 20px;
            font-family: Tahoma, Arial, sans-serif;
            background: #f4f6f3;
            color: #183c2b;
        }

        .container {
            max-width: 560px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 15px #00000012;
        }

        h1 { color: #17452f; }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
        }

        input, select, button {
            width: 100%;
            padding: 12px;
            font: inherit;
            border-radius: 7px;
        }

        input, select {
            border: 1px solid #ccc;
            background: white;
        }

        button {
            margin-top: 20px;
            border: 0;
            background: #17452f;
            color: white;
            cursor: pointer;
        }

        .message {
            padding: 12px;
            border-radius: 7px;
            margin: 15px 0;
            overflow-wrap: anywhere;
        }

        .success { background: #e7f7ec; color: #176534; }
        .error { background: #fff0f0; color: #a12626; }

        .back {
            display: inline-block;
            margin-top: 18px;
            color: #17452f;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>إضافة مستخدم جديد</h1>
    <p>أنشئ حسابًا جديدًا للوصول إلى النظام.</p>

    <?php if ($message !== ''): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <label for="full_name">الاسم الكامل</label>
        <input id="full_name" name="full_name" maxlength="150"
               required value="<?= htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="username">اسم المستخدم بالإنجليزية</label>
        <input id="username" name="username" maxlength="100"
               pattern="[a-zA-Z0-9_.-]{3,100}" required
               value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        } elseif (strlen($password) < 8) {
    $message = 'كلمة المرور يجب ألا تقل عن 8 أحرف أو أرقام.';
        <input id="password" name="password" type="password"
              minlength="8" autocomplete="new-password" required>

        <label for="role">صلاحية الحساب</label>
        <select id="role" name="role" required>
            <option value="staff">موظف</option>
            <option value="admin">مدير</option>
        </select>

        <button type="submit">إنشاء المستخدم</button>
    </form>

    <a class="back" href="dashboard.php">العودة إلى لوحة الإدارة</a>
</div>
</body>
</html>
