<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sotuken";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("接続失敗: " . $conn->connect_error);

// テーブル名を locations に修正
$sql = "SELECT * FROM locations";
$result = $conn->query($sql);

$locations = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($locations, JSON_UNESCAPED_UNICODE);

$conn->close();
?>
