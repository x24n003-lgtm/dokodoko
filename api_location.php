<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "ログインが必要です"]);
    exit();
}

// データベース接続
$host = "172.16.199.21";  // Linux MariaDB の IP
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB接続失敗: " . $conn->connect_error]);
    exit();
}

// 学校の緯度経度（fjb）
$school_lat = 35.704517;
$school_lng = 139.984413;
$school_radius = 500; // 校内判定の半径（メートル）

// リクエストメソッドによる処理分岐
$method = $_SERVER['REQUEST_METHOD'];

// ==================== GET: 位置情報取得 ====================
if ($method === 'GET') {
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    
    if ($user_type === 'teacher') {
        // 教員の場合：全学生の位置情報を取得
        $class_filter = isset($_GET['class']) ? $_GET['class'] : null;
        
        $sql = "SELECT u.id, u.username as name, u.class_name, u.lat, u.lng, u.location_updated_at,
                       a.status, a.location as attendance_location
                FROM users u
                LEFT JOIN attendance a ON u.id = a.user_id AND DATE(a.attendance_date) = CURDATE()
                WHERE u.user_type = 'student'";
        
        if ($class_filter) {
            $sql .= " AND u.class_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $class_filter);
        } else {
            $stmt = $conn->prepare($sql);
        }
        
    } else {
        // 学生の場合：自分の位置情報のみ取得
        $sql = "SELECT id, username as name, lat, lng, location_updated_at 
                FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $locations = [];
    while ($row = $result->fetch_assoc()) {
        $lat = (float)$row["lat"];
        $lng = (float)$row["lng"];
        
        if ($lat && $lng) {
            // 距離を計算（メートル）
            $distance = calculateDistance($school_lat, $school_lng, $lat, $lng);
            
            // 判定
            $status = ($distance <= $school_radius) ? "校内" : "校外";
            
            $locations[] = [
                "id" => $row["id"],
                "name" => $row["name"],
                "class" => isset($row["class_name"]) ? $row["class_name"] : null,
                "lat" => $lat,
                "lng" => $lng,
                "distance_m" => round($distance, 2),
                "status" => $status,
                "updated_at" => $row["location_updated_at"],
                "attendance_status" => isset($row["status"]) ? $row["status"] : null
            ];
        }
    }
    
    echo json_encode($locations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
// ==================== POST: 位置情報更新 ====================
} elseif ($method === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // JSONデータを取得
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['lat']) || !isset($data['lng'])) {
        http_response_code(400);
        echo json_encode(["error" => "緯度経度が必要です"]);
        exit();
    }
    
    $lat = (float)$data['lat'];
    $lng = (float)$data['lng'];
    
    // 距離を計算
    $distance = calculateDistance($school_lat, $school_lng, $lat, $lng);
    $location_status = ($distance <= $school_radius) ? "school" : "other";
    
    // usersテーブルを更新
    $sql = "UPDATE users SET lat = ?, lng = ?, location_updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddi", $lat, $lng, $user_id);
    
    if ($stmt->execute()) {
        // 今日の出席記録も更新
        $update_attendance = "UPDATE attendance 
                             SET latitude = ?, longitude = ?, location = ?
                             WHERE user_id = ? AND DATE(attendance_date) = CURDATE()";
        $stmt2 = $conn->prepare($update_attendance);
        $stmt2->bind_param("ddsi", $lat, $lng, $location_status, $user_id);
        $stmt2->execute();
        
        echo json_encode([
            "success" => true,
            "distance_m" => round($distance, 2),
            "status" => ($distance <= $school_radius) ? "校内" : "校外",
            "message" => "位置情報を更新しました"
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "更新に失敗しました"]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(["error" => "許可されていないメソッドです"]);
}

$conn->close();

// ==================== 距離計算関数 ====================
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371000; // 地球の半径[m]
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat / 2) ** 2 +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) ** 2;
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earth_radius * $c;
    
    return $distance;
}
?>