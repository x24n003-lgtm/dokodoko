<?php
// ===============================
// save_locations.php（パターンA用）
// ===============================

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json; charset=UTF-8");

// -------------------------------
// ログイン確認
// -------------------------------
if (!isset($_SESSION['email'])) {
    echo json_encode(["error" => "未ログイン"], JSON_UNESCAPED_UNICODE);
    exit;
}

$email = $_SESSION['email'];

// -------------------------------
// JSON 受信
// -------------------------------
$data = json_decode(file_get_contents("php://input"), true);
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;

if ($lat === null || $lng === null) {
    echo json_encode(["error" => "緯度経度がありません"], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------
// DB接続
// -------------------------------
$conn = new mysqli(
    "172.16.199.21",
    "x24n007",
    "n051211",
    "dokodoko"
);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "DB接続失敗"], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------
// 現在地のみ更新
// -------------------------------
$stmt = $conn->prepare("
    UPDATE users
    SET lat = ?, lng = ?, location_updated_at = NOW()
    WHERE email = ?
");
$stmt->bind_param("dds", $lat, $lng, $email);
$stmt->execute();

echo json_encode([
    "success" => true,
    "lat" => $lat,
    "lng" => $lng
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
