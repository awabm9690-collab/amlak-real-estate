
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, username, password, full_name, role
             FROM users
             WHERE username = :username
             LIMIT 1"
        );

        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $user &&
            password_verify($password, $user['password'])
        ) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول الإدارة | أملاك العقارية</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f2f4ef;
            color: #183c30;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 25px #00000012;
        }

        h1 {
            color: #123b2e;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #66736b;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 15px 0 7px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd4cd;
            border-radius: 6px;
            font-size: 16px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: #123b2e;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1c5942;
        }

        .error {
            background: #fff0f0;
            color: #a32626;
            padding: 12px;
            border-radius: 6px;
        }

        .home {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #24533e;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="login-box">
    <h1>أملاك العقارية</h1>
    <p class="subtitle">تسجيل دخول الإدارة</p>

    <?php if ($error !== ''): ?>
        <p class="error"><?= htmlspecialchars(
            $error, ENT_QUOTES, 'UTF-8'
        ) ?></p>
    <?php endif; ?>

    <form method="post" action="">
        <label for="username">اسم المستخدم</label>
        <input
            type="text"
            id="username"
            name="username"
            autocomplete="username"
            required
        >

        <label for="password">كلمة المرور</label>
        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        >

        <button type="submit">تسجيل الدخول</button>
    </form>

    <a class="home" href="../index.php">العودة إلى الموقع</a>
</div>

</body>
</html>
