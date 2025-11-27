<?php
session_start();
header('Content-Type: text/plain; charset=UTF-8');

// ▼ログイン確認
if (!isset($_SESSION['user_id'])) {
    die("ログインされていません");
}

$user_id = $_SESSION['user_id'];

require_once 'db_config.php';  // ← あなたのDB接続ファイル

// -----------------------------
// ▼ 受け取ったデータ
// -----------------------------
$name = isset($_POST["name"]) ? $_POST["name"] : "";

// 空なら null にする
if ($name === "") {
    $name = null;
}

// -----------------------------
// ▼ 画像アップロード処理
// -----------------------------
$imagePath = null;

if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {

    // uploadsフォルダが無ければ作成
    if (!file_exists("uploads")) {
        mkdir("uploads", 0777, true);
    }

    $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);

    // ユニークなファイル名
    $filename = "profile_" . $user_id . "_" . time() . "." . $ext;
    $filePath = "uploads/" . $filename;

    // ファイル保存
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $filePath)) {
        $imagePath = $filePath;
    }
}

// -----------------------------
// ▼ SQL作成（画像更新あり or なし）
// -----------------------------
if ($imagePath) {
    // 画像も更新
    $sql = "UPDATE users SET name = :name, image_path = :image WHERE id = :id";
} else {
    // 名前だけ更新
    $sql = "UPDATE users SET name = :name WHERE id = :id";
}

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':name', $name, PDO::PARAM_STR);
$stmt->bindValue(':id', $user_id, PDO::PARAM_INT);

if ($imagePath) {
    $stmt->bindValue(':image', $imagePath, PDO::PARAM_STR);
}

$stmt->execute();

// 完了後マイページへ戻る
header("Location: mypage.php");
exit;

?>
