<?php
require_once 'upload_config.php';
session_start();


// -------------------------------
// 安全対策：imageExists / getImageUrl が未定義の場合だけ定義する
// upload_config.php が既に定義している可能性があるため、二重定義を防ぐ
// -------------------------------
if (!function_exists('imageExists')) {
    /**
     * imageExists
     * 相対パス (例: uploads/logos/xxx.jpg) や保存方法の違いに対応して物理ファイルの存在を確認する。
     * @param string|null $fileName DB に保存されている値（相対パスやファイル名）
     * @return bool
     */
    function imageExists($fileName) {
        if (empty($fileName)) return false;

        // 候補となる物理パスを順にチェック
        $candidates = [];

        // upload_config.php で定義されている定数があれば利用
        if (defined('UPLOAD_BASE_DIR')) {
            // 例: UPLOAD_BASE_DIR + uploads/logos/xxx.jpg
            $candidates[] = rtrim(UPLOAD_BASE_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($fileName, '/\\');
        }

        // 現在のスクリプト相対パスからの候補
        $candidates[] = __DIR__ . DIRECTORY_SEPARATOR . ltrim($fileName, '/\\');

        // LOGO_DIR が定義されている場合は basename も試す（保存が logos/xxx.jpg だけだったケース向け）
        if (defined('LOGO_DIR')) {
            $candidates[] = rtrim(LOGO_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($fileName);
        }

        foreach ($candidates as $p) {
            if ($p && file_exists($p)) return true;
        }
        return false;
    }
}

if (!function_exists('getImageUrl')) {
    /**
     * getImageUrl
     * DB に保存された値をブラウザで参照できる URL に変換する。
     * 返り値は表示用 URL またはファイル名（必要に応じて upload_config.php の定義と合わせる）。
     *
     * @param string|null $fileName DB に保存されたパス・ファイル名
     * @return string URL もしくはそのままの値（無ければ default_icon.png を返す）
     */
    function getImageUrl($fileName) {
        if (empty($fileName)) return 'default_icon.png';

        // 既に http(s) で始まる（外部URLなど）はそのまま返す
        if (preg_match('#^https?://#i', $fileName)) {
            return $fileName;
        }

        // upload_config.php の定数に合わせた変換
        if (defined('UPLOAD_BASE_URL') && strpos($fileName, 'uploads/') === 0) {
            // ファイル名が uploads/... で保存されているケース
            return rtrim(UPLOAD_BASE_URL, '/') . '/' . ltrim(substr($fileName, strlen('uploads/')), '/');
        }

        // ファイル名だけ（例: logo_123_1600000000.jpg）なら LOGO_URL に付ける
        if (!str_contains($fileName, '/')) {
            if (defined('LOGO_URL')) {
                return rtrim(LOGO_URL, '/') . '/' . $fileName;
            }
            return 'uploads/logos/' . $fileName;
        }

        // その他（相対パス）ならそのまま返す（Apache / Nginx が解決する想定）
        return $fileName;
    }
}

// 教員のログインチェック（画面表示は既存と同じにする）
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

// ------------------ DB 接続（元コードと同じ） ------------------
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

// ------------------ 初期化 ------------------
$message = '';
$messageType = '';
$userId = $_SESSION['user_id'];

// フラッシュメッセージ取得（PRG 後も表示するため）
if (!empty($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_message_type'] ?? 'info';
    unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);
}

// ------------------ POST 処理（PRG を適用） ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- 名前の更新 ---
    if (isset($_POST['update_username'])) {
        try {
            $newUsername = trim((string)($_POST['username'] ?? ''));
            if ($newUsername === '') {
                throw new Exception('名前を入力してください。');
            }
            if (mb_strlen($newUsername) > 50) {
                throw new Exception('名前は50文字以内で入力してください。');
            }

            $update_stmt = $pdo->prepare("UPDATE users SET username=:username WHERE id=:user_id");
            $update_stmt->execute([':username' => $newUsername, ':user_id' => $userId]);

            $_SESSION['flash_message'] = '名前が更新されました';
            $_SESSION['flash_message_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'エラー: ' . $e->getMessage();
            $_SESSION['flash_message_type'] = 'error';
        }

        // PRG
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // --- 画像削除 ---
    if (isset($_POST['delete_logo'])) {
        try {
            $stmt = $pdo->prepare("SELECT logo_image FROM users WHERE id=:user_id");
            $stmt->execute([':user_id' => $userId]);
            $data = $stmt->fetch();
            if ($data && $data['logo_image']) {
                $old = $data['logo_image'];

                // 物理パス候補を複数試す（環境差対策）
                $candidates = [];
                if (defined('UPLOAD_BASE_DIR')) {
                    $candidates[] = rtrim(UPLOAD_BASE_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($old, '/\\');
                }
                $candidates[] = __DIR__ . DIRECTORY_SEPARATOR . ltrim($old, '/\\');
                if (defined('LOGO_DIR')) {
                    $candidates[] = rtrim(LOGO_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($old);
                }

                foreach ($candidates as $physical) {
                    if ($physical && file_exists($physical)) {
                        @unlink($physical);
                    }
                }
            }

            $del_stmt = $pdo->prepare("UPDATE users SET logo_image=NULL WHERE id=:user_id");
            $del_stmt->execute([':user_id' => $userId]);

            $_SESSION['flash_message'] = 'プロフィール画像を削除しました';
            $_SESSION['flash_message_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'エラー: ' . $e->getMessage();
            $_SESSION['flash_message_type'] = 'error';
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // --- 画像アップロード（input name="logo_image"） ---
    if (isset($_FILES['logo_image']) && is_uploaded_file($_FILES['logo_image']['tmp_name'])) {
        try {
            $file = $_FILES['logo_image'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('ファイルアップロードエラー（コード: ' . $file['error'] . '）');
            }

            // 受け入れ拡張子チェック
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($ext, $allowed, true)) {
                throw new Exception('jpg, jpeg, png, gif のみアップロードできます');
            }

            // ディレクトリ準備（upload_config の関数を使用）
            ensureUploadDirectory(LOGO_DIR);

            // 保存ファイル名／パス
            $fileName = 'logo_' . $userId . '_' . time() . '.' . $ext;
            $savePath = LOGO_DIR . $fileName;                  // 物理パス
            $dbPath   = 'uploads/logos/' . $fileName;          // DB 保存値（相対パス）

            // 古い画像の物理削除（複数候補を試す）
            $stmt = $pdo->prepare("SELECT logo_image FROM users WHERE id=:uid");
            $stmt->execute([':uid' => $userId]);
            $old = $stmt->fetchColumn();
            if ($old) {
                $candidates = [];
                if (defined('UPLOAD_BASE_DIR')) {
                    $candidates[] = rtrim(UPLOAD_BASE_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($old, '/\\');
                }
                $candidates[] = __DIR__ . DIRECTORY_SEPARATOR . ltrim($old, '/\\');
                if (defined('LOGO_DIR')) {
                    $candidates[] = rtrim(LOGO_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($old);
                }
                foreach ($candidates as $physical) {
                    if ($physical && file_exists($physical)) {
                        @unlink($physical);
                    }
                }
            }

            // 実際の保存
            if (!move_uploaded_file($file['tmp_name'], $savePath)) {
                throw new Exception('画像保存に失敗しました');
            }

            // パーミッションセット（念のため、必要な環境向け）
            @chmod($savePath, 0644);

            // DB 更新
            $up = $pdo->prepare("UPDATE users SET logo_image=:img WHERE id=:uid");
            $up->execute([':img' => $dbPath, ':uid' => $userId]);

            $_SESSION['flash_message'] = 'プロフィール画像が更新されました';
            $_SESSION['flash_message_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'エラー: ' . $e->getMessage();
            $_SESSION['flash_message_type'] = 'error';
        }

        // PRG
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ------------------ 表示用データ取得 ------------------
$stmt = $pdo->prepare("SELECT username, logo_image FROM users WHERE id=:user_id");
$stmt->execute([':user_id' => $userId]);
$profile_data = $stmt->fetch();
$userName = $profile_data['username'] ?? '';
$logoImage = $profile_data['logo_image'] ?? '';

// デフォルトアイコン（data URI）
$defaultIcon = 'data:image/svg+xml;utf8,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="#bdbdbd"/><circle cx="50" cy="37" r="15" fill="#ffffff"/><path d="M 30 65 Q 30 55 50 55 Q 70 55 70 65 L 70 85 Q 70 90 50 90 Q 30 90 30 85 Z" fill="#ffffff"/></svg>'
);
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
            <?php
                // キャッシュバスター：可能ならファイルmtimeを使う（速やかに更新を反映）
                $cacheToken = time();
                // 物理パス候補
                $physicalCandidates = [];
                if (defined('UPLOAD_BASE_DIR')) {
                    $physicalCandidates[] = rtrim(UPLOAD_BASE_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($logoImage, '/\\');
                }
                $physicalCandidates[] = __DIR__ . '/' . ltrim($logoImage, '/\\');
                if (defined('LOGO_DIR')) {
                    $physicalCandidates[] = rtrim(LOGO_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($logoImage);
                }
                foreach ($physicalCandidates as $pc) {
                    if ($pc && file_exists($pc)) {
                        $fm = @filemtime($pc);
                        if ($fm !== false) {
                            $cacheToken = $fm;
                            break;
                        }
                    }
                }
            ?>
            <img src="<?php echo htmlspecialchars(getImageUrl($logoImage) . '?t=' . $cacheToken); ?>" id="logoPreview">
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

    <!-- カレンダー管理 -->
    <div class="calendar-management">
        <a href="teacher_calendar.php" class="calendar-link">カレンダーを管理する →</a>
    </div>

    <!-- 換算値設定 -->
    <div class="detail-row">
        <span class="detail-label">換算値</span>
        <div class="detail-value">
            <button type="button" class="kansanti-btn" onclick="goToKansanti()">
                生徒の換算値を設定する
            </button>
        </div>
    </div>

    <!-- ログアウト -->
    <div class="logout-section">
        <input type="button" class="logout-btn" onclick="location.href='logout.php'" value="ログアウト">
    </div>

    <!-- ボトムナビ -->
    <div class="bottom-nav">
        <a href="syusseki.php" class="nav-item">
            <div class="nav-icon person"></div>
            <span class="nav-text">出席</span>
        </a>
        <a href="teachatp.php" class="nav-item">
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
            const preview = document.getElementById('logoPreview');
            if (preview) preview.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);

        // 少し遅延して送信（ローカルプレビューが見える時間を確保）
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

function goToKansanti() {
    window.location.href = 'kansanti.php';
}
</script>
</body>
</html>
