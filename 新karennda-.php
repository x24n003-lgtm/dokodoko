<?php
require_once 'db_config.php';
session_start();

// ------------------ ログイン & 権限確認 ------------------
if (!isset($_SESSION['email'], $_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}

// 生徒専用ページ（teacher が来たら先生用ページへ返す）
if ($_SESSION['user_type'] !== 'student') {
    header('Location: syusseki.php'); // 必要に応じて別ページへ
    exit();
}

$userEmail = $_SESSION['email'];

// ------------------ 基本設定 ------------------
date_default_timezone_set('Asia/Tokyo');

// ------------------ 年月取得 ------------------
$year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date("Y");
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date("n");

// 前月・次月計算
$prev = strtotime("-1 month", strtotime("$year-$month-01"));
$next = strtotime("+1 month", strtotime("$year-$month-01"));
$prevYear  = (int)date("Y", $prev);
$prevMonth = (int)date("n", $prev);
$nextYear  = (int)date("Y", $next);
$nextMonth = (int)date("n", $next);

// ------------------ DB接続 ------------------
$pdo = getDbConnection();

// ------------------ ログイン中ユーザーのID取得 ------------------
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$userEmail]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userRow) {
        $studentId = (int)$userRow['id'];
    } else {
        session_destroy();
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("ユーザー取得エラー: " . $e->getMessage());
    session_destroy();
    header('Location: login.php');
    exit();
}

// ------------------ 生徒の換算値合計取得 ------------------
$totalValueRaw = 0.00;

try {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(value), 0) AS total_value
        FROM kansanti
        WHERE student_id = :student_id
    ");
    $stmt->execute([':student_id' => $studentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $totalValueRaw = (float)$row['total_value'];
    }
} catch (PDOException $e) {
    error_log("換算値合計取得エラー: " . $e->getMessage());
}

// 小数第三位を切り捨てて表示用2桁に合わせる
$totalValue = floor($totalValueRaw * 100) / 100;

// ------------------ 警告メッセージ判定 ------------------
$warningMessage = '';
$warningLevel   = '';

// 55 以上は常に退学メッセージ
if ($totalValue >= 55) {
    $warningMessage = '【退学】あなたは退学です。詳しくは学校からの正式な通知・説明を確認してください。';
    $warningLevel   = 'level-5';

// 54.00〜<55.00
} elseif ($totalValue >= 54 && $totalValue < 55) {
    $warningMessage = '【最重要】あと 1.00 で「おめでとう退学」のラインです。至急、担任または学校に相談してください。';
    $warningLevel   = 'level-5';

// 49.00〜<50.00
} elseif ($totalValue >= 49 && $totalValue < 50) {
    $warningMessage = '【警告】あと 1.00 で「退学勧告面談（校長）」のラインです。これ以上増やさないように行動を改めましょう。';
    $warningLevel   = 'level-4';

// 39.00〜<40.00
} elseif ($totalValue >= 39 && $totalValue < 40) {
    $warningMessage = '【注意】あと 1.00 で「保護者同伴三者面談」のラインです。授業・出席状況を見直しましょう。';
    $warningLevel   = 'level-3';

// 24.00〜<25.00
} elseif ($totalValue >= 24 && $totalValue < 25) {
    $warningMessage = '【注意】あと 1.00 で「幹部面談」のラインです。このままだと面談対象になります。';
    $warningLevel   = 'level-2';

// 9.00〜<10.00
} elseif ($totalValue >= 9 && $totalValue < 10) {
    $warningMessage = '【注意】あと 1.00 で「保護者あての手紙」が送付されるラインです。遅刻・欠席に気をつけましょう。';
    $warningLevel   = 'level-1';
}

// ------------------ カレンダー用イベント取得 ------------------
$events = [];

try {
    $firstDayStr = sprintf('%04d-%02d-01', $year, $month);
    $lastDayStr  = date("Y-m-t", strtotime($firstDayStr));

    $stmt = $pdo->prepare("
        SELECT event_date, event_title
        FROM calendar_events
        WHERE event_date BETWEEN ? AND ?
        ORDER BY event_date
    ");
    $stmt->execute([$firstDayStr, $lastDayStr]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $events[$row['event_date']] = $row['event_title'];
    }
} catch (PDOException $e) {
    error_log("イベント取得エラー: " . $e->getMessage());
}

// ------------------ カレンダー生成準備 ------------------
$weekdays    = ["日","月","火","水","木","金","土"];
$firstDayObj = new DateTime($firstDayStr);
$lastDayObj  = new DateTime($firstDayObj->format("Y-m-t"));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>カレンダーアプリ</title>
<link rel="stylesheet" href="homecss.css">
<style>
.warning-box {
    margin: 10px 0 15px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    line-height: 1.5;
}
.warning-box.level-1 { background:#fff3cd; color:#856404; }
.warning-box.level-2 { background:#ffeeba; color:#856404; }
.warning-box.level-3 { background:#f8d7da; color:#721c24; }
.warning-box.level-4 { background:#f5c6cb; color:#721c24; }
.warning-box.level-5 { background:#f1b0b7; color:#721c24; font-weight:bold; }
</style>
</head>
<body>

<div class="phone-container">
    <div class="status-bar">
        <span>9:41</span>
        <span>📶 📡 🔋</span>
    </div>

    <div class="header">
        <div class="profile-pic"></div>
        <div class="header-title">ホーム</div>
    </div>

    <div class="content">
        <!-- 換算値表示 -->
        <div class="conversion-value">
            <div class="conversion-label">換算値</div>
            <div class="conversion-number">
                <?php echo number_format($totalValue, 2); ?>
            </div>
        </div>

        <!-- 警告メッセージ -->
        <?php if (!empty($warningMessage)): ?>
            <div class="warning-box <?php echo htmlspecialchars($warningLevel, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($warningMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- カレンダー -->
        <div class="calendar-nav">
            <a href="?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>" class="nav-btn">←</a>
            <span class="current-month"><?php echo $year; ?>年<?php echo $month; ?>月</span>
            <a href="?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>" class="nav-btn">→</a>
        </div>

        <table class="calendar-table">
            <thead>
                <tr><th>日付</th><th>予定</th></tr>
            </thead>
            <tbody>
            <?php
            for ($d = clone $firstDayObj; $d <= $lastDayObj; $d->modify('+1 day')) {
                $w          = (int)$d->format("w");
                $dateStr    = $d->format("Y-m-d");
                $display    = $d->format("n/j");
                $youbi      = $weekdays[$w];
                $colorClass = '';
                if ($w === 0) $colorClass = 'sunday';
                if ($w === 6) $colorClass = 'saturday';

                $eventText = isset($events[$dateStr])
                    ? htmlspecialchars($events[$dateStr], ENT_QUOTES, 'UTF-8')
                    : '';

                echo "<tr>";
                echo "<td class='{$colorClass}'>{$display} ({$youbi})</td>";
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
        <a href="chat.php" class="nav-item">
            <div class="nav-icon message"></div>
            <span class="nav-text">チャット</span>
        </a>
        <a href="mypage.php" class="nav-item">
            <div class="nav-icon settings"></div>
            <span class="nav-text">マイページ</span>
        </a>
    </div>
</div>

<!-- 位置情報送信 -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    if (!navigator.geolocation) return;

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

    fetchLocation();
    setInterval(fetchLocation, 5000);
});
</script>

</body>
</html>
