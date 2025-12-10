<?php
session_start();
?>
<!-- user_id がある場合のみ hidden input を出力 -->
<?php if (isset($_SESSION['user_id'])): ?>
<input type="hidden" id="user_id" value="<?= htmlspecialchars($_SESSION['user_id']) ?>">
<?php endif; ?>

<?php
// 既にログインしている場合はリダイレクト
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'student') {
        header('Location: karennda-.php');
    } else if ($_SESSION['user_type'] === 'teacher') {
        header('Location: syusseki.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h2>ログイン</h2>
        
        <?php
        // エラーメッセージの表示
        if (isset($_SESSION['error'])) {
            echo '<div class="message error">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        
        // 成功メッセージの表示
        if (isset($_SESSION['success'])) {
            echo '<div class="message success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        ?>
        
        <form action="login_process.php" method="POST" id="loginForm">
            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       required 
                       placeholder="例: x12345@school.ac.jp"
                       autocomplete="email">
            </div>
            
            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       minlength="6"
                       placeholder="6文字以上"
                       autocomplete="current-password">
            </div>
            
            <button type="submit" class="login-btn">ログイン</button>
        </form>
        
        <div class="register-link">
            <p>アカウントをお持ちでない方は<br>
            <a href="newacc.php">こちらから新規登録</a></p>
        </div>
    </div>
    
    <script>
        // フォームのバリデーション
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (email === '' || password === '') {
                e.preventDefault();
                alert('メールアドレスとパスワードを入力してください。');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('パスワードは6文字以上で入力してください。');
                return false;
            }
        });
    </script>
</body>
</html>