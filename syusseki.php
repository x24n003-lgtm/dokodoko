<?php
session_start();

// 教員のログインチェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

// ------------------ 基本設定 ------------------
date_default_timezone_set('Asia/Tokyo');

// ------------------ データベース接続 ------------------
$host = "172.16.199.21";  // Linux MariaDB の IP
$user = "x24n007";        // MariaDB のユーザー名
$pass = "n051211";        // MariaDB のパスワード
$db   = "dokodoko";       // データベース名
$port = 3306;             // MariaDB のポート番号（通常は 3306）

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

// ------------------ ロゴ画像の取得 ------------------
$logo_sql = "SELECT logo_image FROM users WHERE id = :teacher_id AND user_type = 'teacher'";
$logo_stmt = $pdo->prepare($logo_sql);
$logo_stmt->bindParam(':teacher_id', $_SESSION['user_id'], PDO::PARAM_INT);
$logo_stmt->execute();
$logo_data = $logo_stmt->fetch();
$logo_image = $logo_data['logo_image'] ?? null;

// ------------------ クラス選択の処理 ------------------
$selected_class = isset($_GET['class']) ? $_GET['class'] : 'all';

// クラス一覧を取得（NULLでないclass_nameのみ）
$class_sql = "SELECT DISTINCT class_name FROM users WHERE user_type = 'student' AND class_name IS NOT NULL ORDER BY class_name";
$class_stmt = $pdo->query($class_sql);
$classes = $class_stmt->fetchAll(PDO::FETCH_COLUMN);

// ------------------ 学生データの取得（位置情報を含む） ------------------
// クラスフィルターを含むSQL
if ($selected_class === 'all') {
    // 全クラス表示
    $sql = "SELECT 
                u.id,
                u.username as name,
                u.class_name,
                u.lat,
                u.lng,
                u.location_updated_at,
                a.status,
                a.status_detail,
                a.location,
                a.attendance_time
            FROM users u
            LEFT JOIN (
                SELECT user_id, status, status_detail, location, attendance_time
                FROM attendance 
                WHERE DATE(attendance_date) = CURDATE()
                GROUP BY user_id
            ) a ON u.id = a.user_id
            WHERE u.user_type = 'student'
            ORDER BY u.class_name, u.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
} else {
    // 特定クラスを表示
    $sql = "SELECT 
                u.id,
                u.username as name,
                u.class_name,
                u.lat,
                u.lng,
                u.location_updated_at,
                a.status,
                a.status_detail,
                a.location,
                a.attendance_time
            FROM users u
            LEFT JOIN (
                SELECT user_id, status, status_detail, location, attendance_time
                FROM attendance 
                WHERE DATE(attendance_date) = CURDATE()
                GROUP BY user_id
            ) a ON u.id = a.user_id
            WHERE u.user_type = 'student' AND u.class_name = :class_name
            ORDER BY u.id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':class_name', $selected_class, PDO::PARAM_STR);
    $stmt->execute();
}

$students = $stmt->fetchAll();

// 学校の位置（船橋情報ビジネス専門学校）
$school_lat = 35.704517;
$school_lng = 139.984413;
$school_radius = 500; // 校内判定の半径（メートル）

// 距離計算関数
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371000; // 地球の半径[m]
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat / 2) ** 2 +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) ** 2;
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earth_radius * $c;
    
    return $distance;
}

// 出席状況がない学生のデフォルト値を設定 & 位置情報による判定
$processed_students = [];
foreach ($students as $student) {
    // 位置情報による自動判定
    if ($student['lat'] && $student['lng']) {
        $distance = calculateDistance(
            $school_lat, 
            $school_lng, 
            $student['lat'], 
            $student['lng']
        );
        
        // 距離に基づいて位置を判定
        if ($distance <= $school_radius) {
            $student['real_location'] = 'school'; // 校内
            $student['distance'] = $distance;
        } else if ($distance <= 3000) { // 3km以内
            $student['real_location'] = 'other'; // 移動中
            $student['distance'] = $distance;
        } else {
            $student['real_location'] = 'home'; // 自宅
            $student['distance'] = $distance;
        }
    } else {
        $student['real_location'] = 'unknown'; // 位置不明
        $student['distance'] = null;
    }
    
    $processed_students[] = $student;
}
$students = $processed_students;

// ------------------ 位置情報の色を返す関数 ------------------
function getLocationColor($real_location) {
    switch($real_location) {
        case 'school':
            return 'green';   // 校内
        case 'home':
            return 'red';     // 自宅
        case 'other':
            return 'yellow';  // 移動中
        default:
            return 'gray';    // 位置不明
    }
}

// ------------------ 位置情報のテキスト変換関数 ------------------
function getLocationText($real_location, $distance = null) {
    switch($real_location) {
        case 'school':
            return '学校';
        case 'home':
            return '自宅';
        case 'other':
            if ($distance) {
                return '移動中 (' . round($distance) . 'm)';
            }
            return '移動中';
        default:
            return '位置不明';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>出席管理</title>
    <link rel="stylesheet" href="syusseki.css">
    <style>
        /* ロゴ画像用のスタイル */
        .header-logo {
            height: 30px;
            width: auto;
            max-width: 40px;
            margin-right: 5px;
            vertical-align: middle;
            object-fit: contain;
        }
        
        .class-info {
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
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
            <?php if ($logo_image && file_exists($logo_image)): ?>
                <img src="<?php echo htmlspecialchars($logo_image); ?>" alt="ロゴ" class="header-logo">
            <?php else: ?>
                <span class="class-name">橋</span>
            <?php endif; ?>
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
            <?php foreach ($students as $student): ?>
                <div class="student-item" data-name="<?php echo htmlspecialchars($student['name']); ?>">
                    <div class="student-avatar">
                        <div class="avatar-circle"></div>
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
        <a href="teacherchat.php" class="nav-item">
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