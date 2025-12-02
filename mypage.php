<?php
require_once 'db_config.php';
session_start();

// ------------------ ログイン確認 ------------------
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$userEmail = $_SESSION['email'];
$pdo = getDbConnection();

// ------------------ ユーザー情報取得 ------------------
$stmt = $pdo->prepare("SELECT username, logo_image FROM users WHERE email = ?");
$stmt->execute([$userEmail]);
$user = $stmt->fetch();

$username = $user['username'] ?? "";
$logoImage = $user['logo_image'] ?? "";
if (empty($logoImage)) {
    $logoImage = "default_icon.png"; // デフォルト画像
}

// メッセージの取得
$message = $_SESSION['profile_message'] ?? '';
$messageType = $_SESSION['profile_message_type'] ?? '';

// メッセージを表示後、セッションから削除
unset($_SESSION['profile_message']);
unset($_SESSION['profile_message_type']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ</title>
    <link rel="stylesheet" href="mypage.css">
</head>
<body>

<!-- ステータスバー -->
<div class="status-bar">
    <span><?php echo date('G:i'); ?></span>
     <div class="status-icons">
        <span>📶</span>
        <span>📡</span>
        <span>🔋</span>
    </div>
</div>

<div class="container">

    <header class="header">
        <h1 class="header-title">マイページ</h1>
    </header>

    <?php if($message): ?>
        <div class="message message-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="main-content">

        <!-- 🔻 編集保存フォーム 🔻 -->
        <form action="s_save_profile.php" method="POST" enctype="multipart/form-data">

            <!-- プロフィール画像 -->
            <div class="profile-section">
                <div class="profile-image-container">
                    <img id="profileImage" class="profile-image" 
                         src="<?= htmlspecialchars($logoImage) ?>?t=<?= time() ?>" 
                         alt="プロフィール画像"
                         onclick="document.getElementById('imageUpload').click();">

                    <input type="file" id="imageUpload" name="profile_image" accept="image/*" style="display:none;">
                </div>

                <button type="button" class="edit-btn" onclick="toggleEdit()">編集</button>
                <button type="submit" id="saveBtn" class="save-btn" style="display:none;">保存</button>
            </div>

             

            <!-- プロフィール詳細 -->
            <div class="profile-details">
                <div class="detail-row">
                    <span class="detail-label">氏名</span>
                    <div class="detail-value">
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($username) ?>" readonly>
                    </div>
                </div>
            </div>

        </form>

        <!-- ログアウト -->
        <div class="logout-section">
            <input type="button" class="logout-btn" onclick="location.href='logout.php'" value="ログアウト">
        </div>

    </div>

    <!-- ▼ボトムナビ -->
    <div class="bottom-nav">
        <a href="karennda-.php" class="nav-item">
            <span class="nav-text">出席</span>
        </a>
        <a href="chatp.php" class="nav-item">
            <span class="nav-text">チャット</span>
        </a>
        <a href="mypage.php" class="nav-item active">
            <span class="nav-text">マイページ</span>
        </a>
    </div>

</div>


<script>
let isEditing = false;

function toggleEdit() {
    isEditing = !isEditing;
    const nameInput = document.getElementById('name');
    const editBtn = document.querySelector('.edit-btn');
    const saveBtn = document.getElementById('saveBtn');

    if (isEditing) {
        nameInput.removeAttribute('readonly');
        nameInput.focus();
        editBtn.style.display = "none";
        saveBtn.style.display = "block";
    }
}

// 画像選択時のプレビュー
document.getElementById('imageUpload').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('profileImage').src = event.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
        
        // 編集モードを有効化
        if (!isEditing) {
            toggleEdit();
        }
    }
});
</script>

</body>
</html>