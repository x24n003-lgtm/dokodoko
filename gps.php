<?php
session_start();
$host = "172.16.199.21";
$user = "x24n007";
$pass = "n051211";
$db   = "dokodoko";
$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

$email = $_SESSION['email'] ?? null;
if (!$email) {
    die("ログインしていません。");
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>リアルタイムGPS送信＆確認マップ</title>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"></script>
<style>
  #map { height: 500px; width: 100%; }
</style>
</head>
<body>
<h2>📍 リアルタイムGPS送信＆DB上の現在地確認</h2>
<p>ユーザー: <b><?php echo htmlspecialchars($email); ?></b></p>
<div id="map"></div>

<script>
let map, marker;

// マップ初期化
function initMap(lat, lng) {
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat, lng },
        zoom: 18,
    });
    marker = new google.maps.Marker({
        position: { lat, lng },
        map: map,
        title: "現在地",
    });
}

// DBから初期位置取得
async function loadInitialLocation() {
    try {
        const res = await fetch('get_location.php');
        const data = await res.json();
        if (data.lat && data.lng) {
            initMap(data.lat, data.lng);
        }
    } catch (err) {
        console.error("初期位置取得エラー:", err);
    }
}

// DBに位置情報送信
async function sendLocation(lat, lng) {
    try {
        const res = await fetch('save_locations.php', {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email: "<?php echo $email; ?>", lat, lng })
        });
        const result = await res.json();
        console.log("送信結果:", result);
    } catch (err) {
        console.error("送信エラー:", err);
    }
}

// 位置監視
if (navigator.geolocation) {
    navigator.geolocation.watchPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            // DB送信
            sendLocation(lat, lng);

            // マーカー更新
            if (!map) {
                initMap(lat, lng);
            } else {
                marker.setPosition({ lat, lng });
                map.setCenter({ lat, lng });
            }
        },
        (error) => { console.error("位置情報取得失敗:", error); },
        { enableHighAccuracy: true, maximumAge: 0, timeout: 5000 }
    );
} else {
    alert("このブラウザでは位置情報が取得できません");
}

// 定期的にDBの最新位置を取得して反映
setInterval(async () => {
    try {
        const res = await fetch('get_location.php');
        const data = await res.json();
        if (data.lat && data.lng && map) {
            marker.setPosition({ lat: data.lat, lng: data.lng });
            map.setCenter({ lat: data.lat, lng: data.lng });
        }
    } catch (err) {
        console.error("DB位置取得エラー:", err);
    }
}, 5000);

// ページ読み込み時に初期位置を設定
loadInitialLocation();

</script>
</body>
</html>
