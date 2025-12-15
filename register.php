<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ===============================
// フォーム値取得
// ===============================
$email        = $_POST['email'] ?? '';
$username     = $_POST['username'] ?? '';
$password     = $_POST['password'] ?? '';
$phone        = $_POST['phone'] ?? '';
$gender       = $_POST['gender'] ?? '';
$home_address = $_POST['home_address'] ?? '';
$class_name   = $_POST['class_name'] ?? null;

// GPS値（送信されていれば使用）
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

// ===============================
// 入力チェック
// ===============================
if (empty($email) || empty($username) || empty($password) || empty($gender) || empty($home_address)) {
    $_SESSION['error'] = "必須項目が未入力です";
    header("Location: newacc.php");
    exit;
}

// ===============================
// パスワードハッシュ化
// ===============================
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// ===============================
// ユーザータイプ判定
// ===============================
$user_type = str_starts_with($email, 'x') ? 'student' : 'teacher';

// ===============================
// Geocoding API で住所→座標取得
// ===============================
function geocodeAddress($address, $apiKey) {
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&key={$apiKey}";
    $json = file_get_contents($url);
    if (!$json) return [null, null];

    $data = json_decode($json, true);
    if (isset($data['results'][0]['geometry']['location'])) {
        return [
            (float)$data['results'][0]['geometry']['location']['lat'],
            (float)$data['results'][0]['geometry']['location']['lng']
        ];
    }
    return [null, null];
}

$apiKey = "AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg";

// home_lat / home_lng は必ず住所から
[$home_lat, $home_lng] = geocodeAddress($home_address, $apiKey);

if ($home_lat === null || $home_lng === null) {
    $_SESSION['error'] = "住所から位置情報を取得できませんでした。正しい住所を入力してください。";
    header("Location: newacc.php");
    exit;
}

// lat / lng が空なら住所から取得
if ($lat === null || $lng === null) {
    [$lat, $lng] = geocodeAddress($home_address, $apiKey);
}

// ===============================
// DB 接続
// ===============================
$conn = new mysqli("172.16.199.21", "x24n007", "n051211", "dokodoko");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("DB接続失敗: " . $conn->connect_error);
}

// ===============================
// INSERT
// ===============================
$stmt = $conn->prepare("
    INSERT INTO users (
        username, email, password, phone, gender,
        home_address, home_lat, home_lng, lat, lng,
        created_at, user_type, class_name
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
");

// double の場合 bind_param は "d"
$lat = $lat !== null ? (float)$lat : null;
$lng = $lng !== null ? (float)$lng : null;
$home_lat = (float)$home_lat;
$home_lng = (float)$home_lng;

$stmt->bind_param(
    "ssssssddddss",
    $username, $email, $hashedPassword, $phone, $gender,
    $home_address, $home_lat, $home_lng, $lat, $lng,
    $user_type, $class_name
);

// ===============================
// 実行
// ===============================
if ($stmt->execute()) {
    $_SESSION['success'] = "登録が完了しました";
    header("Location: login.php");
    exit;
} else {
    // メール重複など
    $_SESSION['error'] = "登録に失敗しました: " . $stmt->error;
    header("Location: newacc.php");
    exit;
}
?>
