<?php
session_start();

// 教員のログインチェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

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
            $uploadDir = 'uploads/logos/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg','jpeg','png','gif'];
            if (!in_array($fileExtension, $allowedExtensions)) throw new Exception('jpg, jpeg, png, gif のみ可能です。');

            $fileName = 'logo_'.$userId.'_'.time().'.'.$fileExtension;
            $uploadPath = $uploadDir.$fileName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $old_stmt = $pdo->prepare("SELECT logo_image FROM users WHERE id=:user_id");
                $old_stmt->execute([':user_id'=>$userId]);
                $old_data = $old_stmt->fetch();
                if ($old_data && $old_data['logo_image'] && file_exists($old_data['logo_image'])) unlink($old_data['logo_image']);

                $update_stmt = $pdo->prepare("UPDATE users SET logo_image=:logo_image WHERE id=:user_id");
                $update_stmt->execute([':logo_image'=>$uploadPath, ':user_id'=>$userId]);

                $message = 'プロフィール画像が更新されました';
                $messageType = 'success';
            } else throw new Exception('アップロードに失敗しました。');
        }
    } catch (Exception $e) {
        $message = 'エラー: '.$e->getMessage();
        $messageType = 'error';
        error_log("画像アップロードエラー: ".$e->getMessage());
    }
}

