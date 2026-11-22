
<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("SELECT COUNT(*) FROM properties");
$totalProperties = $stmt->fetchColumn();

$stmt = $pdo->query(
    "SELECT COUNT(*) FROM properties WHERE status = 'available'"
);
$availableProperties = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم أملاك العقارية</title>
    <style>
        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f4f6f3;
            color: #183c2b;
        }
        header {
            background: #17452f;
            color: white;
            padding: 22px;
        }
        main {
            max-width: 900px;
            margin: 35px auto;
            padding: 20px;
        }
        .cards {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            flex: 1;
            min-width: 180px;
            box-shadow: 0 3px 12px #00000010;
        }
        .number {
            color: #b58b32;
            font-size: 30px;
            font-weight: bold;
        }
        a {
            color: #17452f;
            margin-left: 15px;
        }
                
        .admin-links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 15px;
        }

        .admin-links a {
            display: inline-block;
            margin: 0;
            padding: 12px 16px;
            background: #17452f;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
        }

        .admin-links a:hover {
            background: #b58b32;
            color: #183c2b;
        }

        @media (max-width: 600px) {
            main {
                margin: 12px auto;
                padding: 12px;
            }

            header {
                padding: 16px;
            }

            .admin-links {
                flex-direction: column;
            }

            .admin-links a {
                width: 100%;
                box-sizing: border-box;
            }
        }
        .table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-top: 15px;
        }

        .table-wrap table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
        }

        .table-wrap th,
        .table-wrap td {
            padding: 10px;
            text-align: right;
            white-space: nowrap;
        }

        @media (max-width: 600px) {
            .table-wrap {
                border-radius: 8px;
            }

            .table-wrap table {
                min-width: 700px;
            }
        }

    </style>
</head>
<body>
<header>
    <h1>أملاك العقارية</h1>
    <p>مرحباً، <?= htmlspecialchars($_SESSION['full_name'] ?? 'المدير') ?></p>
</header>

<main>
    <h2>لوحة التحكم</h2>

    <div class="cards">
        <div class="card">
            <p>إجمالي العقارات</p>
            <div class="number"><?= (int)$totalProperties ?></div>
        </div>

        <div class="card">
            <p>العقارات المتاحة</p>
            <div class="number"><?= (int)$availableProperties ?></div>
        </div>
    </div>

    
<h3>إدارة العقارات</h3>
<div class="table-wrap">

<table style="width:100%; border-collapse:collapse; background:white; margin-top:20px;">
    <thead>
        <tr style="background:#17452f; color:white;">
            <th style="padding:12px;">رقم</th>
            <th style="padding:12px;">اسم العقار</th>
            <th style="padding:12px;">السعر</th>
            <th style="padding:12px;">الحالة</th>
            <th style="padding:12px;">الإجراء</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $stmt = $pdo->query(
            "SELECT id, title, price, status
             FROM properties
             ORDER BY created_at DESC"
        );

        while ($item = $stmt->fetch(PDO::FETCH_ASSOC)):
        ?>
            <tr>
                <td style="padding:12px;"><?= (int)$item['id'] ?></td>
                <td style="padding:12px;">
                    <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td style="padding:12px;">
                    <?= number_format((float)$item['price'], 2) ?> جنيه
                </td>
                <td style="padding:12px;">
                    <?php
                    $statuses = [
                        'available' => 'متاح',
                        'sold' => 'تم البيع',
                        'rented' => 'تم التأجير'
                    ];
                    echo $statuses[$item['status']] ?? 'غير محدد';
                    ?>
                </td>
                <td style="padding:12px;">
                    
                    <a href="edit-property.php?id=<?= (int)$item['id'] ?>">
                        تعديل
                    </a>

                    <form method="post"
                        action="delete-property.php"
                        style="display:inline;"
                        onsubmit="return confirm('هل أنت متأكد من حذف هذا العقار؟ لا يمكن التراجع عن الحذف.');">

                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                        >

                        <button type="submit"
                                style="background:#b42318;color:white;border:0;padding:7px 12px;border-radius:5px;cursor:pointer;">
                            حذف
                        </button>
                    </form>

                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>

<h3>إدارة الموقع</h3>
<div class="admin-links">
    <a href="add-property.php">＋ إضافة عقار جديد</a>
    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
    <a href="change-password.php">🔐 تغيير كلمة السر</a>
    <a href="add-user.php">👤 إضافة مستخدم جديد</a>
<?php endif; ?>
    <a href="../index.php">عرض الموقع الرئيسي</a>
    <a href="../properties.php">عرض جميع العقارات</a>
    <a href="logout.php">تسجيل الخروج</a>
</div>


    
</main>
</body>
</html>
