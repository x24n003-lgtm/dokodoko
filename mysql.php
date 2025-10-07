<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sqlとリンク</title>
</head>
<body>
    <?php
    //データベース接続
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ドコどこ";

    $conn = new mysqli($servername,$username,$password,$dbname);

    //接続チェック
    if($conn->connect_error){
        die("接続失敗：". $conn->connect_error);
    }

    //SQLを準備
    $sql = "SELECT * FROM `家計簿`;";

    //SQLを実行
    $resuit = $conn->query($sql);

    //結果を取り出す
    if($resuit->num_rows > 0){
        while($row = $resuit->fetch_assoc()){
            echo "ユーザー名:" . $row["ユーザー名"]." | 住所: " . $row["住所"]. " | 緯度: " . $row["緯度"]." | 経度: " . $row["経度"].
            " | 学校の住所: " . $row["学校の住所"]." | パスワード: " . $row["パスワード"]." | 役割: " . $row["役割"]." | 電話番号: " . $row["電話番号"]." | 性別: " . $row["性別"]. "<br>";
        }}else{
            echo "データなし";
        }
    

    //接続を閉じる
    $conn->close();
    ?>
</body>
</html>
