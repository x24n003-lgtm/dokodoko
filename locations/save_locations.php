<?php
// エラー表示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// セッションをサイト全体で有効化
session_set_cookie_params([
    'path' => '/',        // サイト全体で有効
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// 以下、POST受信や位置情報更新処理…

header("Content-Type: application/json; charset=UTF-8");

// JSONを受け取る
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// セッションに user_id があるか確認
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "セッション切れ（ログイン情報なし）"], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$lat = $data["lat"] ?? null;
$lng = $data["lng"] ?? null;
$home_address = $data["home_address"] ?? null;

// DB接続
$conn = new mysqli("172.16.199.21", "x24n007", "n051211", "dokodoko");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "DB接続失敗"]);
    exit;
}

// 緯度経度がある場合のみ更新
if ($lat !== null && $lng !== null) {
    $stmt = $conn->prepare("
        UPDATE users
        SET lat = ?, lng = ?, location_updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("ddi", $lat, $lng, $user_id);
    $stmt->execute();
    $stmt->close();

    // 住所もあれば更新
    if ($home_address !== null) {
        $stmt2 = $conn->prepare("UPDATE users SET home_address = ? WHERE id = ?");
        $stmt2->bind_param("si", $home_address, $user_id);
        $stmt2->execute();
        $stmt2->close();
    }

    echo json_encode(["status" => "✅ 位置情報更新完了（セッション方式）"]);
    exit;

} else {
    echo json_encode(["status" => "データ受信（緯度経度なし）"]);
    exit;
}
?>
