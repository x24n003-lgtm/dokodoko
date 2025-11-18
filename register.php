<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

$conn = new mysqli("172.16.199.21", "x24n007", "n051211", "dokodoko");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("DB接続失敗");
}

$email = $_POST['email'];
$username = $_POST['username'];
$pass = $_POST['password'];
$phone = $_POST['phone'];
$gender = $_POST['gender'];
$home_address = $_POST['home_address'];
$class_name = $_POST['class_name'] ?? null;

$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

$hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

// どちらか：教員 / 学生
$user_type = (str_starts_with($email, 'x')) ? "student" : "teacher";

$stmt = $conn->prepare("
    INSERT INTO users (username, email, password, phone, gender, home_address, lat, lng, created_at, user_type, class_name)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
");

$stmt->bind_param(
    "ssssssddss",
    $username,
    $email,
    $hashedPassword,
    $phone,
    $gender,
    $home_address,
    $lat,
    $lng,
    $user_type,
    $class_name
);

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
