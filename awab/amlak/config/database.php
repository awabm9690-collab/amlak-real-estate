
<?php
$host = "127.0.0.1";
$port = 3307;
$dbname = "amlak_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات.");
}
?>
