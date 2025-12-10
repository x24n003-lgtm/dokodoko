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
                <img id="profileImage" class="profile-image" src="" alt="プロフィール画像" onclick="goToLogoSettings()">
                <input type="file" id="imageUpload" accept="image/*">
            </div>
            <br>
            <button class="edit-btn" onclick="goToLogoSettings()">編集</button>
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

        // ===== ロゴ設定ページに遷移 =====
        function goToLogoSettings() {
            window.location.href = 'logo_settings.php';
        }

        // 初期化
        window.onload = function() {
            console.log('ページが読み込まれました');
            const defaultImageSvg = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="35" r="20" fill="%23999"/><path d="M20 80 Q20 60 50 60 Q80 60 80 80 Z" fill="%23999"/></svg>';
            document.getElementById('profileImage').src = defaultImageSvg;
        };
    </script>
</body>
</html>