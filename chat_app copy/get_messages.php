<?php
session_start();
require 'db.php';

$userId = $_SESSION['user_id'];
$partnerId = $_GET['partner'];

$stmt = $pdo->prepare("SELECT * FROM messages 
  WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)
  ORDER BY created_at ASC");
$stmt->execute([$userId, $partnerId, $partnerId, $userId]);

// 相手から自分宛ての未読を既読にする
$update = $pdo->prepare("UPDATE messages SET is_read=1 WHERE receiver_id=? AND sender_id=? AND is_read=0");
$update->execute([$userId, $partnerId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
