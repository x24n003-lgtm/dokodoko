<?php
session_start();

// 教員のログインチェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

// データベース接続
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("DB接続エラー: " . $e->getMessage());
}

// 教員のプロフィール画像取得
$logo_stmt = $pdo->prepare("SELECT username, logo_image FROM users WHERE id=:teacher_id AND user_type='teacher'");
$logo_stmt->execute([':teacher_id' => $_SESSION['user_id']]);
$teacher_data = $logo_stmt->fetch();
$teacher_name = $teacher_data['username'] ?? '今どこ';
$logo_image = $teacher_data['logo_image'] ?? null;

// デフォルトアイコン
$default_icon = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="%23e0e0e0"/><circle cx="50" cy="35" r="20" fill="%23999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80 Z" fill="%23999"/></svg>';

// 教員のプロフィール画像を決定
if ($logo_image && file_exists($logo_image)) {
    $display_image = $logo_image;
} else {
    $display_image = $default_icon;
}

// クラス選択
$selected_class = $_GET['class'] ?? 'all';
$classes = $pdo->query("SELECT DISTINCT class_name FROM users WHERE user_type='student' AND class_name IS NOT NULL ORDER BY class_name")->fetchAll(PDO::FETCH_COLUMN);

// 学生データ取得（logo_imageも取得）
if ($selected_class === 'all') {
    $stmt = $pdo->prepare("
        SELECT id, username as name, class_name, logo_image, lat, lng, home_lat, home_lng
        FROM users
        WHERE user_type='student'
        ORDER BY class_name, id
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT id, username as name, class_name, logo_image, lat, lng, home_lat, home_lng
        FROM users
        WHERE user_type='student' AND class_name=:class_name
        ORDER BY id
    ");
    $stmt->execute([':class_name' => $selected_class]);
}
$students = $stmt->fetchAll();

// 学校の位置
$school_lat = 35.704517;
$school_lng = 139.984413;
$school_radius = 500; // 校内判定[m]

// 距離計算関数
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

// 位置情報のテキスト変換関数
function getLocationText($real_location, $distance = null) {
    switch($real_location) {
        case 'school':
            return '学校';
        case 'home':
            return '自宅';
        case 'other':
            if ($distance !== null) {
                return '移動中 (' . round($distance) . 'm)';
            }
            return '移動中';
        default:
            return '位置不明';
    }
}

// 色判定関数
function getLocationColor($loc) {
    return match($loc) {
        'school' => 'green',
        'home' => 'red',
        'other' => 'yellow',
        default => 'gray',
    };
}

// 学生データの距離判定
$epsilon = 0.00001;

foreach ($students as &$s) {
    $lat = $s['lat'] ?? null;
    $lng = $s['lng'] ?? null;
    $home_lat = $s['home_lat'] ?? null;
    $home_lng = $s['home_lng'] ?? null;

    if ($lat !== null && $lng !== null) {
        $distance_to_school = calculateDistance($school_lat, $school_lng, $lat, $lng);

        if ($distance_to_school <= $school_radius) {
            $s['real_location'] = 'school';
        } elseif ($home_lat !== null && $home_lng !== null &&
                  abs($lat - $home_lat) < $epsilon && abs($lng - $home_lng) < $epsilon) {
            $s['real_location'] = 'home';
        } else {
            $s['real_location'] = 'other';
        }

        $s['distance'] = $distance_to_school;
    } else {
        $s['real_location'] = 'unknown';
        $s['distance'] = null;
    }

    $s['display_status'] = getLocationText($s['real_location'], $s['distance']);
}
unset($s);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>出席管理</title>
    <link rel="stylesheet" href="syusseki.css">
</head>
<body>

<div class="phone-container">
    <!-- ステータスバー -->
    <div class="status-bar">
        <span><?php echo date('G:i'); ?></span>
        <div class="status-icons">
            <span>📶</span>
            <span>📡</span>
            <span>🔋</span>
        </div>
    </div>

    <!-- ヘッダー -->
    <div class="header">
        <div class="class-info">
            <img src="<?php echo htmlspecialchars($display_image); ?>?t=<?php echo time(); ?>" 
                 alt="プロフィール" 
                 class="header-profile-icon">
            <span class="class-name">今どこ</span>
        </div>
        <div class="class-selector">
            <form method="GET" action="" style="margin: 0;">
                <select class="class-dropdown" name="class" onchange="this.form.submit()">
                    <option value="all" <?php echo $selected_class === 'all' ? 'selected' : ''; ?>>
                        全クラス ▼
                    </option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo htmlspecialchars($class); ?>" 
                                <?php echo $selected_class === $class ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class); ?> ▼
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- 検索バー -->
    <div class="search-bar">
        <input type="text" placeholder="Search" class="search-input" id="searchInput">
        <button class="refresh-btn" onclick="location.reload()">🔄</button>
    </div>

    <!-- 生徒リスト -->
    <div class="content">
        <div class="student-list" id="studentList">
            <?php foreach ($students as $student):
                // 学生のプロフィール画像を設定
                $student_image = $student['logo_image'] ?? '';
                if (empty($student_image) || !file_exists($student_image)) {
                    $student_image = null;
                }
            ?>
                <div class="student-item" data-name="<?php echo htmlspecialchars($student['name']); ?>">
                    <div class="student-avatar">
                        <?php if ($student_image): ?>
                            <img src="<?php echo htmlspecialchars($student_image); ?>?t=<?php echo time(); ?>" 
                                 alt="<?php echo htmlspecialchars($student['name']); ?>"
                                 onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'avatar-circle\'>👤</div>';">
                        <?php else: ?>
                            <div class="avatar-circle">👤</div>
                        <?php endif; ?>
                    </div>

                    <div class="student-info">
                        <div class="student-name">
                            <?php echo htmlspecialchars($student['name']); ?>
                            <?php if ($selected_class === 'all' && $student['class_name']): ?>
                                <span class="class-badge"><?php echo htmlspecialchars($student['class_name']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="student-detail location-<?php echo getLocationColor($student['real_location']); ?>">
                            <?php echo getLocationText($student['real_location'], $student['distance']); ?>
                        </div>
                    </div>

                    <div class="status-display">
                        <div class="status-text location-<?php echo getLocationColor($student['real_location']); ?>">
                            <?php echo getLocationText($student['real_location']); ?>
                        </div>
                        <?php if ($student['distance']): ?>
                            <div class="status-time"><?php echo round($student['distance']); ?>m</div>
                        <?php endif; ?>
                    </div>

                    <div class="location-indicator <?php echo getLocationColor($student['real_location']); ?>">
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($students)): ?>
                <div style="text-align: center; padding: 50px; color: #999;">
                    <?php if ($selected_class === 'all'): ?>
                        <p>学生が登録されていません</p>
                    <?php else: ?>
                        <p><?php echo htmlspecialchars($selected_class); ?>クラスに学生が登録されていません</p>
                        <p style="font-size: 14px; margin-top: 10px;">他のクラスを選択してください</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ボトムナビゲーション -->
    <div class="bottom-nav">
        <button class="nav-item active">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <span class="nav-text">出席</span>
        </button>
        <a href="teachatp.php" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <span class="nav-text">チャット</span>
        </a>
        <button class="nav-item" onclick="location.href='teachermypage.php'">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </div>
            <span class="nav-text">マイページ</span>
        </button>
    </div>
</div>

<script>
// リアルタイム検索機能
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchText = e.target.value.toLowerCase();
    const studentItems = document.querySelectorAll('.student-item');
    
    studentItems.forEach(item => {
        const name = item.dataset.name.toLowerCase();
        if (name.includes(searchText)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
});
</script>

</body>
</html>