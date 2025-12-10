<?php
// データベース接続設定
define('DB_HOST', '172.16.199.21');
define('DB_NAME', 'dokodoko');
define('DB_USER', 'x24n007');
define('DB_PASS', 'n051211');

// データベース接続
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("データベース接続エラー: " . $e->getMessage());
        die("データベースに接続できません");
    }
}
?>
