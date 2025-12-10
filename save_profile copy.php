<?php
require_once 'upload_config.php';
session_start();

// データベース接続設定
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$dbname = "dokodoko";
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'データベース接続エラー']);
    exit;
}

// レスポンスヘッダー設定
header('Content-Type: application/json');

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '認証が必要です']);
    exit;
}

$userId = $_SESSION['user_id'];

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

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => '氏名を入力してください']);
    exit;
}

try {
    // ユーザー情報を更新（usernameカラムを使用）
    $stmt = $pdo->prepare("
        UPDATE users 
        SET username = :name
        WHERE id = :user_id
    ");
    
    $result = $stmt->execute([
        'name' => $name,
        'user_id' => $userId
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'プロフィールが保存されました',
            'data' => [
                'name' => $name
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => '保存に失敗しました']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'データベースエラー: ' . $e->getMessage()]);
}
?>