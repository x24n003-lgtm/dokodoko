<?php
header("Content-Type: application/json; charset=UTF-8");

// JSON を受け取り
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data["email"])) {
    echo json_encode(["error" => "不正なデータ（email が必要）"], JSON_UNESCAPED_UNICODE);
    exit;
}

$email = $data["email"];
$lat = $data["lat"] ?? null;
$lng = $data["lng"] ?? null;
$home_address = $data["home_address"] ?? null;

// DB 接続
$conn = new mysqli("172.16.199.21", "x24n007", "n051211", "dokodoko");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "DB接続失敗"]);
    exit;
}

// lat/lng がある → 位置情報を更新
if ($lat !== null && $lng !== null) {

    // 位置情報更新
    $stmt = $conn->prepare("
        UPDATE users 
        SET lat = ?, lng = ?, location_updated_at = NOW()
        WHERE email = ?
    ");
    $stmt->bind_param("dds", $lat, $lng, $email);
    $stmt->execute();
    $stmt->close();

    // 住所も来ていたら更新
    if ($home_address !== null) {
        $stmt2 = $conn->prepare("
            UPDATE users 
            SET home_address = ?
            WHERE email = ?
        ");
        $stmt2->bind_param("ss", $home_address, $email);
        $stmt2->execute();
        $stmt2->close();
    }

    echo json_encode(["status" => "位置情報更新完了"]);
    exit;
}

// lat/lng が無い場合
echo json_encode(["status" => "データ受信（lat/lng 無しのため未更新）"]);
exit;
