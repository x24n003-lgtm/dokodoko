<?php
session_start();

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

// 学生IDを取得
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$student_id) {
    header('Location: syusseki.php');
    exit();
}

// 学生情報を取得
$student_stmt = $pdo->prepare("SELECT id, username, class_name, logo_image FROM users WHERE id = :id AND user_type = 'student'");
$student_stmt->execute([':id' => $student_id]);
$student = $student_stmt->fetch();

if (!$student) {
    header('Location: syusseki.php');
    exit();
}

// 生徒の換算値合計取得
$totalValueRaw = 0.00;
 
try {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(value), 0) AS total_value
        FROM kansanti
        WHERE student_id = :student_id
    ");
    $stmt->execute([':student_id' => $student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $totalValueRaw = (float)$row['total_value'];
    }
} catch (PDOException $e) {
    error_log("換算値合計取得エラー: " . $e->getMessage());
}
 
// 小数第三位を切り捨てて表示用2桁に合わせる
$totalValue = floor($totalValueRaw * 100) / 100;

// 欠席理由を取得（チャットから）
$absence_stmt = $pdo->prepare("
    SELECT DATE(created_at) as date, message as reason
    FROM messages
    WHERE sender_id = :user_id 
    AND (message LIKE '%休%' OR message LIKE '%欠席%' OR message LIKE '%病院%' OR message LIKE '%体調%')
    ORDER BY created_at DESC
    LIMIT 10
");
$absence_stmt->execute([':user_id' => $student_id]);
$absences = $absence_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['username']); ?> - 詳細</title>
    <link rel="stylesheet" href="student_detail.css">
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
        <a href="syusseki.php" class="back-btn">← 戻る</a>
        <h1 class="header-title">学生詳細</h1>
    </div>

    <!-- 学生情報 -->
    <div class="student-profile">
        <div class="student-name-large"><?php echo htmlspecialchars($student['username']); ?></div>
        <?php if ($student['class_name']): ?>
            <div class="student-class"><?php echo htmlspecialchars($student['class_name']); ?></div>
        <?php endif; ?>
    </div>

    <!-- 換算値 -->
    <div class="kansan-section">
        <div class="kansan-label">換算値</div>
        <div class="kansan-value"><?php echo number_format($totalValue, 2); ?></div>
    </div>

    <!-- コンテンツ -->
    <div class="content">
        <!-- 欠席理由セクション -->
        <div class="section">
            <div class="section-header">
                <span class="section-title">日付</span>
                <span class="section-title">休んだ理由</span>
            </div>
            <div class="section-content">
                <?php if (empty($absences)): ?>
                    <div class="no-data">データなし</div>
                <?php else: ?>
                    <?php foreach ($absences as $absence): ?>
                        <div class="record-row">
                            <span class="record-date"><?php echo date('n/j', strtotime($absence['date'])); ?></span>
                            <span class="record-reason"><?php echo htmlspecialchars($absence['reason']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ボトムナビゲーション -->
    <div class="bottom-nav">
        <a href="syusseki.php" class="nav-item active">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <span class="nav-text">出席</span>
        </a>
        <a href="teacherchat.php" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <span class="nav-text">チャット</span>
        </a>
        <a href="teachermypage.php" class="nav-item">
            <div class="nav-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
            </div>
            <span class="nav-text">マイページ</span>
        </a>
    </div>
</div>

</body>
</html>