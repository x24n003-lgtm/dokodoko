<?php
require_once 'upload_config.php';
session_start();

// ------------------ データベース接続 ------------------
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";
$port = 3306;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("データベース接続エラー: " . $e->getMessage());
}

$message = '';
$messageType = '';
$userId = $_SESSION['user_id'];

// ------------------ 名前の更新 ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_username'])) {
    try {
        $newUsername = trim($_POST['username']);
        if (empty($newUsername)) {
            throw new Exception('名前を入力してください。');
        }
        if (mb_strlen($newUsername) > 50) {
            throw new Exception('名前は50文字以内で入力してください。');
        }
        
        $update_stmt = $pdo->prepare("UPDATE users SET username=:username WHERE id=:user_id");
        $update_stmt->execute([':username'=>$newUsername, ':user_id'=>$userId]);
        
        $message = '名前が更新されました';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'エラー: '.$e->getMessage();
        $messageType = 'error';
        error_log("名前更新エラー: ".$e->getMessage());
    }
}

// ------------------ プロフィール画像のアップロード ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo_image'])) {
    try {
        $file = $_FILES['logo_image'];

        if ($file['error'] === UPLOAD_ERR_OK) {

            ensureUploadDirectory(LOGO_DIR);

            // 拡張子チェック
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];
            if (!in_array($ext, $allowed)) {
                throw new Exception('jpg, jpeg, png, gif のみアップロードできます');
            }

            // 保存ファイル名
            $fileName = 'logo_'.$userId.'_'.time().'.'.$ext;
            $savePath = LOGO_DIR . $fileName;     // 物理パス
            $dbPath   = 'logos/' . $fileName;     // DBには相対パス（uploads/logos は付けない）

            // 古い画像削除
            $stmt = $pdo->prepare("SELECT logo_image FROM users WHERE id=:uid");
            $stmt->execute([':uid'=>$userId]);
            $old = $stmt->fetchColumn();

            if ($old && imageExists($old)) {
                $oldFullPath = UPLOAD_BASE_DIR . $old;
                if (file_exists($oldFullPath)) unlink($oldFullPath);
            }

            // 保存
            if (!move_uploaded_file($file['tmp_name'], $savePath)) {
                throw new Exception('画像保存に失敗しました');
            }

            // DB へ保存（logos/xxx.jpg）
            $up = $pdo->prepare("UPDATE users SET logo_image=:img WHERE id=:uid");
            $up->execute([':img'=>$dbPath, ':uid'=>$userId]);

            $message = "プロフィール画像が更新されました";
            $messageType = "success";
        }

    } catch (Exception $e) {
        $message = "エラー: ".$e->getMessage();
        $messageType = "error";
    }
}

// ------------------ 画像削除 ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_logo'])) {
    try {
        $stmt = $pdo->prepare("SELECT logo_image FROM users WHERE id=:user_id");
        $stmt->execute([':user_id'=>$userId]);
        $data = $stmt->fetch();

        if ($data && $data['logo_image']) {
            $fileName = $data['logo_image'];               // logos/xxx.jpg
            $fullPath = UPLOAD_BASE_DIR . $fileName;       // 物理パス

            if (file_exists($fullPath)) unlink($fullPath);
        }

        $del_stmt = $pdo->prepare("UPDATE users SET logo_image=NULL WHERE id=:user_id");
        $del_stmt->execute([':user_id'=>$userId]);

        $message = 'プロフィール画像を削除しました';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'エラー: '.$e->getMessage();
        $messageType = 'error';
        error_log("画像削除エラー: ".$e->getMessage());
    }
}

// ------------------ プロフィール情報取得 ------------------
$stmt = $pdo->prepare("SELECT username, logo_image FROM users WHERE id=:user_id");
$stmt->execute([':user_id'=>$userId]);
$profile_data = $stmt->fetch();
$userName = $profile_data['username'] ?? '';
$logoImage = $profile_data['logo_image'] ?? '';

