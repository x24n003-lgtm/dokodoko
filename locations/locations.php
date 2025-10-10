<?php
header("Content-Type: application/json; charset=UTF-8");

$conn = new mysqli("localhost", "root", "", "sotuken");
if ($conn->connect_error) {
    die("接続失敗: " . $conn->connect_error);
}

$sql = "SELECT name, lat, lng FROM locations";
$result = $conn->query($sql);

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = $row;
}

echo json_encode($locations, JSON_UNESCAPED_UNICODE);
?>
