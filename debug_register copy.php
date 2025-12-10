<?php
// 最優先: エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!-- デバッグ: スクリプト開始 -->\n";
flush();

session_start();

echo "<!-- デバッグ: セッション開始完了 -->\n";
flush();

// データベース接続情報
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";
$port = 3306;

echo "<!-- デバッグ: 変数設定完了 -->\n";
echo "<!-- ホスト: {$host}, DB: {$db} -->\n";
flush();

// POSTリクエストかどうか確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<!DOCTYPE html><html><body>";
    echo "<h2>⚠️ このページは直接アクセスできません</h2>";
    echo "<p>リクエストメソッド: " . $_SERVER['REQUEST_METHOD'] . "</p>";
    echo "<p><a href='newacc.php'>新規登録画面に戻る</a></p>";
    echo "</body></html>";
    exit();
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>登録処理</title></head><body>";
echo "<h2>登録処理デバッグ</h2>";
echo "<hr>";

// 受信データの確認
echo "<h3>1. 受信データ</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// データベース接続テスト
echo "<h3>2. データベース接続テスト</h3>";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    echo "<p>DSN: <code>{$dsn}</code></p>";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<p style='color: green;'>✅ データベース接続成功</p>";
    
    // 入力データ取得
    echo "<h3>3. 入力データの処理</h3>";
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $password_confirm = isset($_POST['password_confirm']) ? trim($_POST['password_confirm']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $home_address = isset($_POST['home_address']) ? trim($_POST['home_address']) : '';
    $class_name = isset($_POST['class_name']) ? trim($_POST['class_name']) : null;
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>項目</th><th>値</th></tr>";
    echo "<tr><td>メールアドレス</td><td>" . htmlspecialchars($email) . "</td></tr>";
    echo "<tr><td>ユーザー名</td><td>" . htmlspecialchars($username) . "</td></tr>";
    echo "<tr><td>パスワード</td><td>" . str_repeat('*', strlen($password)) . " ({strlen($password)}文字)</td></tr>";
    echo "<tr><td>パスワード確認</td><td>" . str_repeat('*', strlen($password_confirm)) . "</td></tr>";
    echo "<tr><td>電話番号</td><td>" . htmlspecialchars($phone) . "</td></tr>";
    echo "<tr><td>性別</td><td>" . htmlspecialchars($gender) . "</td></tr>";
    echo "<tr><td>住所</td><td>" . htmlspecialchars($home_address) . "</td></tr>";
    echo "<tr><td>クラス</td><td>" . htmlspecialchars($class_name ?? '未設定') . "</td></tr>";
    echo "</table>";
    
    // バリデーション
    echo "<h3>4. バリデーション</h3>";
    $errors = [];
    
    if (empty($email)) $errors[] = "メールアドレスが空です";
    if (empty($username)) $errors[] = "ユーザー名が空です";
    if (empty($password)) $errors[] = "パスワードが空です";
    if (empty($phone)) $errors[] = "電話番号が空です";
    if (empty($gender)) $errors[] = "性別が空です";
    if (empty($home_address)) $errors[] = "住所が空です";
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "メールアドレスの形式が不正です";
    }
    
    if ($password !== $password_confirm) {
        $errors[] = "パスワードが一致しません";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "パスワードは6文字以上必要です";
    }
    
    if (count($errors) > 0) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "<h4>❌ バリデーションエラー:</h4><ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul></div>";
        echo "<p><a href='newacc.php'>戻る</a></p>";
        echo "</body></html>";
        exit();
    }
    
    echo "<p style='color: green;'>✅ バリデーション通過</p>";
    
    // ユーザータイプ判定
    echo "<h3>5. ユーザータイプ判定</h3>";
    $email_lower = strtolower($email);
    $first_char = substr($email_lower, 0, 1);
    
    echo "<p>メールアドレス: {$email}</p>";
    echo "<p>小文字変換後: {$email_lower}</p>";
    echo "<p>最初の文字: <strong>{$first_char}</strong></p>";
    
    if ($first_char === 'x') {
        $user_type = 'student';
        echo "<p style='color: blue;'>✅ 判定結果: <strong>学生</strong></p>";
        
        if (empty($class_name)) {
            echo "<p style='color: red;'>❌ エラー: 学生はクラスが必須です</p>";
            echo "<p><a href='newacc.php'>戻る</a></p>";
            echo "</body></html>";
            exit();
        }
    } else {
        $user_type = 'teacher';
        $class_name = null;
        echo "<p style='color: orange;'>✅ 判定結果: <strong>教員</strong></p>";
    }
    
    // 重複チェック
    echo "<h3>6. 重複チェック</h3>";
    
    // メール重複チェック
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "<p style='color: red;'>❌ このメールアドレスは既に登録されています</p>";
        echo "<p><a href='newacc.php'>戻る</a></p>";
        echo "</body></html>";
        exit();
    }
    echo "<p>✅ メールアドレス: 重複なし</p>";
    
    // ユーザー名重複チェック（nameカラムを使用）
    $stmt = $pdo->prepare("SELECT id FROM users WHERE name = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo "<p style='color: red;'>❌ このユーザー名は既に使用されています</p>";
        echo "<p><a href='newacc.php'>戻る</a></p>";
        echo "</body></html>";
        exit();
    }
    echo "<p>✅ ユーザー名: 重複なし</p>";
    
    // パスワードハッシュ化
    echo "<h3>7. パスワードのハッシュ化</h3>";
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    echo "<p>✅ ハッシュ化完了 (長さ: " . strlen($hashed_password) . "文字)</p>";
    
    // データベースに挿入
    echo "<h3>8. データベースへの挿入</h3>";
    
    $pdo->beginTransaction();
    
    $sql = "INSERT INTO users (email, name, password, phone, gender, home_address, user_type, class_name, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    echo "<p>SQL: <code>" . htmlspecialchars($sql) . "</code></p>";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $email,
        $username,
        $hashed_password,
        $phone,
        $gender,
        $home_address,
        $user_type,
        $class_name
    ]);
    
    if ($result) {
        $pdo->commit();
        $new_user_id = $pdo->lastInsertId();
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "<h2>✅ 登録成功！</h2>";
        echo "<p>ユーザーID: {$new_user_id}</p>";
        echo "<p>ユーザータイプ: {$user_type}</p>";
        echo "</div>";
        
        // セッション設定
        session_regenerate_id(true);
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['user_type'] = $user_type;
        
        // 遷移先
        $redirect = ($user_type === 'student') ? 'karennda-.php' : 'syusseki.php';
        echo "<p>3秒後に <strong>{$redirect}</strong> に遷移します...</p>";
        echo "<p>自動遷移しない場合は<a href='{$redirect}'>こちら</a>をクリック</p>";
        echo "<script>setTimeout(function(){ window.location.href = '{$redirect}'; }, 3000);</script>";
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h2>❌ データベースエラー</h2>";
    echo "<p><strong>エラーメッセージ:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p><strong>エラーコード:</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>エラー発生場所:</strong> " . $e->getFile() . " (行 " . $e->getLine() . ")</p>";
    echo "</div>";
    echo "<p><a href='newacc.php'>戻る</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h2>❌ エラー</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
    echo "<p><a href='newacc.php'>戻る</a></p>";
}

echo "</body></html>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        max-width: 1000px;
        margin: 0 auto;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        margin: 10px 0;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th {
        background: #f0f0f0;
    }
    code {
        background: #f5f5f5;
        padding: 2px 5px;
        border-radius: 3px;
    }
    pre {
        background: #f5f5f5;
        padding: 10px;
        border-radius: 5px;
        overflow-x: auto;
    }
</style>