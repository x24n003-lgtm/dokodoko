<?php
// save_location.php
// --- エラー表示 ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// JSON受信
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// 必須項目チェック
if (!$data || !isset($data['username'], $data['lat'], $data['lng'])) {
    echo json_encode(["error" => "不正なデータ"]);
    exit;
}

$username = $data['username'];
$lat = $data['lat'];
$lng = $data['lng'];

// --- DB接続 ---
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["error" => "DB接続失敗: " . $conn->connect_error]);
    exit;
}

// --- 既存ユーザーのチェック ---
$sql_check = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// --- 更新または挿入 ---
if ($result->num_rows > 0) {
    $sql_update = "UPDATE users SET lat = ?, lng = ?, location_updated_at = NOW() WHERE username = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dds", $lat, $lng, $username);
    $stmt_update->execute();
    echo json_encode(["success" => "位置情報を更新しました"]);
    $stmt_update->close();
} else {
    echo json_encode(["error" => "ユーザーが存在しません"]);
}

$stmt->close();
$conn->close();
?>
