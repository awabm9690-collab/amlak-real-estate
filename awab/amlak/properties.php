
<?php
require_once __DIR__ . '/config/database.php';


$search = trim($_GET['search'] ?? '');
$property_type = trim($_GET['property_type'] ?? '');
$purpose = trim($_GET['purpose'] ?? '');
$min_price = trim($_GET['min_price'] ?? '');
$max_price = trim($_GET['max_price'] ?? '');

$sql = "SELECT * FROM properties WHERE status = 'available'";
$params = [];

if ($search !== '') {
    $sql .= " AND (title LIKE :search_title OR location LIKE :search_location)";
    $params['search_title'] = "%{$search}%";
    $params['search_location'] = "%{$search}%";
}

if ($property_type !== '') {
    $sql .= " AND property_type = :property_type";
    $params['property_type'] = $property_type;
}

if (in_array($purpose, ['sale', 'rent'], true)) {
    $sql .= " AND purpose = :purpose";
    $params['purpose'] = $purpose;
}

if ($min_price !== '' && is_numeric($min_price) && (float)$min_price >= 0) {
    $sql .= " AND price >= :min_price";
    $params['min_price'] = (float)$min_price;
}

if ($max_price !== '' && is_numeric($max_price) && (float)$max_price >= 0) {
    $sql .= " AND price <= :max_price";
    $params['max_price'] = (float)$max_price;
}

$sort = $_GET['sort'] ?? 'newest';

switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY price ASC";
        break;

    case 'price_high':
        $sql .= " ORDER BY price DESC";
        break;

    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);


