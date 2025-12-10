<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ロゴ設定</title>
    <link rel="stylesheet" href="mypage.css">
</head>
<body>
    <div class="container">
        <!-- ヘッダー -->
        <div class="header">
            <a href="teachermypage.php" class="back-btn">← 戻る</a>
            <h1 class="header-title">ロゴ設定</h1>
        </div>

        <!-- ロゴ画像設定セクション -->
        <div class="logo-section" id="logoSection">
            <h3 class="logo-title">🌸 ヘッダーロゴ画像</h3>
            <p class="logo-description">出席管理画面のヘッダーに表示されるロゴ画像を設定できます</p>
            
            <div class="logo-preview">
                <div class="logo-preview-empty" id="logoPlaceholder">画像未設定</div>
                <img id="logoPreview" style="display: none;">
            </div>
            
            <form class="logo-upload-form" id="logoUploadForm">
                <input type="file" id="logoFileInput" accept="image/*" class="logo-file-input">
                <button type="button" class="logo-btn logo-upload-btn" onclick="uploadLogo()">
                    📤 ロゴをアップロード
                </button>
                <button type="button" class="logo-btn logo-delete-btn" onclick="deleteLogo()" style="display: none;" id="deleteBtn">
                    🗑️ ロゴを削除
                </button>
            </form>
        </div>

        <!-- メッセージ表示エリア -->
        <div id="messageArea"></div>

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
        // ===== ページ読み込み時に既存のロゴを取得 =====
        window.onload = function() {
            console.log('ロゴ設定ページが読み込まれました');
            loadExistingLogo();
        };

        // ===== 既存のロゴを読み込む =====
        function loadExistingLogo() {
            fetch('get_logo.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.logo_url) {
                        const logoPreview = document.getElementById('logoPreview');
                        const placeholder = document.getElementById('logoPlaceholder');
                        const deleteBtn = document.getElementById('deleteBtn');
                        
                        logoPreview.src = data.logo_url;
                        logoPreview.style.display = 'block';
                        placeholder.style.display = 'none';
                        deleteBtn.style.display = 'block';
                        
                        console.log('既存のロゴを読み込みました:', data.logo_url);
                    }
                })
                .catch(error => {
                    console.error('ロゴの読み込みエラー:', error);
                });
        }

        // ===== ロゴ画像のプレビュー =====
        document.getElementById('logoFileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const logoPreview = document.getElementById('logoPreview');
            const placeholder = document.getElementById('logoPlaceholder');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    logoPreview.src = e.target.result;
                    logoPreview.style.display = 'block';
                    placeholder.style.display = 'none';
                };
                reader.readAsDataURL(file);
                
                console.log('ロゴファイルが選択されました:', file.name);
            }
        });

        // ===== ロゴのアップロード =====
        function uploadLogo() {
            const fileInput = document.getElementById('logoFileInput');
            const file = fileInput.files[0];
            
            if (!file) {
                showMessage('ファイルを選択してください', 'error');
                return;
            }
            
            // FormDataを使ってファイルをアップロード
            const formData = new FormData();
            formData.append('logo', file);
            
            console.log('アップロード処理開始:', file.name);
            
            // ローディング表示
            showMessage('アップロード中...', 'info');
            
            // サーバーにアップロード
            fetch('upload_logo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('ロゴがアップロードされました！', 'success');
                    console.log('アップロード成功');
                    
                    // 削除ボタンを表示
                    document.getElementById('deleteBtn').style.display = 'block';
                    
                    // 2秒後にマイページに戻る
                    setTimeout(() => {
                        window.location.href = 'mypage.php';
                    }, 2000);
                } else {
                    showMessage('アップロードに失敗しました: ' + data.error, 'error');
                    console.error('アップロード失敗:', data.error);
                }
            })
            .catch(error => {
                console.error('エラー:', error);
                showMessage('アップロード中にエラーが発生しました', 'error');
            });
        }

        // ===== ロゴの削除 =====
        function deleteLogo() {
            if (!confirm('ロゴを削除しますか？')) {
                return;
            }
            
            fetch('delete_logo.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('ロゴが削除されました', 'success');
                    
                    // プレビューをリセット
                    const logoPreview = document.getElementById('logoPreview');
                    const placeholder = document.getElementById('logoPlaceholder');
                    const deleteBtn = document.getElementById('deleteBtn');
                    
                    logoPreview.style.display = 'none';
                    placeholder.style.display = 'block';
                    deleteBtn.style.display = 'none';
                    
                    // ファイル入力をリセット
                    document.getElementById('logoFileInput').value = '';
                } else {
                    showMessage('削除に失敗しました: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('エラー:', error);
                showMessage('削除中にエラーが発生しました', 'error');
            });
        }

        // ===== メッセージ表示 =====
        function showMessage(text, type) {
            const messageArea = document.getElementById('messageArea');
            const messageClass = type === 'success' ? 'message-success' : 
                                type === 'error' ? 'message-error' : 'message-info';
            
            messageArea.innerHTML = `<div class="message ${messageClass}">${text}</div>`;
            
            // 3秒後にメッセージを消す（エラー以外）
            if (type !== 'error') {
                setTimeout(() => {
                    messageArea.innerHTML = '';
                }, 3000);
            }
        }
    </script>
</body>
</html>