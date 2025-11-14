<?php
require_once 'db_config.php';

// ------------------ 基本設定 ------------------
date_default_timezone_set('Asia/Tokyo');

// GETパラメータから年月を受け取る（なければ今月）
$year  = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");
$month = isset($_GET['month']) ? (int)$_GET['month'] : date("n");

// 前月と次月を計算
$prev = strtotime("-1 month", strtotime("$year-$month-01"));
$next = strtotime("+1 month", strtotime("$year-$month-01"));
$prevYear = date("Y", $prev);
$prevMonth = date("n", $prev);
$nextYear = date("Y", $next);
$nextMonth = date("n", $next);

// ------------------ データベースからイベントを取得 ------------------
$pdo = getDbConnection();
$events = [];

try {
    // 指定された月のイベントを取得
    $firstDay = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $lastDay = date("Y-m-t", strtotime($firstDay));
    
    $stmt = $pdo->prepare("SELECT event_date, event_title, event_description FROM calendar_events WHERE event_date BETWEEN ? AND ? ORDER BY event_date");
    $stmt->execute([$firstDay, $lastDay]);
    
    while ($row = $stmt->fetch()) {
        $events[$row['event_date']] = $row['event_title'];
    }
} catch (PDOException $e) {
    error_log("イベント取得エラー: " . $e->getMessage());
}

// ------------------ カレンダー生成 ------------------
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
        <div class="conversion-value">
            <div class="conversion-label">換算値</div>
            <div class="conversion-number">5.66</div>
        </div>

        <div class="calendar-nav">
            <a href="?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>" class="nav-btn">←</a>
            <span class="current-month"><?php echo $year; ?>年<?php echo $month; ?>月</span>
            <a href="?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>" class="nav-btn">→</a>
        </div>

        <table class="calendar-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>予定</th>
                </tr>
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

                    $eventText = isset($events[$dateStr]) ? $events[$dateStr] : "";

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
        <!-- 左：出席管理 -->
        <a href="karennda-.php" class="nav-item active">
            <div class="nav-icon person"></div>
            <span class="nav-text">カレンダー</span>
        </a>

        <!-- 中：チャット -->
        <a href="chat.php" class="nav-item">
            <div class="nav-icon message"></div>
            <span class="nav-text">チャット</span>
        </a>

        <!-- 右：マイページ -->
        <a href="mypage.php" class="nav-item">
            <div class="nav-icon settings"></div>
            <span class="nav-text">マイページ</span>
        </a>
    </div>
</div>

</body>
</html>