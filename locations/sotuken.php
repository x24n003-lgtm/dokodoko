<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>Google Maps 位置情報マップ</title>
<link rel="stylesheet" href="sotuken.css">
<style>
  #map { height: 500px; width: 90%; margin: 20px auto; }
</style>
</head>
<body>

<button onclick="getLocation()">現在地取得</button>
<p id="output"></p>
<div id="map"></div>

<!-- Google Maps API を読み込み (YOUR_API_KEY を置き換え) -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"></script>
<script>
let map;
let userMarker;

// マップ初期化
function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        center: {lat: 35.681, lng: 139.767}, // 東京駅中心
        zoom: 12
    });

    // PHP から取得した位置情報をマーカーで描画
    fetch('locations.php')
        .then(res => res.json())
        .then(data => {
            data.forEach(loc => {
                let color = 'yellow';
                if (loc.name.includes('学校')) color = 'green';
                else if (loc.name.includes('自宅')) color = 'red';

                new google.maps.Circle({
                    strokeColor: color,
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                    fillColor: color,
                    fillOpacity: 0.35,
                    map: map,
                    center: {lat: parseFloat(loc.lat), lng: parseFloat(loc.lng)},
                    radius: 50
                });

                new google.maps.Marker({
                    position: {lat: parseFloat(loc.lat), lng: parseFloat(loc.lng)},
                    map: map,
                    title: loc.name
                });
            });
        });
}

// 現在地取得
function getLocation() {
    const output = document.getElementById('output');
    if (!navigator.geolocation) {
        output.textContent = "このブラウザでは位置情報はサポートされていません";
        return;
    }
    output.textContent = "位置情報を取得しています...";

    navigator.geolocation.getCurrentPosition(
        pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            output.textContent = `緯度: ${lat.toFixed(6)}, 経度: ${lng.toFixed(6)}`;

            if (userMarker) userMarker.setMap(null);
            userMarker = new google.maps.Marker({
                position: {lat: lat, lng: lng},
                map: map,
                title: "あなたの現在地"
            });

            map.setCenter({lat: lat, lng: lng});
            map.setZoom(16);
        },
        err => {
            output.textContent = `エラー: ${err.code} - ${err.message}`;
        }
    );
}

// 初期化
window.onload = initMap;
</script>

</body>
</html>
