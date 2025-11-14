<?php
session_start();

// 学生のログインチェック
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: login.php');
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>位置情報送信</title>
    <link rel="stylesheet" href="student_checkin.css">
    <style>
        .location-status {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .location-status.school {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        .location-status.outside {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
        }
        .location-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }
        .distance-info {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        .map-container {
            width: 100%;
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
            border: 2px solid #ddd;
        }
        #map {
            width: 100%;
            height: 100%;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .auto-update {
            text-align: center;
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📍 現在地送信</h1>
        
        <div class="status-info">
            <h2>ようこそ、<?php echo $username; ?>さん</h2>
        </div>
        
        <div id="locationStatus" class="location-status" style="display: none;">
            <h2 id="statusText">位置情報を取得中...</h2>
            <p id="distanceText" class="distance-info"></p>
        </div>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>位置情報を取得しています...</p>
        </div>
        
        <div class="map-container" style="display: none;" id="mapContainer">
            <div id="map"></div>
        </div>
        
        <div class="form-group">
            <button type="button" class="submit-btn" id="sendLocationBtn" disabled>
                📡 現在地を送信
            </button>
            <p class="auto-update">※ 30秒ごとに自動更新されます</p>
        </div>
        
        <div id="message" class="message" style="display: none;"></div>
        
        <a href="karennda-.php" class="back-link">← ホームに戻る</a>
    </div>
    
    <script>
        let currentLat = null;
        let currentLng = null;
        let map = null;
        let marker = null;
        let schoolMarker = null;
        let circle = null;
        
        // 学校の位置
        const SCHOOL_LAT = 35.704517;
        const SCHOOL_LNG = 139.984413;
        const SCHOOL_RADIUS = 500; // メートル
        
        // ページ読み込み時に位置情報を取得
        window.onload = function() {
            getLocation();
            // 30秒ごとに自動更新
            setInterval(getLocation, 30000);
        };
        
        // 位置情報取得
        function getLocation() {
            if (!navigator.geolocation) {
                showError('このブラウザは位置情報に対応していません');
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    currentLat = position.coords.latitude;
                    currentLng = position.coords.longitude;
                    
                    const accuracy = position.coords.accuracy;
                    console.log('位置情報取得成功:', currentLat, currentLng, '精度:', accuracy + 'm');
                    
                    updateStatus();
                    initMap();
                    document.getElementById('sendLocationBtn').disabled = false;
                    document.getElementById('loading').style.display = 'none';
                    
                    // 自動送信
                    sendLocation();
                },
                (error) => {
                    let errorMsg = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = '位置情報の利用が許可されていません。ブラウザの設定を確認してください。';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = '位置情報が取得できません。';
                            break;
                        case error.TIMEOUT:
                            errorMsg = '位置情報の取得がタイムアウトしました。';
                            break;
                        default:
                            errorMsg = '位置情報の取得中にエラーが発生しました。';
                    }
                    showError(errorMsg);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
        
        // ステータス表示更新
        function updateStatus() {
            const distance = calculateDistance(SCHOOL_LAT, SCHOOL_LNG, currentLat, currentLng);
            const statusDiv = document.getElementById('locationStatus');
            const statusText = document.getElementById('statusText');
            const distanceText = document.getElementById('distanceText');
            
            statusDiv.style.display = 'block';
            statusDiv.className = 'location-status';
            
            if (distance <= SCHOOL_RADIUS) {
                statusDiv.classList.add('school');
                statusText.textContent = '🏫 校内にいます';
                statusText.innerHTML += ' <span style="font-size: 24px;">✅</span>';
            } else {
                statusDiv.classList.add('outside');
                statusText.textContent = '📍 校外にいます';
            }
            
            distanceText.textContent = `学校まで約 ${Math.round(distance)}m`;
        }
        
        // 距離計算
        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371000; // 地球の半径(m)
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }
        
        // 地図初期化（Googleマップ使用の場合）
        function initMap() {
            // ここに地図のコードを追加できます
            // Google Maps APIキーが必要
        }
        
        // 位置情報送信
        function sendLocation() {
            if (!currentLat || !currentLng) {
                showMessage('位置情報が取得できていません', 'error');
                return;
            }
            
            fetch('api_location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    lat: currentLat,
                    lng: currentLng
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('✅ ' + data.message + ' (' + data.status + ')', 'success');
                } else {
                    showMessage('❌ ' + (data.error || '送信に失敗しました'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('❌ 通信エラーが発生しました', 'error');
            });
        }
        
        // メッセージ表示
        function showMessage(msg, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = msg;
            messageDiv.className = 'message ' + type;
            messageDiv.style.display = 'block';
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 3000);
        }
        
        // エラー表示
        function showError(msg) {
            document.getElementById('loading').style.display = 'none';
            const statusDiv = document.getElementById('locationStatus');
            statusDiv.style.display = 'block';
            statusDiv.className = 'location-status error';
            document.getElementById('statusText').textContent = '❌ エラー';
            document.getElementById('distanceText').textContent = msg;
        }
        
        // 手動送信ボタン
        document.getElementById('sendLocationBtn').addEventListener('click', sendLocation);
    </script>
</body>
</html>