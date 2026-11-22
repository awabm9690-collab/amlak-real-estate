
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

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit('رقم العقار غير صحيح.');
}

$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = :id");
$stmt->execute(['id' => $id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    exit('العقار غير موجود.');
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        empty($_SESSION['csrf_token']) ||
        !isset($_POST['csrf_token']) ||
        !is_string($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('طلب غير صالح. ارجع إلى صفحة التعديل وحاول مرة أخرى.');
    }
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $propertyType = $_POST['property_type'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $price = $_POST['price'] ?? '';
    $area = $_POST['area'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $bedrooms = $_POST['bedrooms'] ?? '';
    $bathrooms = $_POST['bathrooms'] ?? '';
    $status = $_POST['status'] ?? '';

    $allowedTypes = ['apartment', 'house', 'land', 'commercial'];
    $allowedPurposes = ['sale', 'rent'];
    $allowedStatuses = ['available', 'sold', 'rented'];

    if (
        $title === '' || $location === '' ||
        !in_array($propertyType, $allowedTypes, true) ||
        !in_array($purpose, $allowedPurposes, true) ||
        !in_array($status, $allowedStatuses, true) ||
        !is_numeric($price) || (float)$price <= 0 ||
        ($area !== '' && (!is_numeric($area) || (float)$area < 0)) ||
        !ctype_digit((string)$bedrooms) ||
        !ctype_digit((string)$bathrooms)
    ) {
        $message = 'راجع البيانات المدخلة وتأكد من صحتها.';
    } else {
        $stmt = $pdo->prepare(
            "UPDATE properties SET
                title = :title,
                description = :description,
                property_type = :property_type,
                purpose = :purpose,
                price = :price,
                area = :area,
                location = :location,
                bedrooms = :bedrooms,
                bathrooms = :bathrooms,
                status = :status
             WHERE id = :id"
        );

        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'property_type' => $propertyType,
            'purpose' => $purpose,
            'price' => $price,
            'area' => $area === '' ? null : $area,
            'location' => $location,
            'bedrooms' => (int)$bedrooms,
            'bathrooms' => (int)$bathrooms,
            'status' => $status,
            'id' => $id
        ]);

        header('Location: dashboard.php?updated=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تعديل العقار</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            background: #f4f6f3;
            color: #183c2b;
            padding: 20px;
        }
        form {
            max-width: 650px;
            margin: 20px auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
        }
        label { display: block; margin-top: 14px; }
        input, select, textarea {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: inherit;
        }
        button {
            margin-top: 20px;
            padding: 12px 22px;
            background: #17452f;
            color: white;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }
        .message { color: #a12626; }
    </style>
</head>
<body>
<form method="post">
    <input
        type="hidden"
        name="csrf_token"
        value="<?= e($_SESSION['csrf_token']) ?>"
    >
    <h1>تعديل بيانات العقار</h1>

    <?php if ($message !== ''): ?>
        <p class="message"><?= e($message) ?></p>
    <?php endif; ?>

    <label>اسم العقار</label>
    <input name="title" value="<?= e($property['title']) ?>" required>

    <label>الوصف</label>
    <textarea name="description" rows="4"><?= e($property['description']) ?></textarea>

    <label>نوع العقار</label>
    <select name="property_type" required>
        <?php foreach ([
            'apartment' => 'شقة',
            'house' => 'منزل',
            'land' => 'أرض',
            'commercial' => 'عقار تجاري'
        ] as $value => $label): ?>
            <option value="<?= e($value) ?>"
                <?= $property['property_type'] === $value ? 'selected' : '' ?>>
                <?= e($label) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>الغرض</label>
    <select name="purpose" required>
        <option value="sale" <?= $property['purpose'] === 'sale' ? 'selected' : '' ?>>للبيع</option>
        <option value="rent" <?= $property['purpose'] === 'rent' ? 'selected' : '' ?>>للإيجار</option>
    </select>

    <label>السعر بالجنيه السوداني</label>
    <input name="price" type="number" min="0.01" step="0.01"
           value="<?= e($property['price']) ?>" required>

    <label>المساحة بالمتر المربع</label>
    <input name="area" type="number" min="0" step="0.01"
           value="<?= e($property['area']) ?>">

    <label>الموقع</label>
    <input name="location" value="<?= e($property['location']) ?>" required>

    <label>عدد غرف النوم</label>
    <input name="bedrooms" type="number" min="0" step="1"
           value="<?= e($property['bedrooms']) ?>" required>

    <label>عدد الحمامات</label>
    <input name="bathrooms" type="number" min="0" step="1"
           value="<?= e($property['bathrooms']) ?>" required>

    <label>حالة العقار</label>
    <select name="status" required>
        <option value="available" <?= $property['status'] === 'available' ? 'selected' : '' ?>>متاح</option>
        <option value="sold" <?= $property['status'] === 'sold' ? 'selected' : '' ?>>تم البيع</option>
        <option value="rented" <?= $property['status'] === 'rented' ? 'selected' : '' ?>>تم التأجير</option>
    </select>

    <button type="submit">حفظ التعديلات</button>
    <p><a href="dashboard.php">العودة إلى لوحة التحكم</a></p>
</form>
</body>
</html>
