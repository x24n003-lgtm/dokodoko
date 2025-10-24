<?php
// 1. DB接続
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ドコどこ";

$conn = new mysqli($servername,$username,$password,$dbname);

// 接続チェック
if ($conn->connect_error) {
    die("接続失敗: " . $conn->connect_error);
}

// 2. フォームからデータを受け取る（例：POST送信）
$user = $_POST["ユーザー名"];
$jyuusyo = $_POST["住所"];
$ido = $_POST["緯度"];
$keido = $_POST["経度"];
$gakkoujyuusyo =$_POST["学校の住所"];
$pass = $_POST["パスワード"];
$yaku = $_POST["役割"];
$tel = $_POST["電話番号"];
$sei = $_POST["性別"];

// 3. SQL文を作成
$sql = "INSERT INTO 家計簿 (ユーザー名, 住所, 緯度,経度,学校の住所,パスワード,役割,電話番号,性別) VALUES ('$user', '$jyuusyo', '$ido', '$keido', '$gakkoujyuusyo', '$pass', '$yaku', '$tel', '$sei')";

// 4. SQL実行
if ($conn->query($sql) === TRUE) {
    echo "データを追加しました！";
} else {
    echo "エラー: " . $conn->error;
}

// 5. 接続を閉じる
$conn->close();
?>
