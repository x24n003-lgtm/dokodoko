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

        <!-- カレンダー管理セクション（教員専用） -->
        <div class="calendar-management">
            <h3>📅 カレンダー管理</h3>
            <p>学生のカレンダーに表示される予定やイベントを追加・編集・削除できます。</p>
            <a href="teacher_calendar.php" class="calendar-link">カレンダーを管理する →</a>
        </div>

 
        <!-- プロフィールセクション -->
        <div class="profile-section">
            <div class="profile-image-container">
                <img id="profileImage" class="profile-image" src="" alt="プロフィール画像" onclick="selectProfileImage()">
                <input type="file" id="imageUpload" accept="image/*" style="display: none;">
            </div>
            <br>
            <button class="edit-btn" onclick="toggleEdit()">編集</button>
        </div>
 
        <!-- プロフィール詳細 -->
        <div class="profile-details">
            <div class="detail-row">
                <span class="detail-label">氏名</span>
                <div class="detail-value">
                    <input type="text" id="name" value="橋純平" placeholder="名前を入力" readonly>
                </div>
            </div>
 
            <div class="detail-row">
                <span class="detail-label">生年月日</span>
                <div class="detail-value">
                    <input type="date" id="birthday" value="2000-12-01" readonly>
                </div>
            </div>
        </div>
 
        <!-- ボトムナビゲーション -->
        <div class="bottom-nav">
            <a href="karennda-.php" class="nav-item">
                <div class="nav-icon person"></div>
                <span class="nav-text">カレンダー</span>
            </a>
 
            <a href="chat.php" class="nav-item">
                <div class="nav-icon message"></div>
                <span class="nav-text">チャット</span>
            </a>
 
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

        // ===== プロフィール画像選択（アイコンクリック時） =====
        function selectProfileImage() {
            console.log('プロフィールアイコンがクリックされました');
            document.getElementById('imageUpload').click();
        }

        // ===== プロフィール画像のアップロード =====
        document.getElementById('imageUpload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                    console.log('プロフィール画像がアップロードされました');
                    
                    // サーバーにアップロード（オプション）
                    uploadProfileImage(file);
                };
                reader.readAsDataURL(file);
            }
        });

        // ===== プロフィール画像をサーバーにアップロード =====
        function uploadProfileImage(file) {
            const formData = new FormData();
            formData.append('profile_image', file);
            
            fetch('upload_profile_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('プロフィール画像のアップロード成功');
                } else {
                    console.error('プロフィール画像のアップロード失敗:', data.error);
                }
            })
            .catch(error => {
                console.error('アップロードエラー:', error);
            });
        }

        // ===== 編集モード切り替え =====
        function toggleEdit() {
            isEditing = !isEditing;
            const nameInput = document.getElementById('name');
            const birthdayInput = document.getElementById('birthday');
            const editBtn = document.querySelector('.edit-btn');

            if (isEditing) {
                // 編集モードON
                nameInput.removeAttribute('readonly');
                birthdayInput.removeAttribute('readonly');
                nameInput.focus();
                editBtn.textContent = '保存';
                editBtn.style.backgroundColor = '#34c759';
                console.log('編集モードON');
            } else {
                // 編集モードOFF（保存）
                nameInput.setAttribute('readonly', true);
                birthdayInput.setAttribute('readonly', true);
                editBtn.textContent = '編集';
                editBtn.style.backgroundColor = '#007AFF';
                
                // サーバーに保存
                saveProfile();
                console.log('編集モードOFF - 保存完了');
            }
        }

        // ===== プロフィール情報を保存 =====
        function saveProfile() {
            const name = document.getElementById('name').value;
            const birthday = document.getElementById('birthday').value;
            
            console.log('保存データ:', { name, birthday });
            
            // サーバーに送信
            fetch('save_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: name,
                    birthday: birthday
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('プロフィール保存成功');
                    // 保存成功のビジュアルフィードバック
                    const nameInput = document.getElementById('name');
                    const birthdayInput = document.getElementById('birthday');
                    nameInput.style.backgroundColor = '#e8f5e8';
                    birthdayInput.style.backgroundColor = '#e8f5e8';
                    setTimeout(() => {
                        nameInput.style.backgroundColor = '';
                        birthdayInput.style.backgroundColor = '';
                    }, 1000);
                } else {
                    console.error('プロフィール保存失敗:', data.error);
                    alert('保存に失敗しました: ' + data.error);
                }
            })
            .catch(error => {
                console.error('保存エラー:', error);
                alert('保存中にエラーが発生しました');
            });
        }

        // 初期化
        window.onload = function() {
            console.log('ページが読み込まれました');
            const defaultImageSvg = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="35" r="20" fill="%23999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80 Z" fill="%23999"/></svg>';
            document.getElementById('profileImage').src = defaultImageSvg;
            
            // 既存のプロフィール情報を読み込む
            loadProfile();
        };

        // ===== プロフィール情報を読み込む =====
        function loadProfile() {
            fetch('get_profile.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.name) {
                            document.getElementById('name').value = data.name;
                        }
                        if (data.birthday) {
                            document.getElementById('birthday').value = data.birthday;
                        }
                        if (data.profile_image) {
                            document.getElementById('profileImage').src = data.profile_image;
                        }
                        console.log('プロフィール情報を読み込みました');
                    }
                })
                .catch(error => {
                    console.error('プロフィール読み込みエラー:', error);
                });
        }
    </script>
</body>
</html>