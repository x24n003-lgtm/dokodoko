<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>リアルタイム位置送信 + 全員表示</title>
<div id="map" style="height:500px;width:90%;margin:20px auto;"></div>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"></script>
</head>
<body>
<h2>リアルタイム位置送信中...</h2>
<div id="map"></div>

<script>
let map;
let userMarker;
let otherMarkers = [];

// マップ初期化
function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        center: {lat: 35.681, lng: 139.767}, // 東京駅中心
        zoom: 12
    });

    loadAllLocations(); // 初回全員マーカー描画
}

// 自分の位置を送信する関数
function sendMyLocation(lat, lng) {
    const name = "久保柊馬"; // 任意の識別名
    fetch("save_locations.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name, lat, lng })
    })
    .then(res => res.text())
    .then(txt => console.log("送信成功:", txt))
    .catch(err => console.error("送信エラー:", err));
}

// 自分の現在地を取得して送信
function updateMyLocation() {
    if (!navigator.geolocation) {
        console.error("ブラウザが位置情報をサポートしていません");
        return;
    }

    navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        // マーカー更新
        if (userMarker) userMarker.setMap(null);
        userMarker = new google.maps.Marker({
            position: {lat, lng},
            map: map,
            title: "あなたの現在地",
            icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
        });

        map.setCenter({lat, lng});
        map.setZoom(16);

        // DBに送信
        sendMyLocation(lat, lng);

    }, err => {
        console.error("位置取得エラー:", err);
    });
}

// DBから全員の位置を取得して描画
function loadAllLocations() {
    fetch("locations.php")
    .then(res => res.json())
    .then(data => {
        // 既存マーカー削除
        otherMarkers.forEach(m => m.setMap(null));
        otherMarkers = [];

        data.forEach(loc => {
            if (loc.name === "久保柊馬") return; // 自分は除外

            const marker = new google.maps.Marker({
                position: {lat: parseFloat(loc.lat), lng: parseFloat(loc.lng)},
                map: map,
                title: loc.name
            });
            otherMarkers.push(marker);
        });
    });
}

// 初期化
window.onload = function() {
    initMap();
    updateMyLocation();
    loadAllLocations();

    // 5秒ごとに自分の位置を送信＆全員の位置を更新
    setInterval(() => {
        updateMyLocation();
        loadAllLocations();
    }, 5000);
}
</script>
</body>
</html>
