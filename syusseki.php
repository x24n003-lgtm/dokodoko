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

// ------------------ 教員のプロフィール画像取得 ------------------
$default_icon = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="%23e0e0e0"/><circle cx="50" cy="35" r="20" fill="%23999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80 Z" fill="%23999"/></svg>';

$logo_sql = "SELECT logo_image FROM users WHERE id = :teacher_id AND user_type = 'teacher'";
$logo_stmt = $pdo->prepare($logo_sql);
$logo_stmt->bindParam(':teacher_id', $_SESSION['user_id'], PDO::PARAM_INT);
$logo_stmt->execute();
$teacher_data = $logo_stmt->fetch();
$logo_image = $teacher_data['logo_image'] ?? null;

$display_image = ($logo_image && file_exists($logo_image)) ? $logo_image : $default_icon;

// ------------------ クラス選択の処理 ------------------
$selected_class = isset($_GET['class']) ? $_GET['class'] : 'all';

$class_sql = "SELECT DISTINCT class_name FROM users WHERE user_type = 'student' AND class_name IS NOT NULL ORDER BY class_name";
$class_stmt = $pdo->query($class_sql);
$classes = $class_stmt->fetchAll(PDO::FETCH_COLUMN);

// ------------------ 学生データ取得 ------------------
if ($selected_class === 'all') {
    $sql = "SELECT 
                u.id,
                u.username as name,
                u.class_name,
                u.logo_image,
                u.lat,
                u.lng,
                u.location_updated_at
            FROM users u
            WHERE u.user_type = 'student'
            ORDER BY u.class_name, u.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
} else {
    $sql = "SELECT 
                u.id,
                u.username as name,
                u.class_name,
                u.logo_image,
                u.lat,
                u.lng,
                u.location_updated_at
            FROM users u
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
$school_radius = 100;

// 距離計算関数
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c;
}

// 位置情報による自動判定
foreach ($students as &$student) {
    if ($student['lat'] && $student['lng']) {
        $distance = calculateDistance($school_lat, $school_lng, $student['lat'], $student['lng']);
        $student['distance'] = $distance;
        if ($distance <= $school_radius) {
            $student['real_location'] = 'school';
        } elseif ($distance <= 3000) {
            $student['real_location'] = 'other';
        } else {
            $student['real_location'] = 'home';
        }
    } else {
        $student['real_location'] = 'unknown';
        $student['distance'] = null;
    }
}
unset($student);

function getLocationColor($real_location) {
    switch($real_location) {
        case 'school': return 'green';
        case 'home': return 'red';
        case 'other': return 'yellow';
        default: return 'gray';
    }
}

