<?php
header("Content-Type: application/json; charset=UTF-8");

// ===============================
// DB 接続
// ===============================
$conn = new mysqli("172.16.199.21", "x24n007", "n051211", "dokodoko");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "DB接続失敗"]);
    exit;
}

// ===============================
// ユーザー取得
// ===============================
$sql = "
    SELECT
        username,
        lat,
        lng,
        home_lat,
        home_lng
    FROM users
";

$res = $conn->query($sql);
$results = [];

// ===============================
// 距離計算
// ===============================
while ($row = $res->fetch_assoc()) {

    $lat  = $row['lat']  !== null ? (float)$row['lat']  : null;
    $lng  = $row['lng']  !== null ? (float)$row['lng']  : null;
    $hLat = $row['home_lat'] !== null ? (float)$row['home_lat'] : null;
    $hLng = $row['home_lng'] !== null ? (float)$row['home_lng'] : null;

    // どれか欠けてたら判定不可
    if ($lat === null || $lng === null || $hLat === null || $hLng === null) {
        $results[] = [
            "username" => $row['username'],
            "status" => "座標不足（判定不可）"
        ];
        continue;
    }

    // Haversine
    $lat1 = deg2rad($lat);
    $lng1 = deg2rad($lng);
    $lat2 = deg2rad($hLat);
    $lng2 = deg2rad($hLng);

    $earth = 6371; // km

    $d = 2 * $earth * asin(
        sqrt(
            pow(sin(($lat2 - $lat1) / 2), 2) +
            cos($lat1) * cos($lat2) *
            pow(sin(($lng2 - $lng1) / 2), 2)
        )
    );

    $results[] = [
        "username" => $row['username'],
        "distance_km" => round($d, 3),
        "status" => ($d < 0.1 ? "自宅付近" : "自宅外")
    ];
}

echo json_encode(
    $results,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
