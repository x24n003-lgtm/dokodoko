<?php
// ===== セッション開始 =====
session_start();

// ===== メッセージをセッションで管理（DB接続前の一時的な保存） =====
if (!isset($_SESSION['chat_messages'])) {
    $_SESSION['chat_messages'] = [];
}

// ===== DB接続設定 =====
// TODO: サーバー設定後に実際の値を入力してください
$host = 'localhost';        // DBホスト名
$dbname = 'your_database';  // データベース名
$username = 'your_user';    // DBユーザー名
$password = 'your_pass';    // DBパスワード

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
    <style>
        /* ===== 基本スタイル ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        .container {
            max-width: 414px;
            margin: 0 auto;
            background-color: white;
            min-height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
            padding-bottom: 80px; /* 入力エリア分の余白 */
        }

        /* ===== ヘッダーエリア ===== */
        .header {
            display: flex;
            align-items: center;
            padding: 16px;
            background-color: white;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .back-btn {
            font-size: 18px;
            color: #007AFF;
            text-decoration: none;
            margin-right: 16px;
        }

        .chat-info {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e0e0e0;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="35" r="20" fill="%23999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80 Z" fill="%23999"/></svg>');
            background-size: cover;
            background-position: center;
            margin-right: 12px;
        }

        .chat-name {
            font-size: 17px;
            font-weight: 600;
            color: #000;
        }

        .chat-status {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        /* ===== メッセージエリア ===== */
        .messages-area {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background-color: #f8f9fa;
        }

        .message {
            margin-bottom: 16px;
            display: flex;
            align-items: flex-end;
        }

        .message.own {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #e0e0e0;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="35" r="20" fill="%23999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80 Z" fill="%23999"/></svg>');
            background-size: cover;
            background-position: center;
            margin: 0 8px;
        }

        .message.own .message-avatar {
            display: none;
        }

        .message-content {
            max-width: 70%;
        }

        .message.own .message-content {
            margin-right: 8px;
        }

        .message-bubble {
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
            position: relative;
        }

        .message:not(.own) .message-bubble {
            background-color: #e9ecef;
            color: #000;
            border-bottom-left-radius: 4px;
        }

        .message.own .message-bubble {
            background-color: #007AFF;
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-text {
            font-size: 16px;
            line-height: 1.4;
        }

        .message-time {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
            text-align: right;
        }

        .message.own .message-time {
            color: #ccc;
        }

        /* ===== 入力エリア ===== */
        .input-area {
            background-color: white;
            border-top: 1px solid #e0e0e0;
            padding: 12px 16px;
            display: flex;
            align-items: flex-end;
            gap: 12px;
            position: sticky;
            bottom: 0;
            z-index: 50;
        }

        .message-input {
            flex: 1;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            padding: 10px 16px;
            font-size: 16px;
            outline: none;
            resize: none;
            min-height: 40px;
            max-height: 100px;
        }

        .message-input:focus {
            border-color: #007AFF;
        }

        .send-btn {
            background-color: #007AFF;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-btn:hover {
            background-color: #0056CC;
        }

        .send-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        /* ===== ボトムナビゲーション ===== */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 414px;
            background-color: white;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-around;
            padding: 12px 0 34px;
            z-index: 40;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #666;
            text-decoration: none;
        }

        .nav-item.active {
            color: #007AFF;
        }

        .nav-icon {
            width: 24px;
            height: 24px;
            margin-bottom: 4px;
            background-size: contain;
        }

        .nav-icon.person {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>');
        }

        .nav-icon.message {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>');
        }

        .nav-icon.settings {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>');
        }

        .nav-text {
            font-size: 11px;
        }

        /* ===== 日付区切り ===== */
        .date-divider {
            text-align: center;
            margin: 20px 0;
        }

        .date-text {
            background-color: rgba(0, 0, 0, 0.1);
            color: #666;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
        }

        /* ===== メッセージがない場合のスタイル ===== */
        .no-messages {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 300px;
        }

        .no-messages p {
            text-align: center;
            color: #666;
            font-size: 16px;
            line-height: 1.5;
        }
        /* ===== レスポンシブ対応 ===== */
        @media (max-width: 414px) {
            .container {
                max-width: 100%;
                padding-bottom: 80px;
            }
            
            .bottom-nav {
                max-width: 100%;
            }
            
            .input-area {
                max-width: 100%;
            }
        }
    </style>
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
            <a href="profile.php" class="nav-item">
                <div class="nav-icon person"></div>
                <span class="nav-text">プロフィール</span>
            </a>
            <a href="chat.php" class="nav-item active">
                <div class="nav-icon message"></div>
                <span class="nav-text">メッセージ</span>
            </a>
            <a href="settings.php" class="nav-item">
                <div class="nav-icon settings"></div>
                <span class="nav-text">設定</span>
            </a>
        </div>
    </div>

    <script>
        // ===== 戻るボタンの機能 =====
        function goBack(event) {
            event.preventDefault(); // デフォルトのリンク動作を防ぐ
            
            // ブラウザの履歴があるかチェック
            if (window.history.length > 1) {
                window.history.back();
                console.log('前のページに戻りました');
            } else {
                // 履歴がない場合のフォールバック
                console.log('履歴がないため、プロフィールページにリダイレクトします');
                window.location.href = 'profile.php'; // プロフィールページにフォールバック
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
            
            // 送信ボタンの有効/無効切り替え
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
            
            // 送信中の状態を表示
            sendBtn.disabled = true;
            const originalHTML = sendBtn.innerHTML;
            sendBtn.innerHTML = '<div style="width: 20px; height: 20px; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite;"></div>';
            messageInput.disabled = true;
            
            // アニメーションのCSSを追加
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
            
            // デバッグ情報をコンソールに出力
            console.log('ページロード完了');
            console.log('現在のメッセージ要素数:', document.querySelectorAll('.message').length);
            console.log('自分のメッセージ数:', document.querySelectorAll('.message.own').length);
        });

        // ===== リアルタイム更新（Ajax実装例 - 将来の拡張用） =====
        /*
        function fetchNewMessages() {
            fetch('api/get_messages.php?last_id=' + lastMessageId)
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        // 新しいメッセージを画面に追加
                        data.messages.forEach(message => {
                            addMessageToUI(message);
                        });
                        scrollToBottom();
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // 3秒ごとに新着メッセージをチェック
        setInterval(fetchNewMessages, 3000);
        */
    </script>
</body>
</html>