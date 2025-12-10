<?php
session_start();
header('Content-Type: text/plain; charset=UTF-8');

// ▼ログイン確認
if (!isset($_SESSION['user_id'])) {
    die("ログインされていません");
}

$user_id = $_SESSION['user_id'];

require_once 'db_config.php';  // PDO接続（$dbh）を作るファイル

// -----------------------------
// ▼ 受け取ったデータ
// -----------------------------
$username = isset($_POST["name"]) ? trim($_POST["name"]) : "";

// 空なら null にする
if ($username === "") {
    $username = null;
}

// -----------------------------
// ▼ 画像アップロード処理
// -----------------------------
$newImagePath = null;

// 画像の保存先（絶対パス）
$uploadDir = __DIR__ . '/uploads/';
$relativeDir = 'uploads/';

// uploads がなければ作る
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {

    // 拡張子チェック
    $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($ext, $allowed, true)) {
        die("許可されていないファイル形式です（jpg/jpeg/png/gif のみ）");
    }

    // ユニークなファイル名
    $filename = "logo_" . $user_id . "_" . time() . "." . $ext;

    $absolutePath = $uploadDir . $filename;     // 実際に保存するフルパス
    $dbPath       = $relativeDir . $filename;   // DBに保存する相対パス

    // 先に旧ファイルを削除
    $old = $dbh->prepare("SELECT logo_image FROM users WHERE id = :id");
    $old->execute([':id' => $user_id]);
    $oldPath = $old->fetchColumn();

    if ($oldPath) {
        $oldFull = __DIR__ . '/' . $oldPath;
        if (file_exists($oldFull)) {
            @unlink($oldFull);
        }
    }

    // ファイル保存
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $absolutePath)) {
        die("画像の保存に失敗しました");
    }

    $newImagePath = $dbPath;
}

// -----------------------------
// ▼ SQL作成（画像更新あり or なし）
// -----------------------------
if ($newImagePath) {
    // 画像あり
    $sql = "UPDATE users 
            SET username = :username, logo_image = :image 
            WHERE id = :id";
} else {
    // 名前だけ
    $sql = "UPDATE users 
            SET username = :username 
            WHERE id = :id";
}

$stmt = $dbh->prepare($sql);
$stmt->bindValue(':username', $username, PDO::PARAM_STR);
$stmt->bindValue(':id', $user_id, PDO::PARAM_INT);

if ($newImagePath) {
    $stmt->bindValue(':image', $newImagePath, PDO::PARAM_STR);
}

$stmt->execute();

// 完了後マイページへ戻る
header("Location: teachermypage.php");
exit;

?>