// ------------------ 画像削除 ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_logo'])) {
    try {
        $stmt = $pdo->prepare("SELECT logo_image FROM users WHERE id=:user_id");
        $stmt->execute([':user_id'=>$userId]);
        $data = $stmt->fetch();
        if ($data && $data['logo_image'] && file_exists($data['logo_image'])) unlink($data['logo_image']);
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
$defaultIcon = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="%23bdbdbd"/><circle cx="50" cy="35" r="15" fill="%23ffffff"/><path d="M 30 75 Q 30 55 50 55 Q 70 55 70 75 Z" fill="%23ffffff"/></svg>';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>マイページ - 教員</title>
<style>
/* ===== 全体 ===== */
body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:#f5f5f5; margin:0; padding:0; }
.container { max-width:414px; margin:0 auto; background:#fff; min-height:100vh; padding:20px; box-sizing:border-box; }

/* ===== ヘッダー ===== */
.header { display:flex; align-items:center; margin-bottom:20px; }
.back-btn { text-decoration:none; color:#007bff; margin-right:10px; }
.header-title { font-size:1.5rem; }

/* ===== メッセージ ===== */
.message { padding:10px; margin-bottom:15px; border-radius:6px; font-size:0.9rem; }
.message-success { background-color:#d4edda; color:#155724; }
.message-error { background-color:#f8d7da; color:#721c24; }

/* ===== プロフィール画像 ===== */
.logo-section { text-align:center; margin-bottom:30px; }
.logo-title { font-size:1.2rem; margin-bottom:5px; }
.logo-description { font-size:0.9rem; color:#666; margin-bottom:15px; }
.logo-preview { position:relative; display:inline-block; width:120px; height:120px; border-radius:50%; overflow:hidden; cursor:pointer; border:2px solid #ddd; transition:border-color 0.3s; }
.logo-preview:hover { border-color:#007bff; }
.logo-preview img { width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.3s; }
.logo-preview:hover img { transform:scale(1.05); }
.edit-overlay { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); font-size:2rem; color:white; background:rgba(0,0,0,0.5); width:40px; height:40px; line-height:40px; border-radius:50%; text-align:center; opacity:0; pointer-events:none; transition:opacity 0.3s, transform 0.3s; }
.logo-preview:hover .edit-overlay { opacity:1; transform:translate(-50%,-50%) scale(1.1); }
.logo-file-input { display:none; }
.logo-btn { padding:6px 12px; font-size:0.9rem; border:none; border-radius:6px; cursor:pointer; }
.logo-delete-btn { background-color:#ff4d4f; color:white; transition:background 0.3s; margin-top:10px; }
.logo-delete-btn:hover { background-color:#e04344; }

/* ===== プロフィール詳細 ===== */
.profile-details { margin-bottom:20px; }
.detail-row { display:flex; align-items:center; margin-bottom:10px; }
.detail-label { flex:0 0 70px; font-weight:bold; }
.detail-value { flex:1; display:flex; align-items:center; gap:10px; }
.detail-value input { flex:1; padding:8px 10px; border:1px solid #ccc; border-radius:6px; font-size:0.9rem; }
.detail-value input:focus { outline:none; border-color:#007bff; }
.edit-btn { padding:6px 12px; font-size:0.85rem; background:#007bff; color:white; border:none; border-radius:6px; cursor:pointer; transition:0.3s; white-space:nowrap; }
.edit-btn:hover { background:#0056b3; }
.save-btn { background:#28a745; }
.save-btn:hover { background:#218838; }
.cancel-btn { background:#6c757d; }
.cancel-btn:hover { background:#5a6268; }

/* 換算値ボタン */
.kansanti-btn {
    padding:8px 12px;
    font-size:0.9rem;
    background:#ff9800;
    color:#fff;
    border:none;
    border-radius:6px;
    cursor:pointer;
    transition:background 0.3s;
    white-space:nowrap;
}
.kansanti-btn:hover {
    background:#fb8c00;
}

/* ===== カレンダー管理 ===== */
.calendar-management { text-align:center; margin-bottom:30px; }
.calendar-management h3 { font-size:1.1rem; margin-bottom:5px; }
.calendar-management p { font-size:0.9rem; color:#666; margin-bottom:10px; }
.calendar-link { display:inline-block; text-decoration:none; color:#007bff; font-weight:bold; padding:6px 12px; border:1px solid #007bff; border-radius:6px; transition:0.3s; }
.calendar-link:hover { background:#007bff; color:#fff; }

/* ===== ボトムナビ ===== */
.bottom-nav { position:fixed; bottom:0; left:0; width:100%; background:#fff; border-top:1px solid #ddd; display:flex; justify-content:space-around; padding:5px 0; }
.nav-item { text-align:center; text-decoration:none; color:#666; font-size:0.8rem; }
.nav-item.active { color:#007bff; }
.nav-icon { width:24px; height:24px; margin:0 auto 3px; background-size:contain; background-repeat:no-repeat; }
.nav-icon.person { background-image:url('icons/person.svg'); }
.nav-icon.message { background-image:url('icons/message.svg'); }
.nav-icon.settings { background-image:url('icons/settings.svg'); }
</style>
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
            <?php if($logoImage && file_exists($logoImage)): ?>
                <img src="<?php echo htmlspecialchars($logoImage); ?>?t=<?php echo time(); ?>" id="logoPreview">
            <?php else: ?>
                <img src="<?php echo $defaultIcon; ?>" id="logoPreview">
            <?php endif; ?>
            <div class="edit-overlay">📷</div>
        </div>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="file" name="logo_image" id="logoInput" class="logo-file-input" onchange="previewAndSubmit()">
        </form>

        <?php if($logoImage && file_exists($logoImage)): ?>
            <form method="POST" onsubmit="return confirm('本当に削除しますか？');">
                <input type="hidden" name="delete_logo" value="1">
                <button type="submit" class="logo-btn logo-delete-btn">🗑️ 画像を削除</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- プロフィール詳細 -->
    <div class="profile-details">
        <form method="POST" id="usernameForm">
            <!-- 氏名 -->
            <div class="detail-row">
                <span class="detail-label">氏名</span>
                <div class="detail-value">
                    <input type="text" name="username" id="usernameInput" value="<?php echo htmlspecialchars($userName); ?>" readonly>
                    <button type="button" class="edit-btn" id="editBtn" onclick="enableEdit()">✏️ 編集</button>
                    <button type="submit" name="update_username" class="edit-btn save-btn" id="saveBtn" style="display:none;">💾 保存</button>
                    <button type="button" class="edit-btn cancel-btn" id="cancelBtn" style="display:none;" onclick="cancelEdit()">✕</button>
                </div>
            </div>

            <!-- 生年月日（今はダミー。必要ならDBから取得するようにしてOK） -->
            <div class="detail-row">
                <span class="detail-label">生年月日</span>
                <div class="detail-value">
                    <input type="date" id="birthdayInput" value="2000-12-01" readonly>
                </div>
            </div>

            <!-- 換算値設定ボタン（生年月日の下） -->
            <div class="detail-row">
                <span class="detail-label">換算値</span>
                <div class="detail-value">
                    <button type="button" class="kansanti-btn" onclick="goToKansanti()">
                        生徒の換算値を設定する
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- カレンダー管理 -->
    <div class="calendar-management">
        <a href="teacher_calendar.php" class="calendar-link">カレンダーを管理する →</a>
    </div>

    <!-- ボトムナビ -->
    <div class="bottom-nav">
        <a href="syusseki.php" class="nav-item">
            <div class="nav-icon person"></div>
            <span class="nav-text">出席</span>
        </a>
        <a href="teacherchat.php" class="nav-item">
            <div class="nav-icon message"></div>
            <span class="nav-text">チャット</span>
        </a>
        <a href="teachermypage.php" class="nav-item active">
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
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    
    input.value = originalUsername;
    input.setAttribute('readonly', true);
    
    editBtn.style.display = 'inline-block';
    saveBtn.style.display = 'none';
    cancelBtn.style.display = 'none';
}

// フォーム送信時のバリデーション
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

// 換算値設定画面へ遷移
function goToKansanti() {
    // 今は固定でkansanti.phpに遷移
    // 生徒ごとのIDを渡したい場合は、ここに ?student_id=◯ を付けるイメージ
    window.location.href = 'kansanti.php';
}
</script>
</body>
</html>