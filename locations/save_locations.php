<?php
header("Content-Type: application/json; charset=UTF-8");

// JSON 受け取り
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// id が無い → エラー（lat/lng は無くても OK）
if (!$data || !isset($data["id"])) {
    echo json_encode(["error" => "不正なデータ（id が必要）"], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int)$data["id"];
$lat = $data["lat"] ?? null;            // 緯度
$lng = $data["lng"] ?? null;            // 経度
$home_address = $data["home_address"] ?? null;  // 住所

// DB 接続
$conn = new mysqli("172.16.199.21", "x24n007", "n051211", "dokodoko");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "DB接続失敗"]);
    exit;
}

// lat/lng が送られてきた場合だけ更新
if ($lat !== null && $lng !== null) {

    $stmt = $conn->prepare("
        UPDATE users 
        SET lat = ?, lng = ?, location_updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ddi", $lat, $lng, $user_id);
    $stmt->execute();
    $stmt->close();

    // home_address があれば更新
    if ($home_address !== null) {
        $stmt2 = $conn->prepare("UPDATE users SET home_address = ? WHERE id = ?");
        $stmt2->bind_param("si", $home_address, $user_id);
        $stmt2->execute();
        $stmt2->close();
    }

    echo json_encode(["status" => "位置情報更新完了"]);
    exit;

} else {
    // 位置が送られてこない → id があれば OK と返す
    echo json_encode(["status" => "データ受信（lat/lng 無しのため未更新）"]);
    exit;
}
?>
