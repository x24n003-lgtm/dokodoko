<?php
// データベース接続設定
$host = '172.16.199.21';
$dbname = 'dokodoko';
$user = 'x24n007';
$pass = 'n051211';
$port = '3306';
 
// 現在のユーザーID（セッションから取得）
session_start();
$current_user_id = $_SESSION['user_id'] ?? 1;
 
// チャットIDを取得
$chat_id = $_GET['chat_id'] ?? 0;
 
if ($chat_id == 0) {
    header('Location: teacherchat.php');
    exit;
}
 
// データベース接続
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
    // メッセージ送信処理（ページ読み込み前に実行）
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
        $message = trim($_POST['message']);
       
        $sql = "INSERT INTO messages (chat_id, sender_id, message, type, created_at)
                VALUES (:chat_id, :sender_id, :message, 'text', NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->bindParam(':sender_id', $current_user_id, PDO::PARAM_INT);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->execute();
       
        // リダイレクトしてページを更新
        header("Location: chatroom.php?chat_id=$chat_id");
        exit;
    }
   
    // チャット相手の情報を取得
    $sql = "
        SELECT u.username AS partner_name
        FROM chat_members cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.chat_id = :chat_id
        AND cm.user_id != :current_user_id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
    $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $chat_info = $stmt->fetch(PDO::FETCH_ASSOC);
   
    if (!$chat_info) {
        header('Location: teacherchat.php');
        exit;
    }
   
    // メッセージを取得
    $sql = "
        SELECT
            m.id,
            m.sender_id,
            m.message,
            m.type,
            m.file_url,
            m.created_at,
            u.username as sender_name
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.chat_id = :chat_id
        ORDER BY m.created_at ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
} catch (PDOException $e) {
    echo "データベースエラー: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($chat_info['partner_name']); ?> - チャット</title>
    <link rel="stylesheet" href="chatroom.css">
</head>
<body>
    <div class="container"> 
        <!-- ヘッダー -->
        <header class="header">
            <div class="status-bar">
                <span>9:41</span>
                <span>📶 📡 🔋</span>
            </div>

            <div class="header-main">
                <a href="teachatp.php" class="back-button">←</a>
                <h1 class="chat-title">
                    <?php echo htmlspecialchars($chat_info['partner_name']); ?>
                </h1>
            </div>

        </header>
 
        <!-- メッセージエリア -->
        <div class="messages-area" id="messagesArea">
            <?php if (empty($messages)): ?>
                <div class="no-messages">
                    <p>まだメッセージがありません<br>最初のメッセージを送信しましょう</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <?php
                    $is_mine = ($message['sender_id'] == $current_user_id);
                    $message_class = $is_mine ? 'message-mine' : 'message-other';
                    ?>
                    <div class="message-wrapper <?php echo $message_class; ?>">
                        <?php if (!$is_mine): ?>
                            <div class="message-avatar">👤</div>
                        <?php endif; ?>
                       
                        <div class="message-content">
                            <?php if (!$is_mine): ?>
                                <div class="message-sender"><?php echo htmlspecialchars($message['sender_name']); ?></div>
                            <?php endif; ?>
                            <div class="message-bubble">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                            <div class="message-time">
                                <?php echo date('H:i', strtotime($message['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
 
        <!-- メッセージ入力エリア -->
        <div class="input-area">
            <form method="POST" action="" class="message-form" id="messageForm">
                <button type="button" class="attach-button">+</button>
                <input type="text" name="message" class="message-input" id="messageInput" placeholder="メッセージを入力..." required autocomplete="off">
                <button type="submit" class="send-button">送信</button>
            </form>
        </div>
 
        <div class="bottom-nav">
            <!-- 左：出席管理 -->
            <a href="syusseki.php" class="nav-item">
                <div class="nav-icon person"></div>
                <span class="nav-text">出席</span>
            </a>
 
            <!-- 中：チャット -->
            <a href="teachatp.php" class="nav-item active">
                <div class="nav-icon message"></div>
                <span class="nav-text">チャット</span>
            </a>
 
            <!-- 右：マイページ -->
            <a href="teachermypage.php" class="nav-item">
                <div class="nav-icon settings"></div>
                <span class="nav-text">マイページ</span>
            </a>
        </div>
    </div>
 
    <script>
        // ページ読み込み時にメッセージエリアを最下部にスクロール
        window.addEventListener('DOMContentLoaded', function() {
            const messagesArea = document.getElementById('messagesArea');
            messagesArea.scrollTop = messagesArea.scrollHeight;
        });
 
        // フォーム送信時の処理
        document.getElementById('messageForm').addEventListener('submit', function() {
            const input = document.getElementById('messageInput');
            if (input.value.trim() === '') {
                return false;
            }
        });
    </script>
</body>
</html>