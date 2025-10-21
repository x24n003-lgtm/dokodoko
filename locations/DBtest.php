<?php
// 接続情報
$host = "172.16.199.21";  // Linux MariaDB の IP
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";

// 接続試行
$conn = new mysqli($host, $user, $pass, $db);

// HTMLヘッダ
echo "<!DOCTYPE html><html lang='ja'><head><meta charset='UTF-8'><title>DB接続テスト</title></head><body>";

if ($conn->connect_error) {
    echo "<h2 style='color:red'>接続失敗！</h2>";
    echo "<p>エラー: " . $conn->connect_error . "</p>";
} else {
    echo "<h2 style='color:green'>接続成功！</h2>";
    echo "<p>dokodoko データベースにアクセスできます。</p>";

    // データベース内のテーブル一覧を取得
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "<h3>テーブル一覧：</h3><ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . htmlspecialchars($row[0]) . "</li>";
        }
        echo "</ul>";
    }
}

$conn->close();

echo "</body></html>";
?>
