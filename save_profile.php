<?php
session_start();

// データベース接続設定
$host = "172.16.199.21";  // Linux MariaDB の IP
$user = "x24n007";
$pass = "n051211";
$dbname = "dokodoko";  // ← 名前を $dbname に変更
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'データベース接続エラー']);
    exit;
}

// レスポンスヘッダー設定
header('Content-Type: application/json');

// ログインチェック（必要に応じて）
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'error' => '認証が必要です']);
//     exit;
// }
// $userId = $_SESSION['user_id'];

// 仮のユーザーID（実際はセッションから取得）
$userId = 1;

// POSTリクエストのチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => '不正なリクエストです']);
    exit;
}

// JSONデータを取得
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'JSONデータの解析に失敗しました']);
    exit;
}

// バリデーション
$name = isset($data['name']) ? trim($data['name']) : null;
$birthday = isset($data['birthday']) ? trim($data['birthday']) : null;

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => '氏名を入力してください']);
    exit;
}

if (empty($birthday)) {
    echo json_encode(['success' => false, 'error' => '生年月日を入力してください']);
    exit;
}

// 生年月日の形式チェック
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
    echo json_encode(['success' => false, 'error' => '生年月日の形式が正しくありません']);
    exit;
}

try {
    // ユーザー情報を更新
    $stmt = $pdo->prepare("
        UPDATE users 
        SET name = :name, birthday = :birthday, updated_at = NOW() 
        WHERE id = :user_id
    ");
    
    $result = $stmt->execute([
        'name' => $name,
        'birthday' => $birthday,
        'user_id' => $userId
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'プロフィールが保存されました',
            'data' => [
                'name' => $name,
                'birthday' => $birthday
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => '保存に失敗しました']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'データベースエラー: ' . $e->getMessage()]);
}
?>