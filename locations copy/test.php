<?php
// エラー表示
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// DB設定
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";
$port = 3306;

// POST 以外拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: test2.php');
    exit();
}
try {
    // ここにあなたの全部の処理を書く
    // DB接続、住所→lat/lng、INSERT など
}
catch (Exception $e) {
    // 例外が起きたときの処理
    $_SESSION['error'] = "内部エラー: " . $e->getMessage();
    header('Location: test2.php');
    exit();
}

    // PDO接続
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // ---------- 入力値取得 ----------
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $home_address = trim($_POST['home_address'] ?? '');
    $class_name = trim($_POST['class_name'] ?? '');

    // ---------- 入力チェック ----------
    if (!$email || !$username || !$password || !$phone || !$home_address || !$gender) {
        $_SESSION['error'] = "すべての項目を入力してください。";
        header('Location: test2.php'); exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "正しいメールアドレスを入力してください。";
        header('Location: test2.php'); exit();
    }

    if ($password !== $password_confirm) {
        $_SESSION['error'] = "パスワードが一致しません。";
        header('Location: test2.php'); exit();
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = "パスワードは6文字以上です。";
        header('Location: test2.php'); exit();
    }

    // 重複チェック（メール）
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "このメールアドレスは既に使われています。";
        header('Location: test2.php'); exit();
    }

    // 重複チェック（ユーザー名）
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "このユーザー名は既に使われています。";
        header('Location: test2.php'); exit();
    }

    // ---------- 学生 or 教員 判定 ----------
    $user_type = strtolower($email)[0] === 'x' ? 'student' : 'teacher';

    if ($user_type === 'student' && empty($class_name)) {
        $_SESSION['error'] = "学生はクラスを選択してください。";
        header('Location: test2.php'); exit();
    }

    // ---------- ✔️ Google Maps API を使って住所→緯度経度 ----------
    $api_key = "AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg";   // ✅ あなたのAPIキーを入れて！
    $encoded_address = urlencode($home_address);

    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$encoded_address}&key={$api_key}";
    $json = file_get_contents($url);
    $geodata = json_decode($json, true);

    if ($geodata["status"] !== "OK") {
        $_SESSION['error'] = "住所から位置情報を取得できませんでした。";
        header('Location: test2.php'); exit();
    }

    $lat = $geodata["results"][0]["geometry"]["location"]["lat"];
    $lng = $geodata["results"][0]["geometry"]["location"]["lng"];

    // ---------- パスワードハッシュ ----------
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ---------- 登録 ----------
    $sql = "INSERT INTO users (
                email, username, password, phone, gender,
                home_address, user_type, class_name,
                lat, lng, location_updated_at, created_at
            ) VALUES (
                :email, :username, :password, :phone, :gender,
                :home_address, :user_type, :class_name,
                :lat, :lng, NOW(), NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':username' => $username,
        ':password' => $hashed_password,
        ':phone' => $phone,
        ':gender' => $gender,
        ':home_address' => $home_address,
        ':user_type' => $user_type,
        ':class_name' => $class_name ?: null,
        ':lat' => $lat,
        ':lng' => $lng,
    ]);

    // ---------- セッション保存 ----------
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['user_type'] = $user_type;

    // ---------- リダイレクト ----------
   

?>
