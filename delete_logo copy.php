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

try {
    // 現在のロゴパスを取得
    $stmt = $pdo->prepare("SELECT logo_path FROM settings WHERE id = 1");
    $stmt->execute();
    $logoPath = $stmt->fetchColumn();
    
    if ($logoPath && file_exists($logoPath)) {
        echo json_encode([
            'success' => true,
            'logo_path' => $logoPath,
            'logo_url' => '/' . $logoPath
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ロゴが設定されていません'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'データベースエラー: ' . $e->getMessage()
    ]);
}
?>