function getLocationText($real_location, $distance = null) {
    switch($real_location) {
        case 'school': return '学校';
        case 'home': return '自宅';
        case 'other': return $distance ? '移動中 (' . round($distance) . 'm)' : '移動中';
        default: return '位置不明';
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
.header-profile-icon { width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid #ddd; }
.student-avatar { width:50px;height:50px;border-radius:50%;overflow:hidden;flex-shrink:0;background:#e0e0e0;display:flex;align-items:center;justify-content:center; }
.student-avatar img { width:100%;height:100%;object-fit:cover; }
.avatar-circle { width:100%;height:100%;background:#9b9b9b;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:24px; }
</style>
</head>
<body>

<div class="phone-container">
    <div class="status-bar">
        <span><?php echo date('G:i'); ?></span>
        <div class="status-icons"><span>📶</span><span>📡</span><span>🔋</span></div>
    </div>

    <div class="header">
        <div class="class-info">
            <img src="<?php echo htmlspecialchars($display_image); ?>?t=<?php echo time(); ?>" alt="プロフィール" class="header-profile-icon">
            <span class="class-name">今どこ</span>
        </div>
        <div class="class-selector">
            <form method="GET" action="" style="margin:0;">
                <select class="class-dropdown" name="class" onchange="this.form.submit()">
                    <option value="all" <?php echo $selected_class==='all'?'selected':''; ?>>全クラス ▼</option>
                    <?php foreach($classes as $class): ?>
                    <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $selected_class===$class?'selected':''; ?>>
                        <?php echo htmlspecialchars($class); ?> ▼
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <div class="search-bar">
        <input type="text" placeholder="Search" class="search-input" id="searchInput">
        <button class="refresh-btn" onclick="location.reload()">🔄</button>
    </div>

    <div class="content">
        <div class="student-list" id="studentList">
            <?php foreach($students as $student): 
                $student_image = $student['logo_image'] ?? '';
                if(empty($student_image) || !file_exists($student_image)) $student_image = null;
            ?>
            <div class="student-item" data-id="<?php echo $student['id']; ?>" data-name="<?php echo htmlspecialchars($student['name']); ?>">
                <div class="student-avatar">
                    <?php if($student_image): ?>
                    <img src="<?php echo htmlspecialchars($student_image); ?>?t=<?php echo time(); ?>" alt="<?php echo htmlspecialchars($student['name']); ?>" onerror="this.style.display='none';this.parentElement.innerHTML='<div class=\'avatar-circle\'>👤</div>';">
                    <?php else: ?>
                    <div class="avatar-circle">👤</div>
                    <?php endif; ?>
                </div>
                <div class="student-info">
                    <div class="student-name">
                        <?php echo htmlspecialchars($student['name']); ?>
                        <?php if($selected_class==='all' && $student['class_name']): ?>
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
                    <?php if($student['distance']): ?>
                    <div class="status-time"><?php echo round($student['distance']); ?>m</div>
                    <?php endif; ?>
                </div>
                <div class="location-indicator <?php echo getLocationColor($student['real_location']); ?>"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="syusseki.php" class="nav-item active"><div class="nav-icon person"></div><span class="nav-text">出席</span></a>
        <a href="teachatp.php" class="nav-item"><div class="nav-icon message"></div><span class="nav-text">チャット</span></a>
        <a href="teachermypage.php" class="nav-item"><div class="nav-icon settings"></div><span class="nav-text">マイページ</span></a>
    </div>
</div>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"></script>
<script>
let directionsService = new google.maps.DirectionsService();
const studentPositions = <?= json_encode($students) ?>;
const SCHOOL_LAT = <?= $school_lat ?>;
const SCHOOL_LNG = <?= $school_lng ?>;

function formatDuration(sec){
    const min = Math.round(sec/60);
    return `${min}分`;
}

function calcTravelTimes(){
    studentPositions.forEach(stu=>{
        if(!stu.lat||!stu.lng) return;
        const item=document.querySelector(`.student-item[data-id="${stu.id}"]`);
        if(!item) return;

        let box=item.querySelector(".arrival-time");
        if(!box){
            box=document.createElement("div");
            box.className="arrival-time";
            item.querySelector(".student-info").appendChild(box);
        }

        // PHP判定で学校なら即到着
        if(stu.real_location==="school"){
            box.innerHTML="到着完了";
            item.querySelector(".status-text").className="status-text location-green";
            return;
        }

        // Google APIで距離計算
        directionsService.route({
            origin:{lat:parseFloat(stu.lat),lng:parseFloat(stu.lng)},
            destination:{lat:SCHOOL_LAT,lng:SCHOOL_LNG},
            travelMode: google.maps.TravelMode.WALKING,
        },(result,status)=>{
            if(status==='OK'){
                const durationSec=result.routes[0].legs[0].duration.value;
                const min=Math.round(durationSec/60);
                if(min<=1){
                    box.innerHTML="到着完了";
                    item.querySelector(".status-text").className="status-text location-green";
                } else {
                    box.innerHTML=`学校まで ${min}分`;
                }
            } else {
                if(stu.distance && stu.distance>100000){
                    box.innerHTML="遠すぎて計算不能";
                } else {
                    box.innerHTML="計算失敗";
                }
            }
        });
    });
}
calcTravelTimes();
</script>

</body>
</html>
