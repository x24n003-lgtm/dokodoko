<?php
session_start();
 
// セッション変数の中身を空にする処理
$_SESSION = array();
 
// セッションID名でクッキーが記録されていたら、
// クッキーを破棄する処理
if(isset($_Cookie[session_name()])) { // もしCookie変数の中身が空ではなかったら（issetは!emptyと近い）
    setcookie(session_name(),"",time()-42000); // 42000秒前にcookieを無効
}
 
// セッションIDを無効なものにする処理
session_destroy();
 
?>
 
<html>
    <head>
        <meta charset="utf-8">
        <title>ログアウト画面</title>
    </head>
 
    <body>
        <p>ログアウトしました</p>
        <input type="button" onclick="location.href='login.php'" value="トップへ戻る">
    </body>
</html>