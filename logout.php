<?php
session_start();

// --- 「はい」ボタンが押されたとき ---
if (isset($_POST['action']) && $_POST['action'] === 'yes') {
    // セッション変数を空に
    $_SESSION = array();

    // セッションID名でクッキーが記録されていたら削除
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }

    // セッションを完全に破棄
    session_destroy();

    // ログイン画面に遷移
    header('Location: login.php');
    exit;
}

// --- 「いいえ」ボタンが押されたとき ---
if (isset($_POST['action']) && $_POST['action'] === 'no') {
    // セッションはそのまま維持して遷移
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>ログアウト確認</title>
    <link rel="stylesheet" href="logout.css">
</head>
<body>
    <p>ログアウトしますか？</p>
    <form method="post">
        <button type="submit" name="action" value="yes" class="btn yes">はい</button>
        <button type="submit" name="action" value="no" class="btn no">いいえ</button>
    </form>
</body>
</html>
