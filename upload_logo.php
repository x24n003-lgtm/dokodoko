<?php
session_start();

// エラー表示を有効化（開発時のみ）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// データベース接続設定（実際の値に変更してください）
$host = "172.16.199.21";  // Linux MariaDB の IP
$user = "x24n007";
$pass = "n051211";
$dbname = "dokodoko";  // ← 名前を $dbname に変更
$port = 3306;

// レスポンスヘッダー設定
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'データベース接続エラー: ' . $e->getMessage()
    ]);
    exit;
}

// POSTリクエストのチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => '不正なリクエストです']);
    exit;
}

// ファイルがアップロードされているかチェック
if (!isset($_FILES['logo'])) {
    echo json_encode(['success' => false, 'error' => 'ファイルが送信されていません']);
    exit;
}

$file = $_FILES['logo'];

// アップロードエラーの詳細チェック
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'ファイルサイズがphp.iniの設定を超えています',
        UPLOAD_ERR_FORM_SIZE => 'ファイルサイズがフォームの設定を超えています',
        UPLOAD_ERR_PARTIAL => 'ファイルが部分的にしかアップロードされませんでした',
        UPLOAD_ERR_NO_FILE => 'ファイルがアップロードされませんでした',
        UPLOAD_ERR_NO_TMP_DIR => '一時フォルダが見つかりません',
        UPLOAD_ERR_CANT_WRITE => 'ディスクへの書き込みに失敗しました',
        UPLOAD_ERR_EXTENSION => 'PHP拡張によってアップロードが停止されました',
    ];
    
    $errorMsg = isset($errorMessages[$file['error']]) 
        ? $errorMessages[$file['error']] 
        : '不明なエラー (コード: ' . $file['error'] . ')';
    
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

// ファイルサイズチェック（5MB以下）
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'ファイルサイズが大きすぎます（最大5MB）']);
    exit;
}

// ファイルが実際にアップロードされたファイルかチェック
if (!is_uploaded_file($file['tmp_name'])) {
    echo json_encode(['success' => false, 'error' => '不正なファイルアップロードです']);
    exit;
}

// ファイルタイプチェック
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode([
        'success' => false, 
        'error' => '許可されていないファイル形式です（JPEG, PNG, GIF, WebPのみ）。検出されたタイプ: ' . $mimeType
    ]);
    exit;
}

// 拡張子取得
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// アップロードディレクトリ作成
$uploadDir = __DIR__ . '/uploads/logos/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'アップロードディレクトリの作成に失敗しました']);
        exit;
    }
}

// ディレクトリの書き込み権限チェック
if (!is_writable($uploadDir)) {
    echo json_encode(['success' => false, 'error' => 'アップロードディレクトリに書き込み権限がありません']);
    exit;
}

// ユニークなファイル名生成
$newFileName = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
$uploadPath = $uploadDir . $newFileName;

// ファイル移動
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'error' => 'ファイルの保存に失敗しました']);
    exit;
}

// データベースに保存
try {
    // 既存のロゴを削除（オプション）
    $stmt = $pdo->prepare("SELECT logo_path FROM settings WHERE id = 1");
    $stmt->execute();
    $oldLogo = $stmt->fetchColumn();
    
    if ($oldLogo && file_exists($oldLogo)) {
        @unlink($oldLogo); // 古いファイルを削除（エラーは無視）
    }
    
    // 新しいロゴパスを保存（相対パス）
    $relativePath = 'uploads/logos/' . $newFileName;
    
    $stmt = $pdo->prepare("
        INSERT INTO settings (id, logo_path, updated_at) 
        VALUES (1, :logo_path, NOW())
        ON DUPLICATE KEY UPDATE 
        logo_path = :logo_path, 
        updated_at = NOW()
    ");
    
    $stmt->execute(['logo_path' => $relativePath]);
    
    echo json_encode([
        'success' => true,
        'message' => 'ロゴが正常にアップロードされました',
        'logo_path' => $relativePath,
        'logo_url' => '/' . $relativePath
    ]);
    
} catch (PDOException $e) {
    // データベースエラーの場合、アップロードしたファイルを削除
    if (file_exists($uploadPath)) {
        @unlink($uploadPath);
    }
    
    echo json_encode(['success' => false, 'error' => 'データベースエラー: ' . $e->getMessage()]);
}
?>