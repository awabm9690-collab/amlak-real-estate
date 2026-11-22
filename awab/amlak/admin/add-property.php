```php
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

$message = '';
$messageType = 'error';

$old = function ($key) {
    return htmlspecialchars(
        (string)($_POST[$key] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (
        empty($_SESSION['csrf_token']) ||
        !isset($_POST['csrf_token']) ||
        !is_string($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('طلب غير صالح. ارجع إلى نموذج الإضافة وحاول مرة أخرى.');
    }


    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');


    $property_type = $_POST['property_type'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $price = $_POST['price'] ?? '';
    $area = $_POST['area'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $bedrooms = $_POST['bedrooms'] ?? '';
    $bathrooms = $_POST['bathrooms'] ?? '';

    $imagePath = null;
    $uploadedFile = null;

    
   
    // التحقق من البيانات المطلوبة
    if (
        $title === '' ||
        $description === '' ||
        $location === '' ||
        $price === '' ||
        $area === '' ||
        !isset($_POST['bedrooms'], $_POST['bathrooms']) ||
        trim((string)$_POST['bedrooms']) === '' ||
        trim((string)$_POST['bathrooms']) === ''
    ) {
        $message = 'يرجى إكمال جميع البيانات المطلوبة للعقار.';
    } elseif (
        !in_array($property_type, ['apartment', 'house', 'land', 'commercial'], true)
    ) {
        $message = 'نوع العقار غير صحيح.';
    } elseif (!in_array($purpose, ['sale', 'rent'], true)) {
        $message = 'يرجى اختيار البيع أو الإيجار.';
    } elseif (!is_numeric($price) || (float)$price <= 0) {
        $message = 'يرجى إدخال سعر صحيح أكبر من صفر.';
    } elseif (!is_numeric($area) || (float)$area < 0) {
        $message = 'يرجى إدخال مساحة صحيحة.';
    } elseif (
        filter_var($bedrooms, FILTER_VALIDATE_INT) === false ||
        (int)$bedrooms < 0 ||
        filter_var($bathrooms, FILTER_VALIDATE_INT) === false ||
        (int)$bathrooms < 0
    ) {
        $message = 'عدد الغرف والحمامات يجب أن يكون رقمًا صحيحًا غير سالب.';
    } else {
        // تجهيز مجلد الصور

        
        $uploadDir = __DIR__ . '/../uploads/';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            $message = 'تعذر إنشاء مجلد الصور.';
        } else {
            // معالجة الصورة إذا اختار المستخدم صورة
            if (
                isset($_FILES['image']) &&
                $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                $file = $_FILES['image'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $message = 'حدث خطأ أثناء رفع الصورة.';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $message = 'حجم الصورة يجب ألا يتجاوز 5 ميجابايت.';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo
                        ? finfo_file($finfo, $file['tmp_name'])
                        : false;

                    if ($finfo) {
                        finfo_close($finfo);
                    }

                    $allowedTypes = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp'
                    ];

                    if (
                        $mime === false ||
                        !isset($allowedTypes[$mime]) ||
                        @getimagesize($file['tmp_name']) === false
                    ) {
                        $message = 'نوع الصورة غير مدعوم. استخدم JPG أو PNG أو WEBP.';
                    } else {
                        $filename = bin2hex(random_bytes(16))
                            . '.' . $allowedTypes[$mime];

                        $destination = $uploadDir . $filename;

                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $imagePath = 'uploads/' . $filename;
                            $uploadedFile = $destination;
                        } else {
                            $message = 'تعذر حفظ الصورة في مجلد uploads.';
                        }
                    }
                }
            }

            // حفظ العقار إذا لم توجد أخطاء
            if ($message === '') {
                try {
                    
                    $sql = "INSERT INTO properties
                        (
                            title,
                            description,
                            property_type,
                            purpose,
                            price,
                            area,
                            location,
                            bedrooms,
                            bathrooms,
                            image,
                            status
                        )
                        VALUES
                        (
                            :title,
                            :description,
                            :property_type,
                            :purpose,
                            :price,
                            :area,
                            :location,
                            :bedrooms,
                            :bathrooms,
                            :image,
                            'available'
                        )";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        ':title' => $title,
                        ':description' => $description,
                        ':property_type' => $property_type,
                        ':purpose' => $purpose,
                        ':price' => $price,
                        ':area' => $area === '' ? null : $area,
                        ':location' => $location,
                        ':bedrooms' => (int)$bedrooms,
                        ':bathrooms' => (int)$bathrooms,
                        ':image' => $imagePath
                    ]);

                    header('Location: dashboard.php?added=1');
                    exit;
                } catch (PDOException $e) {
                    // حذف الصورة إذا فشل حفظ العقار
                    if ($uploadedFile && is_file($uploadedFile)) {
                        unlink($uploadedFile);
                    }

                    $message = 'تعذر حفظ العقار. تحقق من قاعدة البيانات وحاول مرة أخرى.';
                }
            }
        }
    }
}

?>
<!DOCTYPE html>

<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة عقار جديد - أملاك العقارية</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px 15px;
            font-family: Tahoma, Arial, sans-serif;
            background: #f4f6f4;
            color: #26352d;
        }

        .container {
            max-width: 750px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
            color: #14532d;
            text-align: center;
        }

        .intro {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font: inherit;
            background: #fff;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: 2px solid #c9a227;
            border-color: #c9a227;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .hint {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #666;
        }

        .message {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 7px;
            background: #fff0f0;
            color: #a12626;
        }

        .buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            border: none;
            border-radius: 7px;
            font: inherit;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }

        .btn-primary {
            background: #14532d;
            color: #fff;
            flex: 1;
        }

        .btn-secondary {
            background: #eee;
            color: #26352d;
        }

        @media (max-width: 550px) {
            .container {
                padding: 20px 15px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>

<body>
<div class="container">
    <h1>إضافة عقار جديد</h1>
    <p class="intro">أدخل بيانات العقار، ويمكنك إرفاق صورة له.</p>

    <?php if ($message !== ''): ?>
    <div class="message">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?> 

    <form method="post" enctype="multipart/form-data">
        
<input
    type="hidden"
    name="csrf_token"
    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
>


        <div class="form-group">
            <label for="title">عنوان العقار *</label>
            <input
                id="title"
                name="title"
                type="text"
                maxlength="200"
                value="<?= $old('title') ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="description">وصف العقار *</label>
            <textarea
                id="description"
                name="description"
                 required
            ><?= $old('description') ?></textarea>
        </div>

        <div class="grid">
            <div class="form-group">
                <label for="property_type">نوع العقار *</label>
                <select id="property_type" name="property_type" required>
                    <option value="">اختر النوع</option>
                    <option value="apartment" <?= $old('property_type') === 'apartment' ? 'selected' : '' ?>>شقة</option>
                    <option value="house" <?= $old('property_type') === 'house' ? 'selected' : '' ?>>منزل / فيلا</option>
                    <option value="land" <?= $old('property_type') === 'land' ? 'selected' : '' ?>>أرض</option>
                    <option value="commercial" <?= $old('property_type') === 'commercial' ? 'selected' : '' ?>>عقار تجاري</option>
                </select>
            </div>

            <div class="form-group">
                <label for="purpose">الغرض *</label>
                <select id="purpose" name="purpose" required>
                    <option value="">اختر الغرض</option>
                    <option value="sale" <?= $old('purpose') === 'sale' ? 'selected' : '' ?>>للبيع</option>
                    <option value="rent" <?= $old('purpose') === 'rent' ? 'selected' : '' ?>>للإيجار</option>
                </select>
            </div>
        </div>

        <div class="grid">
            <div class="form-group">
                <label for="price">السعر *</label>
                <input
                    id="price"
                    name="price"
                    type="number"
                    min="0.01"
                    step="0.01"
                    value="<?= $old('price') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="area">المساحة بالمتر المربع *</label>
                <input
                    id="area"
                    name="area"
                    type="number"
                    min="0"
                    step="0.01"
                    value="<?= $old('area') ?>"
                     required
                >
            </div>
        </div>

        <div class="form-group">
            <label for="location">الموقع *</label>
            <input
                id="location"
                name="location"
                type="text"
                maxlength="200"
                value="<?= $old('location') ?>"
                required
            >
        </div>

        <div class="grid">
            <div class="form-group">
                <label for="bedrooms">عدد غرف النوم</label>
                <input
                    id="bedrooms"
                    name="bedrooms"
                    type="number"
                    min="0"
                    step="1"
                    value="<?= $old('bedrooms') !== '' ? $old('bedrooms') : '0' ?>"
                     required
                >
            </div>

            <div class="form-group">
                <label for="bathrooms">عدد الحمامات</label>
                <input
                    id="bathrooms"
                    name="bathrooms"
                    type="number"
                    min="0"
                    step="1"
                    value="<?= $old('bathrooms') !== '' ? $old('bathrooms') : '0' ?>"
                     required
                >
            </div>
        </div>

        <div class="form-group">
            <label for="image">صورة العقار</label>
            <input
                id="file"
                name="image"
                type="image[]"
                accept="image/jpeg,image/png,image/webp"
                multiple
            >
            <span class="hint">الصيغ المدعومة: JPG وPNG وWEBP. الحد الأقصى 5 ميجابايت.</span>
        </div>

        <div class="buttons">
            <button class="btn btn-primary" type="submit">
                حفظ العقار
            </button>

            <a class="btn btn-secondary" href="dashboard.php">
                الرجوع للوحة الإدارة
            </a>
        </div>

    </form>
</div>
</body>
</html>
```