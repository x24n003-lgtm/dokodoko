<?php
session_start();
require_once 'db_config.php';

// 簡易認証（実際は適切な認証システムを実装してください）
// $_SESSION['user_type'] = 'teacher'; // 教員として設定

$pdo = getDbConnection();

// イベントの追加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO calendar_events (event_date, event_title, event_description, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['event_date'],
            $_POST['event_title'],
            $_POST['event_description'] ?? '',
            $_SESSION['teacher_name'] ?? '教員'
        ]);
        $message = "イベントを追加しました";
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ?");
        $stmt->execute([$_POST['event_id']]);
        $message = "イベントを削除しました";
    } elseif ($_POST['action'] === 'update') {
        $stmt = $pdo->prepare("UPDATE calendar_events SET event_date = ?, event_title = ?, event_description = ? WHERE id = ?");
        $stmt->execute([
            $_POST['event_date'],
            $_POST['event_title'],
            $_POST['event_description'] ?? '',
            $_POST['event_id']
        ]);
        $message = "イベントを更新しました";
    }
}

// 全イベントを取得
$stmt = $pdo->query("SELECT * FROM calendar_events ORDER BY event_date ASC");
$events = $stmt->fetchAll();

// 年月の取得
$year = isset($_GET['year']) ? (int)$_GET['year'] : date("Y");
$month = isset($_GET['month']) ? (int)$_GET['month'] : date("n");
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教員用 カレンダー管理</title>
    <link rel="stylesheet" href="teacher_calendar.css">
</head>
<body>
    <div class="container">
        <a href="teachermypage.php" class="back-link">← マイページに戻る</a>
        
        <h1>📅 カレンダー管理（教員用）</h1>
        
        <?php if (isset($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- イベント追加フォーム -->
        <div class="form-section">
            <h2>新しいイベントを追加</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="event_date">日付</label>
                    <input type="date" id="event_date" name="event_date" required>
                </div>
                
                <div class="form-group">
                    <label for="event_title">イベント名</label>
                    <input type="text" id="event_title" name="event_title" required placeholder="例: php基礎演習めのり切り">
                </div>
                
                <div class="form-group">
                    <label for="event_description">詳細説明（任意）</label>
                    <textarea id="event_description" name="event_description" placeholder="イベントの詳細を入力してください"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">イベントを追加</button>
            </form>
        </div>

        <!-- イベント一覧 -->
        <div class="events-list">
            <h2>登録済みイベント一覧</h2>
            
            <?php if (empty($events)): ?>
                <p style="color: #999;">まだイベントが登録されていません。</p>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-item">
                        <div class="event-date">📅 <?php echo date('Y年n月j日', strtotime($event['event_date'])); ?></div>
                        <div class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></div>
                        <?php if ($event['event_description']): ?>
                            <div class="event-description"><?php echo nl2br(htmlspecialchars($event['event_description'])); ?></div>
                        <?php endif; ?>
                        <div class="event-meta">
                            登録者: <?php echo htmlspecialchars($event['created_by']); ?> | 
                            登録日: <?php echo date('Y/m/d H:i', strtotime($event['created_at'])); ?>
                        </div>
                        
                        <div class="event-actions">
                            <button class="btn btn-edit" onclick="toggleEdit(<?php echo $event['id']; ?>)">編集</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('本当に削除しますか?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <button type="submit" class="btn btn-danger">削除</button>
                            </form>
                        </div>

                        <!-- 編集フォーム -->
                        <div id="edit-form-<?php echo $event['id']; ?>" class="edit-form">
                            <form method="POST">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                
                                <div class="form-group">
                                    <label>日付</label>
                                    <input type="date" name="event_date" value="<?php echo $event['event_date']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>イベント名</label>
                                    <input type="text" name="event_title" value="<?php echo htmlspecialchars($event['event_title']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>詳細説明</label>
                                    <textarea name="event_description"><?php echo htmlspecialchars($event['event_description']); ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">更新</button>
                                <button type="button" class="btn btn-danger" onclick="toggleEdit(<?php echo $event['id']; ?>)">キャンセル</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleEdit(eventId) {
            const editForm = document.getElementById('edit-form-' + eventId);
            if (editForm.style.display === 'none' || editForm.style.display === '') {
                editForm.style.display = 'block';
            } else {
                editForm.style.display = 'none';
            }
        }
    </script>
</body>
</html>