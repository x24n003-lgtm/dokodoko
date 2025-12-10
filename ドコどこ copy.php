<?php
include 'db.php';

$username = "x24n001";  
$password = password_hash("pass1234", PASSWORD_DEFAULT);  
$role = "student";

$sql = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
if ($conn->query($sql) === TRUE) {
    echo "ユーザー登録成功！";
} else {
    echo "エラー: " . $conn->error;
}
?>
