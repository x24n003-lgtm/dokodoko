<?php
header("Content-Type: application/json; charset=UTF-8");

// --- エラー表示を追加（デバッグ用） ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// --- 学校（固定）の緯度経度 ---
$school_lat = 35.704517;
$school_lng = 139.984413;

// --- DB内の全データ取得 ---
$sql = "SELECT username, lat, lng FROM users";
$result = $conn->query($sql);

$locations = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lat = (float)$row["lat"];
        $lng = (float)$row["lng"];

        // --- 距離を計算（メートル）---
        // Haversine公式を使用
        $earth_radius = 6371000; // 地球の半径[m]
        $dLat = deg2rad($lat - $school_lat);
        $dLng = deg2rad($lng - $school_lng);
        
        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($school_lat)) * cos(deg2rad($lat)) *
             sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earth_radius * $c;

        // --- 判定（500m以内なら「校内」）---
        $status = ($distance <= 500) ? "校内" : "校外";

        // --- 結果を配列に追加 ---
        $locations[] = [
            "username" => $row["username"],
            "lat" => $lat,
            "lng" => $lng,
            "distance_m" => round($distance, 2),
            "status" => $status
        ];
    }
} else {
    // データが0件の場合も正常なレスポンスを返す
    $locations = [];
}

// --- DB接続を閉じる ---
$conn->close();

// --- JSON出力 ---
echo json_encode($locations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>