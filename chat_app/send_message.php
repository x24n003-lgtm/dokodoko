<?php
session_start();
require 'db.php';

$sender = $_SESSION['user_id'];
$receiver = $_POST['partner'];
$message = $_POST['message'];

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->execute([$sender, $receiver, $message]);
