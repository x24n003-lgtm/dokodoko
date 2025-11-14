<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マイページ</title>
    <link rel="stylesheet" href="mypage.css">
</head>
<body>
    <div class="container">
        <!-- ヘッダー -->
        <div class="header">
            <a href="#" class="back-btn" onclick="goBack(event)">← 戻る</a>
            <h1 class="header-title">マイページ</h1>
        </div>
 
        <!-- プロフィールセクション -->
        <div class="profile-section">
            <div class="profile-image-container">
                <img id="profileImage" class="profile-image" src="" alt="プロフィール画像" onclick="selectImage()">
                <input type="file" id="imageUpload" accept="image/*">
            </div>
            <br>
            <button class="edit-btn" onclick="toggleEdit()">編集</button>
        </div>
 
        <!-- プロフィール詳細 -->
        <div class="profile-details">
            <div class="detail-row">
                <span class="detail-label">氏名</span>
                <div class="detail-value">
                    <input type="text" id="name" value="" placeholder="名前を入力" readonly>
                </div>
            </div>
 
            <div class="detail-row">
                <span class="detail-label">生年月日</span>
                <div class="detail-value">
                    <input type="date" id="birthday" value="2000-12-01" readonly>
                </div>
            </div>
        </div>
 
        <!-- 保存ボタン -->
        <div class="save-section">
            <button class="save-btn" id="saveBtn" onclick="saveProfile()" style="display: none;">保存</button>
        </div>
 
        <!-- ボトムナビゲーション -->
        <div class="bottom-nav">
            <!-- 左：出席管理 -->
            <a href="karennda-.php" class="nav-item">
                <div class="nav-icon person"></div>
                <span class="nav-text">カレンダー</span>
            </a>
 
            <!-- 中：チャット -->
            <a href="chat.php" class="nav-item">
                <div class="nav-icon message"></div>
                <span class="nav-text">チャット</span>
            </a>
 
            <!-- 右：マイページ -->
            <a href="mypage.php" class="nav-item active">
                <div class="nav-icon settings"></div>
                <span class="nav-text">マイページ</span>
            </a>
        </div>
    </div>
 
    <script>
        let isEditing = false;
 
        // ===== 戻るボタンの機能 =====
        function goBack(event) {
            event.preventDefault();
            if (window.history.length > 1) {
                window.history.back();
                console.log('前のページに戻りました');
            } else {
                console.log('履歴がないため、ホームページにリダイレクトします');
                alert('戻る履歴がありません');
            }
        }
 
        // 画像アップロード機能
        document.getElementById('imageUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                    console.log('画像がアップロードされました');
                };
                reader.readAsDataURL(file);
            }
        });
 
        // アイコンをクリックして画像選択
        function selectImage() {
            console.log('アイコンがクリックされました');
            document.getElementById('imageUpload').click();
        }
 
        // 編集モード切り替え
        function toggleEdit() {
            isEditing = !isEditing;
            const nameInput = document.getElementById('name');
            const birthdayInput = document.getElementById('birthday');
            const editBtn = document.querySelector('.edit-btn');
 
            if (isEditing) {
                nameInput.removeAttribute('readonly');
                birthdayInput.removeAttribute('readonly');
                editBtn.textContent = '保存';
                console.log('編集モードON');
            } else {
                nameInput.setAttribute('readonly', true);
                birthdayInput.setAttribute('readonly', true);
                editBtn.textContent = '編集';
               
                saveField('name', nameInput.value);
                saveField('birthday', birthdayInput.value);
                console.log('編集モードOFF - 保存完了');
            }
        }
 
        // 個別フィールド保存
        function saveField(fieldName, value) {
            console.log(`${fieldName}が保存されました: ${value}`);
            const input = document.getElementById(fieldName);
            if (input) {
                input.style.backgroundColor = '#e8f5e8';
                setTimeout(() => { input.style.backgroundColor = ''; }, 1000);
            }
        }
 
        // 保存ボタンの処理
        function saveProfile() {
            const name = document.getElementById('name').value;
            const birthday = document.getElementById('birthday').value;
            console.log('保存ボタンが押されました');
            console.log('氏名:', name);
            console.log('生年月日:', birthday);
            alert('プロフィールが保存されました');
        }
 
        // 初期化
        window.onload = function() {
            console.log('ページが読み込まれました');
            const defaultImageSvg = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="35" r="20" fill="%23999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80 Z" fill="%23999"/></svg>';
            document.getElementById('profileImage').src = defaultImageSvg;
        };
    </script>

</body>
