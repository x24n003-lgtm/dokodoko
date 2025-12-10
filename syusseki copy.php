<?php
session_start();

// ----------------------------------------
// 教員ログインチェック
// ----------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    header('Location: login.php');
    exit();
}

// ----------------------------------------
// DB 接続
// ----------------------------------------
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";
$port = 3306;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("DB接続エラー: " . $e->getMessage());
}

// ----------------------------------------
// 教員プロフィール画像取得
// ----------------------------------------
$logo_stmt = $pdo->prepare("
    SELECT username, logo_image 
    FROM users 
    WHERE id = :teacher_id AND user_type='teacher'
");
$logo_stmt->execute([":teacher_id" => $_SESSION['user_id']]);
$teacher = $logo_stmt->fetch();

$teacher_name  = $teacher['username'] ?? '今どこ';
$teacher_image = $teacher['logo_image'] ?? null;

// デフォルトアイコン（SVG）
$default_icon = 'data:image/svg+xml;utf8,' .
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">' .
    '<circle cx="50" cy="50" r="50" fill="%23e0e0e0"/>' .
    '<circle cx="50" cy="35" r="20" fill="%23999"/>' .
    '<path d="M20 80 Q20 60 50 60 Q80 60 80 80 Z" fill="%23999"/>' .
    '</svg>';

// 画像存在チェック
function safeImage($path, $fallback) {
    if ($path && file_exists($path)) {
        return $path;
    }
    return $fallback;
}

$display_image = safeImage($teacher_image, $default_icon);

// ----------------------------------------
// クラス取得
// ----------------------------------------
$selected_class = $_GET["class"] ?? "all";

$classes = $pdo->query("
    SELECT DISTINCT class_name 
    FROM users 
    WHERE user_type='student' AND class_name IS NOT NULL
    ORDER BY class_name
")->fetchAll(PDO::FETCH_COLUMN);

// ----------------------------------------
// 学生データ取得
// ----------------------------------------
if ($selected_class === "all") {
    $stmt = $pdo->prepare("
        SELECT id, username AS name, class_name, logo_image, 
               lat, lng, home_lat, home_lng
        FROM users
        WHERE user_type='student'
        ORDER BY class_name, id
    ");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("
        SELECT id, username AS name, class_name, logo_image,
               lat, lng, home_lat, home_lng
        FROM users
        WHERE user_type='student' AND class_name=:class_name
        ORDER BY id
    ");
    $stmt->execute([":class_name" => $selected_class]);
}

$students = $stmt->fetchAll();

// ----------------------------------------
// 学校の位置
// ----------------------------------------
$school_lat = 35.704517;
$school_lng = 139.984413;
$school_radius = 500;

// ----------------------------------------
// 距離計算
// ----------------------------------------
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earth = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat/2) ** 2 +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) ** 2;

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth * $c;
}

// ----------------------------------------
// 表示用テキスト
// ----------------------------------------
function getLocationText($loc, $dist = null) {
    return match($loc) {
        'school' => '学校',
        'home'   => '自宅',
        'other'  => $dist ? "移動中 (" . round($dist) . "m)" : "移動中",
        default  => '位置不明',
    };
}

function getLocationColor($loc) {
    return match($loc) {
        'school' => 'green',
        'home'   => 'red',
        'other'  => 'yellow',
        default  => 'gray',
    };
}

// ----------------------------------------
// 学生の位置判定
// ----------------------------------------
$epsilon = 0.00001;

foreach ($students as &$stu) {
    $lat  = $stu['lat'];
    $lng  = $stu['lng'];
    $hlat = $stu['home_lat'];
    $hlng = $stu['home_lng'];

    if ($lat !== null && $lng !== null) {

        $dist_school = calculateDistance($school_lat, $school_lng, $lat, $lng);

        if ($dist_school <= $school_radius) {
            $stu['real_location'] = 'school';
        } elseif (
            $hlat !== null && $hlng !== null &&
            abs($lat - $hlat) < $epsilon &&
            abs($lng - $hlng) < $epsilon
        ) {
            $stu['real_location'] = 'home';
        } else {
            $stu['real_location'] = 'other';
        }

        $stu['distance'] = $dist_school;
    } else {
        $stu['real_location'] = 'unknown';
        $stu['distance'] = null;
    }

    // 学生画像にも file_exists を適用
    $stu['logo_image'] = safeImage($stu['logo_image'], null);
}

