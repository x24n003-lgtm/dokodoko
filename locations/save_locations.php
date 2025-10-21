<?php
// --- データベース接続設定 ---
$host = "172.16.199.21";  // Linux MariaDB の IP
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB接続失敗: " . $conn->connect_error);
}

// --- JSON受信 ---
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data["name"], $data["lat"], $data["lng"])) {
    die("不正なデータ");
}

$name = $data["name"];
$lat  = $data["lat"];
$lng  = $data["lng"];

// --- 既存の name をチェック ---
$sql_check = "SELECT id FROM users WHERE name = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // 既存 → 更新
    $sql_update = "UPDATE users SET lat = ?, lng = ? WHERE name = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dds", $lat, $lng, $name);
    $stmt_update->execute();
    echo "位置情報を更新しました";
    $stmt_update->close();
} else {
    // 新規 → 追加
    $sql_insert = "INSERT INTO users (name, lat, lng) VALUES (?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sdd", $name, $lat, $lng);
    $stmt_insert->execute();
    echo "位置情報を追加しました";
    $stmt_insert->close();
}

$stmt->close();
$conn->close();
?>
