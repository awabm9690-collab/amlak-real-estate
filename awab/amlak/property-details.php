```php
<?php
require_once __DIR__ . '/config/database.php';

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(404);
    exit('رقم العقار غير صحيح.');
}

$stmt = $pdo->prepare(
    "SELECT * FROM properties
     WHERE id = :id AND status = 'available'
     LIMIT 1"
);

$stmt->execute(['id' => $id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    http_response_code(404);
    exit('العقار غير موجود أو غير متاح حاليًا.');
}

$types = [
    'apartment' => 'شقة',
    'house' => 'منزل',
    'land' => 'أرض',
    'commercial' => 'عقار تجاري',
    'shop' => 'محل تجاري'
];

$purposes = [
    'sale' => 'للبيع',
    'rent' => 'للإيجار'
];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($property['title']) ?> | أملاك العقارية</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f5f6f2;
            color: #183c30;
        }

        header {
            background: #123b2e;
            color: white;
            padding: 22px 7%;
        }

        main {
            width: 90%;
            max-width: 850px;
            margin: 35px auto;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px #00000012;
        }

        .property-image {
            width: 100%;
            max-height: 420px;
            object-fit: cover;
            border-radius: 8px;
            cursor: zoom-in;
            transition: transform 0.2s;
        }

        .property-image:hover {
            opacity: 0.95;
        }

        .placeholder {
            height: 250px;
            background: #e4e9e2;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            color: #456b59;
        }

        .price {
            color: #99751c;
            font-size: 25px;
            font-weight: bold;
        }

        .details {
            line-height: 2.2;
        }

        .contact {
            display: inline-block;
            background: #25D366;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 15px;
            font-weight: bold;
        }

        .contact:hover {
            background: #1da851;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #24533e;
        }

        /* نافذة تكبير الصورة */
        .image-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.9);
            align-items: center;
            justify-content: center;
            padding: 25px;
            cursor: zoom-out;
        }

        .image-modal img {
            max-width: 95%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            left: 25px;
            color: white;
            font-size: 38px;
            cursor: pointer;
            background: none;
            border: none;
        }

        @media (max-width: 600px) {
            .card {
                padding: 16px;
            }

            .image-modal img {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

<header>
    <strong>أملاك العقارية</strong>
    <span> | تفاصيل العقار</span>
</header>

<main>
    <article class="card">

        <?php if (!empty($property['image'])): ?>
            <img
                class="property-image"
                id="propertyImage"
                src="<?= e($property['image']) ?>"
                alt="<?= e($property['title']) ?>"
                title="اضغط لتكبير الصورة"
            >
        <?php else: ?>
            <div class="placeholder">لا توجد صورة للعقار</div>
        <?php endif; ?>

        <h1><?= e($property['title']) ?></h1>

        <p class="price">
            <?= number_format((float)$property['price'], 2) ?> جنيه
        </p>

        <div class="details">
            <div>
                <strong>نوع العقار:</strong>
                <?= e($types[$property['property_type']]
                    ?? $property['property_type']) ?>
            </div>

            <div>
                <strong>الغرض:</strong>
                <?= e($purposes[$property['purpose']]
                    ?? $property['purpose']) ?>
            </div>

            <div>
                <strong>الموقع:</strong>
                <?= e($property['location']) ?>
            </div>

            <div>
                <strong>المساحة:</strong>
                <?= e($property['area'] ?? 'غير محددة') ?> م²
            </div>

            <div>
                <strong>غرف النوم:</strong>
                <?= e($property['bedrooms']) ?>
            </div>

            <div>
                <strong>الحمامات:</strong>
                <?= e($property['bathrooms']) ?>
            </div>
        </div>

        <h2>وصف العقار</h2>
        <p style="background: yellow; color: black; padding: 10px;">
    
        </p>
        

        <p>
            <?= nl2br(e($property['description'] ?? 'لا يوجد وصف حاليًا.')) ?>
        </p>

        <a
            class="contact"
            href="https://wa.me/249905049600?text=<?= rawurlencode('السلام عليكم، أريد الاستفسار عن العقار: ' . $property['title']) ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            تواصل معنا عبر واتساب
        </a>

    </article>

    <a class="back" href="properties.php">← العودة إلى العقارات</a>
</main>

<?php if (!empty($property['image'])): ?>
<div
    class="image-modal"
    id="imageModal"
    role="dialog"
    aria-modal="true"
    aria-label="صورة العقار المكبرة"
>
    <button class="close-modal" id="closeModal" aria-label="إغلاق">&times;</button>
    <img
        src="<?= e($property['image']) ?>"
        alt="<?= e($property['title']) ?>"
    >
</div>
<?php endif; ?>

<script>
const propertyImage = document.getElementById('propertyImage');
const imageModal = document.getElementById('imageModal');
const closeModal = document.getElementById('closeModal');

if (propertyImage && imageModal) {
    propertyImage.addEventListener('click', function () {
        imageModal.style.display = 'flex';
    });

    imageModal.addEventListener('click', function (event) {
        if (event.target === imageModal || event.target === closeModal) {
            imageModal.style.display = 'none';
        }
    });

    closeModal.addEventListener('click', function () {
        imageModal.style.display = 'none';
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            imageModal.style.display = 'none';
        }
    });
}
</script>

</body>
</html>
```