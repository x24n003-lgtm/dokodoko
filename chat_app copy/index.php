<?php
// ダミーデータ（実際はDBから取得）
$users = [
    ["id" => 1, "name" => "先生A", "role" => "先生"],
    ["id" => 2, "name" => "先生B", "role" => "先生"],
    ["id" => 3, "name" => "学生 太郎", "role" => "学生"],
    ["id" => 4, "name" => "学生 花子", "role" => "学生"]
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>チャット相手を選択</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="select-container">
    <h2>チャット相手を選んでください</h2>
    <div class="user-list">
      <?php foreach($users as $u): ?>
        <a class="user-item" href="chat.php?user=<?=$u['id']?>">
          <div class="avatar"><?=$u['role']=="先生"?"👩‍🏫":"🎓"?></div>
          <div class="info">
            <p class="name"><?=$u['name']?></p>
            <p class="role"><?=$u['role']?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
