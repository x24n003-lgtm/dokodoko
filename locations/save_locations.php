<?php
// データベース接続設定
$host = "localhost";
$user = "root";
$pass = "";          // XAMPPなら通常は空
$dbname = "sotuken";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("DB接続失敗: " . $conn->connect_error);
}

// JSONデータ受け取り
$data = json_decode(file_get_contents("php://input"), true);
$name = $data["name"];
$lat = $data["lat"];
$lng = $data["lng"];

// 既存の name をチェック
$sql_check = "SELECT id FROM locations WHERE name = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  // 既に存在 → 更新
  $sql_update = "UPDATE locations SET lat = ?, lng = ? WHERE name = ?";
  $stmt_update = $conn->prepare($sql_update);
  $stmt_update->bind_param("dds", $lat, $lng, $name);
  $stmt_update->execute();
  echo "位置情報を更新しました";
  $stmt_update->close();
} else {
  // 存在しない → 新規登録
  $sql_insert = "INSERT INTO locations (name, lat, lng) VALUES (?, ?, ?)";
  $stmt_insert = $conn->prepare($sql_insert);
  $stmt_insert->bind_param("sdd", $name, $lat, $lng);
  $stmt_insert->execute();
  echo "位置情報を追加しました";
  $stmt_insert->close();
}

$stmt->close();
$conn->close();
?>
