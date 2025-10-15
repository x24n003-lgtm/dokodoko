<?php
header("Content-Type: application/json; charset=UTF-8");

// DB接続
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sotuken";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "DB接続失敗: " . $conn->connect_error]));
}

// 学校（固定）の緯度経度
$school_lat = 35.704517;
$school_lng = 139.984413;

// DB内の全データ取得
$sql = "SELECT name, lat, lng FROM locations";
$result = $conn->query($sql);

$locations = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lat = (float)$row["lat"];
        $lng = (float)$row["lng"];

        // 距離を計算（メートル）
        $earth_radius = 6371000; // 地球の半径[m]
        $dLat = deg2rad($lat - $school_lat);
        $dLng = deg2rad($lng - $school_lng);
        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($school_lat)) * cos(deg2rad($lat)) *
             sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earth_radius * $c;

        // 判定（例：500m以内なら「校内」）
        $status = ($distance <= 500) ? "校内" : "校外";

        $locations[] = [
            "name" => $row["name"],
            "lat" => $lat,
            "lng" => $lng,
            "distance_m" => round($distance, 2),
            "status" => $status
        ];
    }
}

$conn->close();
echo json_encode($locations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
