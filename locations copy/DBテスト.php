<?php
// save_location.php
// --- エラー表示設定 ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- ヘッダー設定（JSON形式で返す） ---
header("Content-Type: application/json; charset=UTF-8");

// --- JSONデータの受信 ---
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// --- 必須項目チェック（修正：lat/lngは必須ではない） ---
if (!$data || !isset($data['username'])) {
    echo json_encode([
        "error" => "ユーザー名が必要です"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$username = $data['username'];

// --- lat/lng または home_address のどちらかが必須 ---
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;
$home_address = $data['home_address'] ?? null;

// 緯度経度も住所も無い場合はエラー
if (($lat === null || $lng === null) && $home_address === null) {
    echo json_encode([
        "error" => "緯度経度または住所が必要です"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 住所がある場合、緯度経度に変換 ---
if ($lat === null || $lng === null) {
    if ($home_address !== null) {
        // Geocoding API で住所→緯度経度変換
        $geocode_url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($home_address) . "&limit=1";
        
        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: LocationApp/1.0'
            ]
        ]);
        
        $geocode_response = @file_get_contents($geocode_url, false, $context);
        
        if ($geocode_response === false) {
            echo json_encode([
                "error" => "住所の変換に失敗しました"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $geocode_data = json_decode($geocode_response, true);
        
        if (empty($geocode_data)) {
            echo json_encode([
                "error" => "住所が見つかりませんでした"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 緯度経度を取得
        $lat = (float)$geocode_data[0]['lat'];
        $lng = (float)$geocode_data[0]['lon'];
    }
}

// --- DB接続設定 ---
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";

// --- DB接続 ---
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode([
        "error" => "DB接続失敗: " . $conn->connect_error
    ], JSON_UNESCAPED_UNICODE);
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
    // ✅ 既に同じ名前のデータがある場合 → 「更新」処理
    $sql_update = "UPDATE users SET lat = ?, lng = ?, location_updated_at = NOW() WHERE username = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dds", $lat, $lng, $username);
    
    if ($stmt_update->execute()) {
        echo json_encode([
            "success" => "位置情報を更新しました",
            "action" => "update",
            "username" => $username,
            "lat" => $lat,
            "lng" => $lng
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "error" => "更新に失敗しました: " . $stmt_update->error
        ], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt_update->close();
} else {
    // 🆕 同じ名前のデータがない場合 → 「新規追加」処理
    $sql_insert = "INSERT INTO users (username, lat, lng, location_updated_at) VALUES (?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sdd", $username, $lat, $lng);
    
    if ($stmt_insert->execute()) {
        echo json_encode([
            "success" => "位置情報を追加しました",
            "action" => "insert",
            "username" => $username,
            "lat" => $lat,
            "lng" => $lng
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "error" => "追加に失敗しました: " . $stmt_insert->error
        ], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt_insert->close();
}

// --- 使用したステートメントと接続を閉じる ---
$stmt->close();
$conn->close();
?>