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

try {
    // ユーザー情報を取得
    $stmt = $pdo->prepare("
        SELECT username, birthday, logo_image 
        FROM users 
        WHERE id = :user_id
    ");
    
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // プロフィール画像のURLを生成
        $profileImageUrl = null;
        if ($user['logo_image'] && file_exists($user['logo_image'])) {
            $profileImageUrl = '/' . $user['logo_image'];
        }
        
        echo json_encode([
            'success' => true,
            'name' => $user['username'],
            'birthday' => $user['birthday'],
            'profile_image' => $profileImageUrl
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'ユーザー情報が見つかりません'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'データベースエラー: ' . $e->getMessage()]);
}
?>