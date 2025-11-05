<?php
// エラー表示を有効化
ini_set('display_errors', 1);
error_reporting(E_ALL);
 
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会員登録</title>
</head>
<body>
    <div class="register-container">
        <h2>🎓 新規会員登録</h2>
       
        <?php
        // エラーメッセージの表示
        if (isset($_SESSION['error'])) {
            echo '<div class="message error">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        ?>
       
        <form action="test.php" method="POST" id="registerForm">
            <div class="form-group">
                <label for="email">メールアドレス *</label>
                <input type="email" id="email" name="email" required
                       placeholder="学生: x12345@... / 教員: teacher@...">
                <p class="note">※ 学生の方はxから始まるメールアドレスを入力</p>
            </div>
           
            <div class="form-group">
                <label for="username">ユーザー名 *</label>
                <input type="text" id="username" name="username" required
                       placeholder="山田太郎">
            </div>
     
            <div class="form-group">
                <label for="password">パスワード *</label>
                <input type="password" id="password" name="password" required minlength="6"
                       placeholder="6文字以上">
            </div>
           
            <div class="form-group">
                <label for="password_confirm">パスワード確認 *</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6"
                       placeholder="もう一度入力">
            </div>
     
            <div class="form-group">
                <label for="phone">電話番号 *</label>
                <input type="tel" id="phone" name="phone" required
                       placeholder="090-1234-5678">
            </div>
     
            <div class="form-group">
                <label for="gender">性別 *</label>
                <select id="gender" name="gender" required>
                    <option value="">選択してください</option>
                    <option value="男性">男性</option>
                    <option value="女性">女性</option>
                    <option value="その他">その他</option>
                </select>
            </div>
     
            <div class="form-group">
                <label for="home_address">自宅住所 *</label>
                <input type="text" id="home_address" name="home_address" required
                       placeholder="東京都渋谷区...">
            </div>
 
            <!-- クラス選択（学生のみ） -->
            <div class="form-group" id="classField" style="display: none;">
                <label for="class_name">クラス *</label>
                <select id="class_name" name="class_name">
                    <option value="">選択してください</option>
                    <option value="2N1">2N1</option>
                    <option value="2N2">2N2</option>
                    <option value="2N3">2N3</option>
                    <option value="1N1">1N1</option>
                    <option value="1N2">1N2</option>
                    <option value="1N3">1N3</option>
                </select>
                <p class="note">※ 学生の方はクラスを選択してください</p>
            </div>
     
            <button type="submit" class="register-btn">登録する</button>
        </form>
    </div>
   
    <script>
        // メールアドレス入力時にクラスフィールドの表示/非表示を切り替え
        document.getElementById('email').addEventListener('input', function(e) {
            const email = e.target.value.toLowerCase();
            const classField = document.getElementById('classField');
            const classSelect = document.getElementById('class_name');
           
            // xで始まるメールアドレスなら学生とみなす
            if (email.startsWith('x')) {
                classField.style.display = 'block';
                classSelect.setAttribute('required', 'required');
            } else {
                classField.style.display = 'none';
                classSelect.removeAttribute('required');
                classSelect.value = ''; // クラス選択をリセット
            }
        });
 
        // フォームのバリデーション
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            const gender = document.getElementById('gender').value;
            const email = document.getElementById('email').value.toLowerCase();
            const className = document.getElementById('class_name').value;
           
            // パスワード一致チェック
            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('パスワードが一致しません。');
                return false;
            }
           
            // パスワード長さチェック
            if (password.length < 6) {
                e.preventDefault();
                alert('パスワードは6文字以上で入力してください。');
                return false;
            }
           
            // 性別選択チェック
            if (gender === '') {
                e.preventDefault();
                alert('性別を選択してください。');
                return false;
            }
 
            // 学生の場合、クラス選択チェック
            if (email.startsWith('x') && className === '') {
                e.preventDefault();
                alert('クラスを選択してください。');
                return false;
            }
           
            // すべてのチェックをパスしたら確認
            return confirm('この内容で登録しますか？');
        });
    </script>
</body>
</html>