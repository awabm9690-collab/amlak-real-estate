
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

if (
    empty($_SESSION['csrf_token']) ||
    !isset($_POST['csrf_token']) ||
    !is_string($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('طلب غير صالح. ارجع إلى لوحة الإدارة وحاول مرة أخرى.');
}


$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: dashboard.php?error=invalid_id');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM properties WHERE id = :id");
$stmt->execute(['id' => $id]);

header('Location: dashboard.php?deleted=1');
exit;
?>
