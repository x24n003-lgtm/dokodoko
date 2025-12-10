<?php
session_start();
// 仮ログイン中ユーザー
$_SESSION['user_id'] = 1; // 自分のID
$partnerId = $_GET['user'] ?? 2;

require 'db.php'; // DB接続

// 相手の名前取得
$stmt = $pdo->prepare("SELECT name FROM users WHERE id=?");
$stmt->execute([$partnerId]);
$chatPartner = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title><?=$chatPartner?> さんとのチャット</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="chat-container">
    <header>
      <h2><?=$chatPartner?> さんとのチャット</h2>
    </header>
    <div id="chat-box" class="chat-box"></div>
    <form id="chat-form" class="chat-form">
      <input type="text" id="message" name="message" placeholder="メッセージを入力..." required>
      <button type="submit">送信</button>
    </form>
  </div>

<script>
const userId = <?=json_encode($_SESSION['user_id'])?>;
const partnerId = <?=json_encode($partnerId)?>;

async function loadMessages() {
  const res = await fetch(`get_messages.php?partner=${partnerId}`);
  const data = await res.json();
  const box = document.getElementById("chat-box");
  box.innerHTML = "";
  data.forEach(msg => {
    const div = document.createElement("div");
    div.className = "chat-message " + (msg.sender_id == userId ? "me" : "partner");
    div.innerHTML = `<p>${msg.message}</p>`;
    box.appendChild(div);
  });
  box.scrollTop = box.scrollHeight;
}

// メッセージ送信
document.getElementById("chat-form").addEventListener("submit", async e => {
  e.preventDefault();
  const msg = document.getElementById("message").value;
  await fetch("send_message.php", {
    method: "POST",
    headers: {"Content-Type": "application/x-www-form-urlencoded"},
    body: `partner=${partnerId}&message=${encodeURIComponent(msg)}`
  });
  document.getElementById("message").value = "";
  loadMessages();
});

// 3秒ごとに新着取得
setInterval(loadMessages, 3000);
loadMessages();
</script>
</body>
</html>
