<?php
// データベース接続テスト
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>MariaDB接続テスト</h2>";
echo "<hr>";

// 接続情報
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";
$port = 3306;

echo "<h3>接続情報</h3>";
echo "<pre>";
echo "ホスト: {$host}\n";
echo "ユーザー: {$user}\n";
echo "パスワード: " . str_repeat('*', strlen($pass)) . "\n";
echo "データベース: {$db}\n";
echo "ポート: {$port}\n";
echo "</pre>";

echo "<h3>接続テスト結果</h3>";

try {
    // DSN作成
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    echo "<p>DSN: <code>{$dsn}</code></p>";
    
    // PDOオプション
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    // 接続試行
    echo "<p>接続を試みています...</p>";
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; color: #155724;'>";
    echo "<h2>✅ 接続成功！</h2>";
    echo "<p>MariaDBに正常に接続できました。</p>";
    echo "</div>";
    
    // サーバー情報を取得
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "<h3>サーバー情報</h3>";
    echo "<p>バージョン: <strong>{$version}</strong></p>";
    
    // テーブル一覧を取得
    echo "<h3>テーブル一覧</h3>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            // 各テーブルの行数を取得
            $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            echo "<li><strong>{$table}</strong> ({$count} 行)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ テーブルが見つかりませんでした。</p>";
    }
    
    // ユーザー情報を確認
    if (in_array('users', $tables)) {
        echo "<h3>ユーザー情報（最新5件）</h3>";
        $stmt = $pdo->query("SELECT id, username, email, user_type, class_name FROM users ORDER BY id DESC LIMIT 5");
        $users = $stmt->fetchAll();
        
        if (count($users) > 0) {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>ID</th><th>名前</th><th>メール</th><th>タイプ</th><th>クラス</th>";
            echo "</tr>";
            
            foreach ($users as $user) {
                $typeColor = ($user['user_type'] === 'student') ? '#4169e1' : '#ff8c00';
                echo "<tr>";
                echo "<td>{$user['id']}</td>";
                echo "<td>{$user['username']}</td>";
                echo "<td>{$user['email']}</td>";
                echo "<td style='color: {$typeColor}; font-weight: bold;'>{$user['user_type']}</td>";
                echo "<td>{$user['class_name']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; color: #721c24;'>";
    echo "<h2>❌ 接続失敗</h2>";
    echo "<p><strong>エラーメッセージ:</strong></p>";
    echo "<pre style='background: white; padding: 10px; border-radius: 3px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
    
    echo "<h3>考えられる原因:</h3>";
    echo "<ul>";
    echo "<li>MariaDBサーバーが起動していない</li>";
    echo "<li>IPアドレスが間違っている</li>";
    echo "<li>ユーザー名またはパスワードが間違っている</li>";
    echo "<li>データベース名が間違っている</li>";
    echo "<li>ファイアウォールでポート3306がブロックされている</li>";
    echo "<li>MariaDBがリモート接続を許可していない</li>";
    echo "</ul>";
    
    echo "<h3>確認事項:</h3>";
    echo "<ol>";
    echo "<li>MariaDBサーバーで以下のコマンドを実行:<br>";
    echo "<code>sudo systemctl status mariadb</code></li>";
    echo "<li>リモート接続を許可しているか確認:<br>";
    echo "<code>mysql -u root -p</code><br>";
    echo "<code>GRANT ALL PRIVILEGES ON dokodoko.* TO 'x24n007'@'%' IDENTIFIED BY 'n051211';</code><br>";
    echo "<code>FLUSH PRIVILEGES;</code></li>";
    echo "<li><code>/etc/mysql/mariadb.conf.d/50-server.cnf</code> で<br>";
    echo "<code>bind-address</code> を <code>0.0.0.0</code> に設定</li>";
    echo "<li>ファイアウォールでポート3306を開放:<br>";
    echo "<code>sudo ufw allow 3306/tcp</code></li>";
    echo "</ol>";
    
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='syusseki.php'>← 出席管理画面に戻る</a></p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        background: #f5f5f5;
        max-width: 1000px;
        margin: 0 auto;
    }
    h2, h3 {
        color: #333;
    }
    pre, code {
        background: #f0f0f0;
        padding: 5px;
        border-radius: 3px;
        font-family: monospace;
    }
    table {
        background: white;
        margin: 10px 0;
    }
</style>