unset($stu);
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
    <!-- 生徒リスト -->
<div class="content">
    <div class="student-list" id="studentList">
        <?php foreach ($students as $student): ?>
            <?php
                // 学生のプロフィール画像を設定
                $student_image = $student['logo_image'] ?? '';
                if (empty($student_image) || !file_exists($student_image)) {
                    $student_image = null;
                }
            ?>
            <a href="student_detail.php?id=<?php echo $student['id']; ?>" 
               class="student-item"
               data-id="<?php echo $student['id']; ?>"
               data-name="<?php echo htmlspecialchars($student['name']); ?>">
                
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
                    <div class="status-text">
                        <?php echo getLocationText($student['real_location']); ?>
                    </div>
                    <?php if ($student['distance']): ?>
                        <div class="status-time"><?php echo round($student['distance']); ?>m</div>
                    <?php endif; ?>
                </div>

                <div class="location-indicator <?php echo getLocationColor($student['real_location']); ?>"></div>
            </a>
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

<!-- Google Maps API を読み込む -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"></script>

<script>
// =====================================================
//      Google API のルート計算サービス
// =====================================================
let directionsService = new google.maps.DirectionsService();

// PHP → JS に渡された学生データ
const studentPositions = <?= json_encode($students) ?>;

// 学校の位置
const SCHOOL_LAT = <?= $school_lat ?>;
const SCHOOL_LNG = <?= $school_lng ?>;

// =====================================================
//      時間フォーマット
// =====================================================
function formatDuration(sec) {
    const min = Math.round(sec / 60);
    return `${min}分`;
}

// =====================================================
//      到着時間計算（学校判定を最優先）
// =====================================================
function calcTravelTimes() {

    studentPositions.forEach(stu => {
        if (!stu.lat || !stu.lng) return;

        const item = document.querySelector(`.student-item[data-id="${stu.id}"]`);
        if (!item) return;

        // ★ arrival-time の場所を先に確保
        let box = item.querySelector(".arrival-time");
        if (!box) {
            box = document.createElement("div");
            box.className = "arrival-time";
            item.querySelector(".student-info").appendChild(box);
        }

        // ---------------------------------------------------
        // ★ PHP が school 判定 → 最優先で到着扱い
        // ---------------------------------------------------
        if (stu.real_location === "school") {
            box.innerHTML = "到着完了"; // 学校判定で即到着
            return;
        }

        // ---------------------------------------------------
        // ★ Google API（歩き）で到着時間計算（学校以外の生徒）
        // ---------------------------------------------------
        directionsService.route(
            {
                origin: { lat: parseFloat(stu.lat), lng: parseFloat(stu.lng) },
                destination: { lat: SCHOOL_LAT, lng: SCHOOL_LNG },
                travelMode: google.maps.TravelMode.WALKING,
            },
            (result, status) => {

                if (status === 'OK') {
                    const durationSec = result.routes[0].legs[0].duration.value;
                    const min = Math.round(durationSec / 60);

                    // ★ Google API で 1 分以下なら到着完了
                    if (min <= 1) {
                        box.innerHTML = "到着完了";
                    } else {
                        box.innerHTML = `学校まで ${min}分`;
                    }

                } else {
                    // 遠すぎてルートが作れない
                    if (stu.distance && stu.distance > 100000) {
                        box.innerHTML = "遠すぎて計算不能";
                    } else {
                        box.innerHTML = "計算失敗";
                    }
                }
            }
        );
    });
}

calcTravelTimes();
</script>
<script>
// ================================
//   出席ページ：検索機能
// ================================
document.getElementById("searchInput").addEventListener("input", function () {
    const keyword = this.value.toLowerCase();
    const students = document.querySelectorAll(".student-item");

    students.forEach(stu => {
        const name = stu.getAttribute("data-name").toLowerCase();

        // 含まれていれば表示、なければ非表示
        if (name.includes(keyword)) {
            stu.style.display = "flex";
        } else {
            stu.style.display = "none";
        }
    });
});
</script>


</body>
</html>