<?php
// ===== セッション開始 =====
session_start();

// ===== メッセージをセッションで管理（DB接続前の一時的な保存） =====
if (!isset($_SESSION['chat_messages'])) {
    $_SESSION['chat_messages'] = [];
}

// ===== DB接続設定 =====
$host = "172.16.199.21";  // Linux MariaDB の IP
$user = "x24n007";
$pass = "n051211";
$dbname = "dokodoko";  // ← 名前を $dbname に変更
$port = 3306;

try {
    // PDO接続（サーバー設定後に有効化）
    // $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // エラーハンドリング
    // echo "接続失敗: " . $e->getMessage();
}

// ===== メッセージ送信処理 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $user_id = 1; // TODO: セッションから実際のユーザーIDを取得
    $chat_room_id = 1; // TODO: 実際のチャットルームIDを取得
    
    if (!empty($message)) {
        // TODO: DBにメッセージを保存
        /*
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, chat_room_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $chat_room_id, $message]);
        */
        
        // セッションに一時保存（DB接続前の代替手段）
        $_SESSION['chat_messages'][] = [
            'id' => count($_SESSION['chat_messages']) + 1,
            'user_id' => $user_id,
            'name' => 'あなた',
            'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), // XSS対策
            'created_at' => date('Y-m-d H:i:s'),
            'profile_image' => null,
            'is_own' => true
        ];
        
        // デバッグ用（実際の運用では削除）
        error_log('メッセージが保存されました: ' . $message);
        error_log('現在のメッセージ数: ' . count($_SESSION['chat_messages']));
    }
    
    // リダイレクトでPOST再送信を防ぐ
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ===== メッセージ取得処理 =====
// TODO: DBからメッセージを取得
/*
$stmt = $pdo->prepare("
    SELECT m.*, u.name, u.profile_image,
           CASE WHEN m.user_id = ? THEN 1 ELSE 0 END as is_own
    FROM messages m 
    JOIN users u ON m.user_id = u.id 
    WHERE m.chat_room_id = ? 
    ORDER BY m.created_at ASC
");
$stmt->execute([$_SESSION['user_id'], $chat_room_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
*/

// セッションからメッセージを取得（DB接続前の代替手段）
$messages = $_SESSION['chat_messages'];

// デバッグ情報出力（開発時のみ）
if (isset($_GET['debug'])) {
    echo '<pre>';
    echo 'セッションID: ' . session_id() . "\n";
    echo 'メッセージ数: ' . count($messages) . "\n";
    echo 'メッセージ内容: ';
    print_r($messages);
    echo '</pre>';
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>チャット</title>
    <link rel="stylesheet" href="chat.css">
</head>
<body>
    <div class="container">
        <!-- ===== ヘッダー ===== -->
        <div class="header">
            <a href="#" class="back-btn" onclick="goBack(event)">← 戻る</a>
            <div class="chat-info">
                <div class="chat-avatar"></div>
                <div>
                    <div class="chat-name">田中太郎</div>
                    <div class="chat-status">オンライン</div>
                </div>
            </div>
        </div>

        <!-- ===== メッセージ表示エリア ===== -->
        <div class="messages-area" id="messagesArea">
            <?php if (empty($messages)): ?>
                <!-- メッセージがない場合の表示 -->
                <div class="no-messages">
                    <p>メッセージを送信して<br>チャットを始めましょう</p>
                </div>
            <?php else: ?>
                <!-- 日付区切り -->
                <div class="date-divider">
                    <span class="date-text"><?= date('Y年n月j日') ?></span>
                </div>

                <!-- メッセージ一覧（PHP出力） -->
                <?php foreach ($messages as $index => $message): ?>
                    <div class="message <?= $message['is_own'] ? 'own' : '' ?>" data-message-id="<?= $message['id'] ?>">
                        <?php if (!$message['is_own']): ?>
                            <div class="message-avatar"></div>
                        <?php endif; ?>
                        <div class="message-content">
                            <div class="message-bubble">
                                <div class="message-text"><?= nl2br(htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8')) ?></div>
                            </div>
                            <div class="message-time">
                                <?= date('H:i', strtotime($message['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- デバッグ情報（開発中のみ表示） -->
                <?php if (count($messages) > 0): ?>
                    <div style="text-align: center; color: #999; font-size: 12px; margin: 10px 0; padding: 5px; background: #f8f9fa; border-radius: 5px;">
                        メッセージ数: <?= count($messages) ?> 件
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- ===== メッセージ入力エリア ===== -->
        <div class="input-area">
            <form method="POST" id="messageForm" style="display: flex; width: 100%; align-items: flex-end; gap: 12px;">
                <textarea 
                    name="message" 
                    class="message-input" 
                    id="messageInput" 
                    placeholder="メッセージを入力..."
                    rows="1"
                    required
                ></textarea>
                <button type="submit" class="send-btn" id="sendBtn">
                    <!-- 送信アイコン -->
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22,2 15,22 11,13 2,9 22,2"></polygon>
                    </svg>
                </button>
            </form>
        </div>

        <!-- ===== ボトムナビゲーション ===== -->
        <div class="bottom-nav">
            <a href="karennda-.php" class="nav-item">
                <div class="nav-icon person"></div>
                <span class="nav-text">プロフィール</span>
            </a>
            <a href="chat.php" class="nav-item active">
                <div class="nav-icon message"></div>
                <span class="nav-text">メッセージ</span>
            </a>
            <a href="mypage.php" class="nav-item">
                <div class="nav-icon settings"></div>
                <span class="nav-text">設定</span>
            </a>
        </div>
    </div>

    <script>
        // ===== 戻るボタンの機能 =====
        function goBack(event) {
            event.preventDefault();
            
            if (window.history.length > 1) {
                window.history.back();
                console.log('前のページに戻りました');
            } else {
                console.log('履歴がないため、プロフィールページにリダイレクトします');
                window.location.href = 'profile.php';
            }
        }

        // ===== DOM要素の取得 =====
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const messagesArea = document.getElementById('messagesArea');

        // ===== 自動高さ調整（テキストエリア） =====
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            
            sendBtn.disabled = this.value.trim() === '';
        });

        // ===== Enterキーでの送信制御 =====
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim() !== '') {
                    messageForm.submit();
                }
            }
        });

        // ===== メッセージエリアを最下部にスクロール =====
        function scrollToBottom() {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // ===== フォーム送信時の処理 =====
        messageForm.addEventListener('submit', function(e) {
            const message = messageInput.value.trim();
            if (message === '') {
                e.preventDefault();
                alert('メッセージを入力してください');
                return false;
            }
            
            sendBtn.disabled = true;
            const originalHTML = sendBtn.innerHTML;
            sendBtn.innerHTML = '<div style="width: 20px; height: 20px; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>';
            messageInput.disabled = true;
            
            if (!document.querySelector('#spin-animation')) {
                const style = document.createElement('style');
                style.id = 'spin-animation';
                style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
            }
        });

        // ===== ページ読み込み時の初期化 =====
        window.addEventListener('load', function() {
            scrollToBottom();
            messageInput.focus();
            sendBtn.disabled = messageInput.value.trim() === '';
            
            console.log('ページロード完了');
            console.log('現在のメッセージ要素数:', document.querySelectorAll('.message').length);
            console.log('自分のメッセージ数:', document.querySelectorAll('.message.own').length);
        });
    </script>
</body>
</html>