<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// データベース設定
$host = 'localhost';
$dbname = 'school_app';
$username = 'your_username';
$password = 'your_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'データベース接続エラー']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // 生徒の位置情報を取得
        $sql = "
            SELECT 
                s.id,
                s.name,
                l.latitude,
                l.longitude,
                l.timestamp
            FROM students s
            LEFT JOIN (
                SELECT 
                    student_id,
                    latitude,
                    longitude,
                    timestamp,
                    ROW_NUMBER() OVER (PARTITION BY student_id ORDER BY timestamp DESC) as rn
                FROM student_locations
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ) l ON s.id = l.student_id AND l.rn = 1
            WHERE s.active = 1
            ORDER BY s.name
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // タイムスタンプをISO形式に変換
        foreach ($students as &$student) {
            if ($student['timestamp']) {
                $student['timestamp'] = date('c', strtotime($student['timestamp']));
            } else {
                // 位置情報がない場合のデフォルト値
                $student['latitude'] = null;
                $student['longitude'] = null;
                $student['timestamp'] = null;
            }
        }
        
        echo json_encode($students);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'データ取得エラー']);
    }
}

// 位置情報を更新するためのPOSTエンドポイント
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['student_id'], $input['latitude'], $input['longitude'])) {
        http_response_code(400);
        echo json_encode(['error' => '必要なパラメータが不足しています']);
        exit;
    }
    
    try {
        $sql = "INSERT INTO student_locations (student_id, latitude, longitude, timestamp) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['student_id'],
            $input['latitude'],
            $input['longitude']
        ]);
        
        echo json_encode(['success' => true, 'message' => '位置情報を更新しました']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '位置情報の更新に失敗しました']);
    }
}
?>