// デフォルトアイコン
$defaultIcon = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="%23bdbdbd"/><circle cx="50" cy="37" r="15" fill="%23ffffff"/><path d="M 30 65 Q 30 55 50 55 Q 70 55 70 65 L 70 85 Q 70 90 50 90 Q 30 90 30 85 Z" fill="%23ffffff"/></svg>';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>マイページ - 教員</title>
<link rel="stylesheet" href="mypage.css">
</head>
<body>
<div class="container">
    <div class="header">
        <a href="syusseki.php" class="back-btn">← 戻る</a>
        <h1 class="header-title">マイページ</h1>
    </div>

    <?php if($message): ?>
        <div class="message message-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- プロフィール画像 -->
    <div class="logo-section">

        <div class="logo-preview" onclick="triggerFileSelect()">
            <?php if($logoImage && imageExists($logoImage)): ?>
                <img src="<?php echo getImageUrl($logoImage); ?>?t=<?php echo time(); ?>" id="logoPreview">
            <?php else: ?>
                <img src="<?php echo $defaultIcon; ?>" id="logoPreview">
            <?php endif; ?>
            <div class="edit-overlay">📷</div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="file" name="logo_image" id="logoInput" class="logo-file-input" onchange="previewAndSubmit()">
        </form>

        <?php if($logoImage && imageExists($logoImage)): ?>
            <form method="POST" onsubmit="return confirm('本当に削除しますか？');">
                <input type="hidden" name="delete_logo" value="1">
                <button type="submit" class="logo-btn logo-delete-btn">🗑️ 画像を削除</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- プロフィール詳細 -->
    <div class="profile-details">
        <form method="POST" id="usernameForm">
            <div class="detail-row">
                <span class="detail-label">氏名</span>
                <div class="detail-value">
                    <input type="text" name="username" id="usernameInput" value="<?php echo htmlspecialchars($userName); ?>" readonly>
                    <button type="button" class="edit-btn" id="editBtn" onclick="enableEdit()">✏️ 編集</button>
                    <button type="submit" name="update_username" class="edit-btn save-btn" id="saveBtn" style="display:none;">💾 保存</button>
                    <button type="button" class="edit-btn cancel-btn" id="cancelBtn" style="display:none;" onclick="cancelEdit()">✕</button>
                </div>
            </div>
        </form>
    </div>

    <!-- ログアウト -->
    <div class="logout-section">
        <input type="button" class="logout-btn" onclick="location.href='logout.php'" value="ログアウト">
    </div>

    <!-- ボトムナビ -->
    <div class="bottom-nav">
        <a href="karennda-.php" class="nav-item">
            <div class="nav-icon person"></div>
            <span class="nav-text">出席</span>
        </a>
        <a href="chatp.php" class="nav-item">
            <div class="nav-icon message"></div>
            <span class="nav-text">チャット</span>
        </a>
        <a href="mypage.php" class="nav-item active">
            <div class="nav-icon settings"></div>
            <span class="nav-text">マイページ</span>
        </a>
    </div>
</div>

<script>
let originalUsername = "<?php echo htmlspecialchars($userName); ?>";

function triggerFileSelect() {
    document.getElementById('logoInput').click();
}

function previewAndSubmit() {
    const input = document.getElementById('logoInput');
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = function(e){
            document.getElementById('logoPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
        setTimeout(()=>{ document.getElementById('uploadForm').submit(); }, 300);
    }
}

function enableEdit() {
    const input = document.getElementById('usernameInput');
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    
    input.removeAttribute('readonly');
    input.focus();
    input.select();
    
    editBtn.style.display = 'none';
    saveBtn.style.display = 'inline-block';
    cancelBtn.style.display = 'inline-block';
}

function cancelEdit() {
    const input = document.getElementById('usernameInput');
    input.value = originalUsername;
    input.setAttribute('readonly', true);

    document.getElementById('editBtn').style.display = 'inline-block';
    document.getElementById('saveBtn').style.display = 'none';
    document.getElementById('cancelBtn').style.display = 'none';
}

document.getElementById('usernameForm').addEventListener('submit', function(e) {
    const input = document.getElementById('usernameInput');
    const value = input.value.trim();
    
    if (value === '') {
        e.preventDefault();
        alert('名前を入力してください。');
        return false;
    }
    
    if (value.length > 50) {
        e.preventDefault();
        alert('名前は50文字以内で入力してください。');
        return false;
    }
});
</script>
</body>
</html>
