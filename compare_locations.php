<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

$conn = new mysqli("172.16.199.21", "x24n007", "n051211", "dokodoko");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "DB接続失敗"]);
    exit;
}

$email = $_POST['email'] ?? null;

if (!$email) {
    echo json_encode(["error" => "email が必要です"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT lat, lng, home_lat, home_lng
    FROM users
    WHERE email = ?
");

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($lat, $lng, $home_lat, $home_lng);

if (!$stmt->fetch()) {
    echo json_encode(["error" => "ユーザーが見つかりません"]);
    exit;
}

// ----- 距離計算 -----
function distance($lat1, $lng1, $lat2, $lng2) {
    $R = 6371; // km
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);

    $dlat = $lat2 - $lat1;
    $dlng = $lng2 - $lng1;

    $a = sin($dlat / 2) * sin($dlat / 2) +
         cos($lat1) * cos($lat2) *
         sin($dlng / 2) * sin($dlng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $R * $c;
}

$distance = distance($home_lat, $home_lng, $lat, $lng);

echo json_encode([
    "home_lat" => $home_lat,
    "home_lng" => $home_lng,
    "current_lat" => $lat,
    "current_lng" => $lng,
    "distance_km" => $distance
]);
?>
0