$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$types = [
    'apartment' => 'شقة',
    'house'     => 'منزل',
    'land'      => 'أرض',
    'shop'      => 'محل تجاري'
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
    <title>العقارات | أملاك العقارية</title>

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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h1 {
            margin: 0;
            color: #d8b45a;
            font-size: 25px;
        }

        header a {
            color: white;
            text-decoration: none;
        }

        .container {
            width: 90%;
            max-width: 1150px;
            margin: 35px auto;
        }

        .intro {
            margin-bottom: 25px;
        }
            
        .search-box {
        background: white;
        padding: 22px;
        border-radius: 12px;
        border: 1px solid #e7e8e1;
        box-shadow: 0 4px 15px #0000000a;
        margin-bottom: 28px;
        }

        .search-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        align-items: end;
        }

        .search-form label {
        display: block;
        margin-bottom: 7px;
        font-size: 14px;
        font-weight: bold;
        }

        .search-form input,
        .search-form select {
        width: 100%;
        padding: 12px;
        border: 1px solid #d5ded5;
        border-radius: 7px;
        font-family: inherit;
        font-size: 14px;
        background: #fff;
        }

        .search-form input:focus,
        .search-form select:focus {
        outline: 2px solid #d8b45a;
        border-color: #176b45;
        }

        .search-form button,
        .reset-search {
        display: inline-block;
        padding: 12px 18px;
        border: none;
        border-radius: 7px;
        background: #176b45;
        color: white;
        font-family: inherit;
        font-size: 14px;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
        }

        .search-form button:hover {
        background: #123b2e;
        }

        .reset-search {
        background: #eee9da;
        color: #183c30;
        }


        .intro h2 {
            margin-bottom: 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit, minmax(260px, 1fr)
            );
            gap: 22px;
        }

        .property {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px #00000012;
            border: 1px solid #e7e8e1;
        }

        .image-placeholder {
            height: 175px;
            background: #e4e9e2;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #456b59;
        }

        .property-content {
            padding: 20px;
        }

        .property h3 {
            margin-top: 0;
        }

        .price {
            color: #99751c;
            font-size: 20px;
            font-weight: bold;
        }

        .details {
            line-height: 2;
            color: #52645a;
        }

        .badge {
            display: inline-block;
            background: #eaf1e9;
            color: #24533e;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 13px;
        }

        .empty {
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        footer {
            text-align: center;
            padding: 25px;
            background: #123b2e;
            color: white;
            margin-top: 45px;
        }
    </style>
</head>

<body>

<header>
    <h1>أملاك العقارية</h1>
    <a href="index.php">الرئيسية</a>
    <a href="admin/login.php">دخول الإدارة</a>
</header>

<main class="container">

    <section class="intro">
        <h2>العقارات المتاحة</h2>
        <p>اكتشف العقارات المناسبة لك للبيع والإيجار.</p>
    </section>
    
    <div class="search-box">
        <h3>ابحث عن عقارك المناسب</h3>

        <form method="GET" action="properties.php" class="search-form">

            <div>
                <label for="search">اسم العقار أو الموقع</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    placeholder="مثلاً: الخرطوم"
                    value="<?= e($search) ?>"
                >
            </div>

            <div>
                <label for="property_type">نوع العقار</label>
                <select id="property_type" name="property_type">
                    <option value="">كل الأنواع</option>
                    <option value="apartment" <?= $property_type === 'apartment' ? 'selected' : '' ?>>شقة</option>
                    <option value="house" <?= $property_type === 'house' ? 'selected' : '' ?>>منزل</option>
                    <option value="land" <?= $property_type === 'land' ? 'selected' : '' ?>>أرض</option>
                    <option value="shop" <?= $property_type === 'shop' ? 'selected' : '' ?>>محل تجاري</option>
                </select>
            </div>

            <div>
                <label for="purpose">الغرض</label>
                <select id="purpose" name="purpose">
                    <option value="">بيع وإيجار</option>
                    <option value="sale" <?= $purpose === 'sale' ? 'selected' : '' ?>>للبيع</option>
                    <option value="rent" <?= $purpose === 'rent' ? 'selected' : '' ?>>للإيجار</option>
                </select>
            </div>

            <div>
                <label for="min_price">أقل سعر</label>
                <input
                    type="number"
                    id="min_price"
                    name="min_price"
                    min="0"
                    placeholder="من"
                    value="<?= e($min_price) ?>"
                >
            </div>

            <div>
                <label for="max_price">أعلى سعر</label>
                <input
                    type="number"
                    id="max_price"
                    name="max_price"
                    min="0"
                    placeholder="إلى"
                    value="<?= e($max_price) ?>"
                >
            </div>
            
            <div>
                <label for="sort">ترتيب العقارات</label>
                <select id="sort" name="sort">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>
                        الأحدث أولاً
                    </option>
                    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>
                        السعر: من الأقل للأعلى
                    </option>
                    <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>
                        السعر: من الأعلى للأقل
                    </option>
                </select>
            </div>


            <div>
                <button type="submit">بحث عن العقارات</button>
                <a href="properties.php" class="reset-search">إعادة ضبط</a>
            </div>

        </form>
    </div>


    <?php if (count($properties) > 0): ?>

        <div class="grid">

            <?php foreach ($properties as $property): ?>

                <article class="property">

                    <?php if (!empty($property['image'])): ?>

                        <img
                            src="<?= e($property['image']) ?>"
                            alt="<?= e($property['title']) ?>"
                            style="width:100%;height:175px;object-fit:cover"
                        >

                    <?php else: ?>

                        <div class="image-placeholder">
                            صورة العقار
                        </div>

                    <?php endif; ?>

                    <div class="property-content">

                        <span class="badge">
                            <?= e($purposes[$property['purpose']]
                                ?? $property['purpose']) ?>
                        </span>

                        
                        
                        <h3>
                            <a href="property-details.php?id=<?= (int)$property['id'] ?>"
                            style="color: #176b45; text-decoration: none; cursor: pointer;">
                                <?= e($property['title']) ?>
                            </a>
                        </h3>

                        <a href="property-details.php?id=<?= (int)$property['id'] ?>"
                        style="color: #176b45; font-size: 14px; text-decoration: underline;">
                            عرض تفاصيل العقار ←
                        </a>


                                            
                        <p class="price">
                            <?php if ($property['price'] !== null && $property['price'] !== ''): ?>
                                <?= number_format((float)$property['price'], 2) ?> جنيه
                            <?php else: ?>
                                <span style="font-size: 15px;">السعر عند التواصل</span>
                            <?php endif; ?>
                        </p>


                        <div class="details">
                            <div>
                                النوع:
                                <?= e($types[$property['property_type']]
                                    ?? $property['property_type']) ?>
                            </div>

                            <div>
                                المساحة:
                                <?= e($property['area'] ?? 'غير محددة') ?>
                                م²
                            </div>

                            <div>
                                الموقع:
                                <?= e($property['location']) ?>
                            </div>

                            <div>
                                غرف النوم:
                                <?= e($property['bedrooms']) ?>
                            </div>
                        </div>

                        <p><?= e($property['description']) ?></p>

                    </div>
                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty">
            لا توجد عقارات متاحة حاليًا. يرجى زيارتنا لاحقًا.
        </div>

    <?php endif; ?>

</main>

<footer>
    جميع الحقوق محفوظة © أملاك العقارية
</footer>

</body>
</html>
