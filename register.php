<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

$conn = new mysqli("172.16.199.21", "x24n007", "n051211", "dokodoko");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("DB接続失敗: " . $conn->connect_error);
}

// フォームから値を取得
$email = $_POST['email'] ?? '';
$username = $_POST['username'] ?? '';
$pass = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';
$gender = $_POST['gender'] ?? '';
$home_address = $_POST['home_address'] ?? '';
$class_name = $_POST['class_name'] ?? null;
$home_lat = $_POST['lat'] ?? null;
$home_lng = $_POST['lng'] ?? null;

// パスワードハッシュ化
$hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

// ユーザータイプ判定
$user_type = str_starts_with($email, 'x') ? "student" : "teacher";

// 現在位置は登録時は home と同じ
$lat = $home_lat;
$lng = $home_lng;

// NULL の場合は 0 に置き換える（decimal 型対応）
$home_lat = $home_lat !== null ? (float)$home_lat : 0;
$home_lng = $home_lng !== null ? (float)$home_lng : 0;
$lat = $lat !== null ? (float)$lat : 0;
$lng = $lng !== null ? (float)$lng : 0;

// INSERT 文
$stmt = $conn->prepare("
    INSERT INTO users (username, email, password, phone, gender, home_address,
                       home_lat, home_lng,
                       lat, lng,
                       created_at, user_type, class_name)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
");

// bind_param の型文字列を修正
// lat/lng は decimal -> d、他は s
$stmt->bind_param(
    "ssssssddddss",
    $username,
    $email,
    $hashedPassword,
    $phone,
    $gender,
    $home_address,
    $home_lat,
    $home_lng,
    $lat,
    $lng,
    $user_type,
    $class_name
);

// 実行
if ($stmt->execute()) {
    $_SESSION['success'] = "登録が完了しました";
    header("Location: login.php");
    exit;
} else {
    $_SESSION['error'] = "登録に失敗しました: " . $stmt->error;
    header("Location: register.html");
    exit;
}
?>
