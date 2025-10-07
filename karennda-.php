<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カレンダーアプリ</title>
    <link rel="stylesheet" href="homecss.css">
</head>
<body>

<?php
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

// ------------------ サンプル予定 ------------------
$events = [
    "$year-06-02" => "php基礎演習めのり切り",
    "$year-06-05" => "卒業研究リーダー決定",
    "$year-06-12" => "卒業研究概要決定",
    "$year-06-15" => "応用ネットワーク演習"
];

// ------------------ カレンダー生成 ------------------
$weekdays = ["日","月","火","水","木","金","土"];
$firstDay = new DateTime("$year-$month-01");
$lastDay  = new DateTime($firstDay->format("Y-m-t"));
?>

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
        <button class="nav-item active">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
        </button>
        <button class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
        </button>
        <button class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
                </svg>
            </div>
        </button>
    </div>
</div>

</body>
</html>