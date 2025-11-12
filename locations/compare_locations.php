<?php
header("Content-Type: application/json; charset=UTF-8");

// DB 接続
$conn = new mysqli("172.16.199.21", "x24n007", "n051211", "dokodoko");
$conn->set_charset("utf8mb4");


if ($conn->connect_error) {
    echo json_encode(["error" => "DB接続失敗"]);
    exit;
}

// 学校位置
$schoolLat = 35.704517;
$schoolLng = 139.984413;

// 全ユーザー取得
$res = $conn->query("SELECT username, lat, lng FROM users");

$results = [];

while ($row = $res->fetch_assoc()) {

    // lat/lng が NULL のユーザー → スキップ
    $lat = $row["lat"] !== null ? (float)$row["lat"] : null;
    $lng = $row["lng"] !== null ? (float)$row["lng"] : null;
    
    if ($lat === null || $lng === null) {
        $results[] = [
            "username" => $row["username"],
            "status" => "位置情報なし（判定不可）"
        ];
        continue;
    }
    
    // 距離計算（Haversine）
    $lat1 = deg2rad($row["lat"]);
    $lng1 = deg2rad($row["lng"]);
    $lat2 = deg2rad($schoolLat);
    $lng2 = deg2rad($schoolLng);

    $earth = 6371; // 地球の半径（km）

    $d = 2 * $earth * asin(
        sqrt(
            pow(sin(($lat2 - $lat1) / 2), 2) +
            cos($lat1) * cos($lat2) * pow(sin(($lng2 - $lng1) / 2), 2)
        )
    );

    $results[] = [
        "username" => $row["username"],
        "distance_km" => round($d, 3),
        "status" => ($d < 0.1 ? "学校内" : "学校外")
    ];
}

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
