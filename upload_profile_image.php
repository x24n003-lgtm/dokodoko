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

// ファイルがアップロードされているかチェック
if (!isset($_FILES['profile_image'])) {
    echo json_encode(['success' => false, 'error' => 'ファイルが送信されていません']);
    exit;
}

$file = $_FILES['profile_image'];

// アップロードエラーチェック
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'ファイルのアップロードに失敗しました']);
    exit;
}

// ファイルサイズチェック（5MB以下）
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'ファイルサイズが大きすぎます（最大5MB）']);
    exit;
}

// ファイルタイプチェック
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => '許可されていないファイル形式です']);
    exit;
}

// 拡張子取得
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// アップロードディレクトリ作成
$uploadDir = __DIR__ . '/uploads/profiles/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'ディレクトリの作成に失敗しました']);
        exit;
    }
}

// ユニークなファイル名生成
$newFileName = 'profile_' . $userId . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $newFileName;

// ファイル移動
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'error' => 'ファイルの保存に失敗しました']);
    exit;
}

// データベースに保存
try {
    // 既存のプロフィール画像を削除
    $stmt = $pdo->prepare("SELECT logo_image FROM users WHERE id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $oldImage = $stmt->fetchColumn();
    
    if ($oldImage && file_exists($oldImage)) {
        @unlink($oldImage);
    }
    
    // 新しい画像パスを保存
    $relativePath = 'uploads/profiles/' . $newFileName;
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET logo_image = :image_path 
        WHERE id = :user_id
    ");
    
    $stmt->execute([
        'image_path' => $relativePath,
        'user_id' => $userId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'プロフィール画像がアップロードされました',
        'image_url' => '/' . $relativePath
    ]);
    
} catch (PDOException $e) {
    // エラー時はアップロードしたファイルを削除
    if (file_exists($uploadPath)) {
        @unlink($uploadPath);
    }
    
    echo json_encode(['success' => false, 'error' => 'データベースエラー: ' . $e->getMessage()]);
}
?>