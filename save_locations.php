<?php
// ===============================
// save_locations.php (セッション対応版)
// ===============================

// エラー表示（開発用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===============================
// セッション読み込み（サイト全体共通）
// ===============================
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// ===============================
// ヘッダー設定
// ===============================
header("Content-Type: application/json; charset=UTF-8");

// ===============================
// JSON データ受信
// ===============================
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// ===============================
// email 判定（セッション優先 → JSON POST）
// ===============================
$email = $_SESSION['email'] ?? ($data['email'] ?? null);
if (!$email) {
    echo json_encode(["error" => "不正なデータ（email が必要）"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===============================
// 緯度・経度・住所取得
// ===============================
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;
$home_address = $data['home_address'] ?? null;

// ===============================
// DB接続
// ===============================
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    echo json_encode(["error" => "DB接続失敗"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===============================
// 既存ユーザーのチェック
// ===============================
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// ===============================
// 更新 or 挿入
// ===============================
if ($result->num_rows > 0) {
    // 更新
    if ($lat !== null && $lng !== null) {
        $stmt_update = $conn->prepare("
            UPDATE users 
            SET lat = ?, lng = ?, location_updated_at = NOW()
            WHERE email = ?
        ");
        $stmt_update->bind_param("dds", $lat, $lng, $email);
        $stmt_update->execute();
        $stmt_update->close();
    }

    if ($home_address !== null) {
        $stmt_update2 = $conn->prepare("
            UPDATE users 
            SET home_address = ?
            WHERE email = ?
        ");
        $stmt_update2->bind_param("ss", $home_address, $email);
        $stmt_update2->execute();
        $stmt_update2->close();
    }

    echo json_encode([
        "success" => "位置情報を更新しました",
        "action" => "update",
        "email" => $email,
        "lat" => $lat,
        "lng" => $lng
    ], JSON_UNESCAPED_UNICODE);

} else {
    // 新規追加（lat/lng が必須）
    if ($lat === null || $lng === null) {
        echo json_encode(["error" => "新規追加時は緯度経度が必要です"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt_insert = $conn->prepare("
        INSERT INTO users (email, lat, lng, home_address, location_updated_at, created_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt_insert->bind_param("sdds", $email, $lat, $lng, $home_address);
    $stmt_insert->execute();
    $stmt_insert->close();

    echo json_encode([
        "success" => "新規ユーザーとして位置情報を登録しました",
        "action" => "insert",
        "email" => $email,
        "lat" => $lat,
        "lng" => $lng
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
