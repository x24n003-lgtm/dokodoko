<?php
require_once 'db_config.php';
session_start();

// ------------------ ログイン確認 ------------------
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$userEmail = $_SESSION['email'];
date_default_timezone_set('Asia/Tokyo');

// ------------------ DB 接続 ------------------
$pdo = getDbConnection();

// ------------------ プロフィール画像取得 (修正) ------------------
$stmt = $pdo->prepare("SELECT logo_image FROM users WHERE email = ?");
$stmt->execute([$userEmail]);
$user = $stmt->fetch();

// logo_imageカラムの値を確認し、適切なパスを設定
$profileImage = '';
if (!empty($user['logo_image']) && file_exists($user['logo_image'])) {
    // DBに保存されているパス(uploads/logos/xxx.jpg など)が存在する場合
    $profileImage = $user['logo_image'];
} elseif (!empty($user['logo_image'])) {
    // パスは保存されているが、ファイルが存在しない場合
    $profileImage = "default_icon.png";
} else {
    // 画像が未設定の場合
    $profileImage = "default_icon.png";
}

// ------------------ 年月取得 ------------------
$year  = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");
$month = isset($_GET['month']) ? (int)$_GET['month'] : date("n");

$prev = strtotime("-1 month", strtotime("$year-$month-01"));
$next = strtotime("+1 month", strtotime("$year-$month-01"));
$prevYear = date("Y", $prev);
$prevMonth = date("n", $prev);
$nextYear = date("Y", $next);
$nextMonth = date("n", $next);

// ------------------ DBからイベント取得 ------------------
$events = [];

try {
    $firstDay = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $lastDay  = date("Y-m-t", strtotime($firstDay));

    $stmt = $pdo->prepare("SELECT event_date, event_title FROM calendar_events 
                           WHERE event_date BETWEEN ? AND ? ORDER BY event_date");
    $stmt->execute([$firstDay, $lastDay]);
    while ($row = $stmt->fetch()) {
        $events[$row['event_date']] = $row['event_title'];
    }
} catch (PDOException $e) {
    error_log("イベント取得エラー: " . $e->getMessage());
}

$weekdays = ["日","月","火","水","木","金","土"];
$firstDay = new DateTime("$year-$month-01");
$lastDay  = new DateTime($firstDay->format("Y-m-t"));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>カレンダーアプリ</title>
<link rel="stylesheet" href="homecss.css">
<style>
/* プロフィール画像のスタイル追加 */
.profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #ddd;
}

.profile-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
</style>
</head>
<body>

<div class="phone-container">
    <div class="status-bar">
        <span>9:41</span>
        <span>📶 📡 🔋</span>
    </div>

    <div class="header">
        <div class="profile-pic">
            <img src="<?= htmlspecialchars($profileImage) ?>?t=<?= time() ?>" class="profile-img" alt="プロフィール">
        </div>
        <div class="header-title">ホーム</div>
    </div>

    <div class="content">
        <div class="conversion-value">
            <div class="conversion-label">換算値</div>
            <div class="conversion-number">5.66</div>
        </div>

        <div class="calendar-nav">
            <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="nav-btn">←</a>
            <span class="current-month"><?= $year ?>年<?= $month ?>月</span>
            <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="nav-btn">→</a>
        </div>

        <table class="calendar-table">
            <thead>
                <tr><th>日付</th><th>予定</th></tr>
            </thead>
            <tbody>
                <?php
                for ($d = clone $firstDay; $d <= $lastDay; $d->modify('+1 day')) {
                    $w = $d->format("w");
                    $dateStr = $d->format("Y-m-d");
                    $displayDate = $d->format("n/j");
                    $youbi = $weekdays[$w];

                    $colorClass = "";
                    if ($w == 0) $colorClass = "sunday";
                    if ($w == 6) $colorClass = "saturday";

                    $eventText = $events[$dateStr] ?? "";

                    echo "<tr>";
                    echo "<td class='$colorClass'>{$displayDate} ({$youbi})</td>";
                    echo "<td class='event-text'>{$eventText}</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="bottom-nav">
        <a href="karennda-.php" class="nav-item active">
            <div class="nav-icon person"></div>
            <span class="nav-text">カレンダー</span>
        </a>
        <a href="chatp.php" class="nav-item">
            <div class="nav-icon message"></div>
            <span class="nav-text">チャット</span>
        </a>
        <a href="mypage.php" class="nav-item">
            <div class="nav-icon settings"></div>
            <span class="nav-text">マイページ</span>
        </a>
    </div>
</div>

<!-- 🔻 位置情報送信機能 🔻 -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    if (!navigator.geolocation) return;

    fetchLocation();

    function fetchLocation() {
        navigator.geolocation.getCurrentPosition(pos => {
            fetch("save_locations.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude
                })
            })
            .then(r => r.json())
            .then(res => console.log(res))
            .catch(e => console.error(e));
        });
    }

    setInterval(fetchLocation, 5000);
});
</script>

</body>
</html>