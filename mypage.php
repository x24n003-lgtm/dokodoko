<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ</title>
    <!-- CSSファイルを読み込み -->
    <link rel="stylesheet" href="mypage.css">
</head>
<body>
    <div class="container">
        <!-- 上部のヘッダー部分 -->
        <div class="header">
            <!-- ★戻るボタン - ここに前のページのURLを入力★ -->
            <a href="index.php" class="back-btn">← 戻る</a>
            <h1 class="header-title">マイページ</h1>
        </div>

        <!-- プロフィール写真と編集ボタンの部分 -->
        <div class="profile-section">
            <div class="profile-image-container">
                <!-- プロフィール写真（クリックすると写真を変更できる） -->
                <img id="profileImage" class="profile-image" src="" alt="プロフィール画像" onclick="selectImage()">
                <!-- 写真選択用の隠れたボタン -->
                <input type="file" id="imageUpload" accept="image/*">
            </div>
            <br>
            <!-- 編集/保存ボタン -->
            <button class="edit-btn" onclick="toggleEdit()">編集</button>
        </div>

        <!-- 名前と生年月日の入力部分 -->
        <div class="profile-details">
            <!-- 名前の行 -->
            <div class="detail-row">
                <span class="detail-label">氏名</span>
                <div class="detail-value">
                    <input type="text" id="name" value="" placeholder="名前を入力してください" readonly>
                </div>
            </div>
            <!-- 生年月日の行 -->
            <div class="detail-row">
                <span class="detail-label">生年月日</span>
                <div class="detail-value">
                    <input type="date" id="birthday" value="2000-12-01" readonly>
                </div>
            </div>
        </div>

        <!-- 下部のメニューボタン -->
        <div class="bottom-nav">
            <!-- ★人のアイコン（現在のページなので#でOK） -->
            <a href="#" class="nav-item active">
                <div class="nav-icon person"></div>
            </a>
            <!-- ★吹き出しアイコン - ここにメッセージページのURLを入力★ -->
            <a href="message.php" class="nav-item">
                <div class="nav-icon message"></div>
            </a>
            <!-- ★歯車アイコン - ここに設定ページのURLを入力★ -->
            <a href="settings.php" class="nav-item">
                <div class="nav-icon settings"></div>
            </a>
        </div>
    </div>

    <!-- JavaScriptの動作部分 -->
    <script>
        // 編集中かどうかを覚えておく変数
        let isEditing = false;

        // 写真がアップロードされた時の処理
        document.getElementById('imageUpload').addEventListener('change', function(e) {
            const file = e.target.files[0]; // 選択されたファイルを取得
            if (file) {
                const reader = new FileReader(); // ファイルを読み込む機能
                reader.onload = function(e) {
                    // 選択した写真を画面に表示
                    document.getElementById('profileImage').src = e.target.result;
                    console.log('画像がアップロードされました');
                };
                reader.readAsDataURL(file); // ファイルを画像として読み込み
            }
        });

        // プロフィール写真をクリックした時の処理
        function selectImage() {
            console.log('アイコンがクリックされました');
            // 隠れているファイル選択ボタンを押す
            document.getElementById('imageUpload').click();
        }

        // 編集ボタンを押した時の処理
        function toggleEdit() {
            isEditing = !isEditing; // 編集状態を切り替え
            const nameInput = document.getElementById('name'); // 名前の入力欄
            const birthdayInput = document.getElementById('birthday'); // 生年月日の入力欄
            const editBtn = document.querySelector('.edit-btn'); // 編集ボタン

            if (isEditing) {
                // 編集モードにする
                nameInput.removeAttribute('readonly'); // 名前を編集可能にする
                birthdayInput.removeAttribute('readonly'); // 生年月日を編集可能にする
                editBtn.textContent = '保存'; // ボタンの文字を「保存」に変更
                console.log('編集モードON');
            } else {
                // 編集を終了して保存する
                nameInput.setAttribute('readonly', true); // 名前を編集不可にする
                birthdayInput.setAttribute('readonly', true); // 生年月日を編集不可にする
                editBtn.textContent = '編集'; // ボタンの文字を「編集」に戻す
                
                // 入力された内容を保存
                saveField('name', nameInput.value);
                saveField('birthday', birthdayInput.value);
                console.log('編集モードOFF - 保存完了');
            }
        }

        // 入力内容を保存する処理
        function saveField(fieldName, value) {
            console.log(`${fieldName}が保存されました: ${value}`);
            
            // ★ここにデータベースに保存する処理を書く★
            // 例: fetch('save_profile.php', { method: 'POST', ... })
            
            // 保存完了を知らせるために一瞬緑色にする
            const input = document.getElementById(fieldName);
            if (input) {
                input.style.backgroundColor = '#e8f5e8'; // 緑色にする
                setTimeout(() => {
                    input.style.backgroundColor = ''; // 元の色に戻す
                }, 1000); // 1秒後に元に戻す
            }
        }

        // ページが読み込まれた時の最初の処理
        window.onload = function() {
            console.log('ページが読み込まれました');
            
            // 最初に表示するデフォルトの人のアイコンを設定
            const defaultImageSvg = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="35" r="20" fill="%23999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80 Z" fill="%23999"/></svg>';
            document.getElementById('profileImage').src = defaultImageSvg;
        };
    </script>
</body>
</html>