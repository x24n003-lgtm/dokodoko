<?php
// ------------------ セッション設定 ------------------
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',        // ドメイン直下で全ページ有効
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

header("Content-Type: application/json; charset=UTF-8");

// セッションから email 取得
$email = $_SESSION['email'] ?? null;
if (!$email) {
    echo json_encode(["error" => "ログイン情報がありません"]);
    exit;
}

// JSON データ受信
$input = file_get_contents("php://input");
$data = json_decode($input, true);

$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;
$home_address = $data['home_address'] ?? null;

if ($lat === null && $lng === null && $home_address === null) {
    echo json_encode(["error" => "緯度経度または住所が必要です"]);
    exit;
}

// DB 接続
$conn = new mysqli("172.16.199.21", "x24n007", "n051211", "dokodoko");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    echo json_encode(["error" => "DB接続失敗"]);
    exit;
}

// 住所がある場合、緯度経度変換（OpenStreetMap）
if (($lat === null || $lng === null) && $home_address !== null) {
    $geocode_url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($home_address) . "&limit=1";
    $context = stream_context_create(['http' => ['header' => 'User-Agent: LocationApp/1.0']]);
    $resp = @file_get_contents($geocode_url, false, $context);
    if ($resp === false) {
        echo json_encode(["error" => "住所の変換に失敗しました"]);
        exit;
    }
    $geo = json_decode($resp, true);
    if (empty($geo)) {
        echo json_encode(["error" => "住所が見つかりません"]);
        exit;
    }
    $lat = (float)$geo[0]['lat'];
    $lng = (float)$geo[0]['lon'];
}

// 更新処理
$stmt = $conn->prepare("UPDATE users SET lat=?, lng=?, location_updated_at=NOW() WHERE email=?");
$stmt->bind_param("dds", $lat, $lng, $email);
if ($stmt->execute()) {
    // 住所も更新
    if ($home_address !== null) {
        $stmt2 = $conn->prepare("UPDATE users SET home_address=? WHERE email=?");
        $stmt2->bind_param("ss", $home_address, $email);
        $stmt2->execute();
        $stmt2->close();
    }
    echo json_encode(["status" => "位置情報更新完了", "email" => $email]);
} else {
    echo json_encode(["error" => "更新失敗: " . $stmt->error]);
}

$stmt->close();
$conn->close();
