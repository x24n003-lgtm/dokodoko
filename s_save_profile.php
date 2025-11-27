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
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $_SESSION['profile_message'] = 'データベース接続エラー';
    $_SESSION['profile_message_type'] = 'error';
    header('Location: mypage.php');
    exit;
}

// ログインチェック
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

$userEmail = $_SESSION['email'];

// ユーザーIDを取得
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$userEmail]);
    $userData = $stmt->fetch();
    
    if (!$userData) {
        $_SESSION['profile_message'] = 'ユーザーが見つかりません';
        $_SESSION['profile_message_type'] = 'error';
        header('Location: mypage.php');
        exit;
    }
    
    $userId = $userData['id'];
} catch (PDOException $e) {
    $_SESSION['profile_message'] = 'ユーザー情報の取得に失敗しました';
    $_SESSION['profile_message_type'] = 'error';
    header('Location: mypage.php');
    exit;
}

// POSTリクエストのチェック
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: mypage.php');
    exit;
}

// 画像アップロード処理
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    try {
        $file = $_FILES['profile_image'];
        
        // アップロードディレクトリを確保
        ensureUploadDirectory(LOGO_DIR);
        
        // ファイル拡張子チェック
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $_SESSION['profile_message'] = 'jpg, jpeg, png, gif のみアップロード可能です';
            $_SESSION['profile_message_type'] = 'error';
            header('Location: mypage.php');
            exit;
        }
        
        // ファイル名生成
        $fileName = 'logo_' . $userId . '_' . time() . '.' . $fileExtension;
        $uploadPath = LOGO_DIR . $fileName;
        
        // 古い画像を削除
        $stmt = $pdo->prepare("SELECT logo_image FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $oldData = $stmt->fetch();
        
        if ($oldData && $oldData['logo_image']) {
            // 古いファイルのパスを生成
            $oldFilePath = $oldData['logo_image'];
            if (!file_exists($oldFilePath)) {
                $oldFilePath = LOGO_DIR . basename($oldData['logo_image']);
            }
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        
        // ファイルを移動
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // DBにはファイル名のみを保存（表示時にURLに変換）
            $dbPath = 'uploads/logos/' . $fileName;
            
            $stmt = $pdo->prepare("UPDATE users SET logo_image = ? WHERE id = ?");
            $stmt->execute([$dbPath, $userId]);
            
            $_SESSION['profile_message'] = 'プロフィール画像が更新されました';
            $_SESSION['profile_message_type'] = 'success';
            header('Location: mypage.php');
            exit;
        } else {
            $_SESSION['profile_message'] = 'ファイルのアップロードに失敗しました';
            $_SESSION['profile_message_type'] = 'error';
            header('Location: mypage.php');
            exit;
        }
        
    } catch (Exception $e) {
        $_SESSION['profile_message'] = '画像アップロードエラー';
        $_SESSION['profile_message_type'] = 'error';
        header('Location: mypage.php');
        exit;
    }
}

// 通常のフォームデータ処理（氏名のみ）
$name = isset($_POST['name']) ? trim($_POST['name']) : null;

// バリデーション
if (empty($name)) {
    $_SESSION['profile_message'] = '氏名を入力してください';
    $_SESSION['profile_message_type'] = 'error';
    header('Location: mypage.php');
    exit;
}

try {
    // ユーザー情報を更新
    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
    $result = $stmt->execute([$name, $userId]);
    
    if ($result) {
        $_SESSION['profile_message'] = 'プロフィールが保存されました';
        $_SESSION['profile_message_type'] = 'success';
    } else {
        $_SESSION['profile_message'] = '保存に失敗しました';
        $_SESSION['profile_message_type'] = 'error';
    }
    
} catch (PDOException $e) {
    $_SESSION['profile_message'] = 'データベースエラー';
    $_SESSION['profile_message_type'] = 'error';
}

header('Location: mypage.php');
exit;
?>