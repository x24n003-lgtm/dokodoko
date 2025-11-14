<?php
// データベース接続設定
$host = '172.16.199.21';
$dbname = 'dokodoko';
$user = 'x24n007';
$pass = 'n051211';
$port = '3306';
 
// セッション開始
session_start();
$current_user_id = $_SESSION['user_id'] ?? 1; // 現在ログインしているユーザーID
 
// データベース接続
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   
    // 全ユーザーを取得（自分以外）
    $sql = "SELECT id, username AS name FROM users WHERE id != :current_user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
   
} catch (PDOException $e) {
    echo "データベース接続エラー: " . $e->getMessage();
    $users = [];
}
 
// チャットルーム作成・取得の処理
function getOrCreateChatRoom($pdo, $current_user_id, $target_user_id) {
    try {
        // 既存のチャットルームを検索（2人だけのチャット）
        $sql = "
            SELECT cm1.chat_id
            FROM chat_members cm1
            INNER JOIN chat_members cm2 ON cm1.chat_id = cm2.chat_id
            INNER JOIN chats c ON cm1.chat_id = c.id
            WHERE cm1.user_id = :current_user_id
            AND cm2.user_id = :target_user_id
            AND c.is_group = 0
            AND (
                SELECT COUNT(*) FROM chat_members WHERE chat_id = cm1.chat_id
            ) = 2
            LIMIT 1
        ";
       
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
        $stmt->bindParam(':target_user_id', $target_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $existing_chat = $stmt->fetch(PDO::FETCH_ASSOC);
       
        if ($existing_chat) {
            // 既存のチャットルームがある場合
            return $existing_chat['chat_id'];
        }
       
        // 新しいチャットルームを作成
        $pdo->beginTransaction();
       
        // 相手のユーザー名を取得
        $sql = "SELECT username FROM users WHERE id = :target_user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':target_user_id', $target_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
       
        // 現在のユーザー名を取得
        $sql = "SELECT username FROM users WHERE id = :current_user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':current_user_id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
       
        // chatsテーブルにチャットルームを作成
        $chat_name = $current_user['name'] . " × " . $target_user['name'];
        $sql = "INSERT INTO chats (name, is_group, created_at) VALUES (:name, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $chat_name, PDO::PARAM_STR);
        $stmt->execute();
        $chat_id = $pdo->lastInsertId();
       
        // chat_membersテーブルに両ユーザーを追加
        $sql = "INSERT INTO chat_members (chat_id, user_id, joined_at) VALUES (:chat_id, :user_id, NOW())";
       
        // 現在のユーザーを追加
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();
       
        // 相手のユーザーを追加
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $target_user_id, PDO::PARAM_INT);
        $stmt->execute();
       
        $pdo->commit();
        return $chat_id;
       
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}
 
// ユーザーがクリックされた場合の処理
if (isset($_GET['user_id'])) {
    $target_user_id = intval($_GET['user_id']);
    try {
        $chat_id = getOrCreateChatRoom($pdo, $current_user_id, $target_user_id);
        header("Location: chatroom.php?chat_id=$chat_id");
        exit;
    } catch (PDOException $e) {
        echo "エラー: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>橘 チャット</title>
    <link rel="stylesheet" href="chatp.css">
</head>
<body>
    <div class="container">
        <!-- ヘッダー -->
        <header class="header">
            <div class="time">9:41</div>
            <div class="status-icons">
                <span class="signal">📶</span>
                <span class="wifi">📶</span>
                <span class="battery">🔋</span>
            </div>
        </header>
 
        <!-- タイトル -->
        <div class="title">
            <h1>橘 チャット</h1>
        </div>
 
        <!-- 検索バー -->
        <div class="search-bar">
            <span class="search-icon">🔍</span>
            <input type="text" placeholder="Search" id="searchInput">
        </div>
 
        <!-- チャットセクション -->
        <div class="chat-section">
            <h2>ユーザー一覧</h2>
 
            <!-- ユーザーリスト -->
            <div class="chat-list">
                <?php
                if (empty($users)) {
                    echo '<p style="text-align: center; color: #999; padding: 20px;">ユーザーがいません</p>';
                } else {
                    foreach ($users as $user) {
                        echo '<a href="?user_id=' . $user['id'] . '" class="chat-item" style="text-decoration: none; color: inherit;">';
                        echo '<div class="avatar">👤</div>';
                        echo '<div class="chat-info">';
                        echo '<div class="chat-name">' . htmlspecialchars($user['name']) . '</div>';
                        echo '<div class="chat-message">タップしてチャットを開始</div>';
                        echo '</div>';
                        echo '</a>';
                    }
                }
                ?>
            </div>
        </div>
 
        <!-- ボトムナビゲーション -->
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
        // 検索機能
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const chatItems = document.querySelectorAll('.chat-item');
           
            chatItems.forEach(item => {
                const name = item.querySelector('.chat-name').textContent.toLowerCase();
                if (name.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>