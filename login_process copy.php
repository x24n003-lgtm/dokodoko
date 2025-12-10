<?php
session_start();

// MariaDBデータベース接続情報
$host = "172.16.199.21";  // Linux MariaDB の IP
$user = "x24n007";
$pass = "n051211";
$dbname = "dokodoko";  // ← 名前を $dbname に変更
$port = 3306;

// POSTリクエスト以外はログイン画面に戻す
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// 入力値の取得
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// 空チェック
if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'メールアドレスとパスワードを入力してください。';
    header('Location: login.php');
    exit();
}

// メールアドレスの形式チェック
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = '正しいメールアドレスを入力してください。';
    header('Location: login.php');
    exit();
}

try {
    // ✅ ここを修正
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // ✅ 正しい変数名を使用
    $pdo = new PDO($dsn, $user, $pass, $options);

    // メールアドレスでユーザーを検索
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch();

    // ユーザーが存在し、パスワードが正しいかチェック
    if ($user && password_verify($password, $user['password'])) {
        // ログイン成功
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];

        if ($user['user_type'] === 'student') {
            header('Location: karennda-.php');
        } else if ($user['user_type'] === 'teacher') {
            header('Location: syusseki.php');
        } else {
            $_SESSION['error'] = 'ユーザータイプが不正です。';
            session_destroy();
            header('Location: login.php');
        }
        exit();
    } else {
        $_SESSION['error'] = 'メールアドレスまたはパスワードが間違っています。';
        header('Location: login.php');
        exit();
    }

} catch (PDOException $e) {
    error_log("ログインエラー: " . $e->getMessage());
    $_SESSION['error'] = 'システムエラーが発生しました。しばらくしてから再度お試しください。';
    header('Location: login.php');
    exit();
}
?>
