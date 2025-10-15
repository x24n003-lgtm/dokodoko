<?php
$host = "localhost";
$user = "root";         // MariaDB ユーザー
$pass = "";             // パスワード
$dbname = "dokodoko";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("DB接続失敗: ".$conn->connect_error);
$conn->set_charset("utf8");

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if(!$data || !isset($data["name"], $data["lat"], $data["lng"])) die("不正なデータ");

$name = $data["name"];
$lat = $data["lat"];
$lng = $data["lng"];

// 緯度経度を更新
$stmt = $conn->prepare("UPDATE users SET lat = ?, lng = ? WHERE name = ?");
$stmt->bind_param("dds", $lat, $lng, $name);
$stmt->execute();
$stmt->close();

$conn->close();
echo "位置情報を更新しました";
?>
