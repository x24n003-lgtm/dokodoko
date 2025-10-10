<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>Google Maps 位置情報マップ</title>
<link rel="stylesheet" href="sotuken.css">
<style>
  #map { height: 300px; width: 90%; margin: 20px auto; }
  #pano { height: 400px; width: 90%; margin: 20px auto; }
</style>
</head>
<body>

<button onclick="getLocation()">現在地取得</button>
<p id="output"></p>
<div id="map"></div>
<div id="pano"></div>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"></script>
<script>
let map, userMarker, panorama;

// マップ初期化
function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        center: {lat: 35.784578, lng: 139.928591}, // 東京駅中心
        zoom: 12
    });

    // PHP から取得した位置情報を描画
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

    // ストリートビューの初期化（マップ中心に合わせる）
    panorama = new google.maps.StreetViewPanorama(
        document.getElementById('pano'),
        {
            position: map.getCenter(),
            pov: {heading: 165, pitch: 0},
            visible: true
        }
    );

    // ストリートビュー移動でマーカーと緯度経度更新
    panorama.addListener("position_changed", () => {
        const pos = panorama.getPosition();
        const lat = pos.lat();
        const lng = pos.lng();

        const output = document.getElementById('output');
        output.textContent = `ストリートビュー座標 - 緯度: ${lat.toFixed(6)}, 経度: ${lng.toFixed(6)}`;

        if (userMarker) userMarker.setMap(null);
        userMarker = new google.maps.Marker({
            position: {lat, lng},
            map: map,
            title: "現在位置（ストリートビュー）"
        });

        map.setCenter({lat, lng});
        map.setZoom(16);
    });
}

// ブラウザGPS取得
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
            output.textContent = `ブラウザGPS - 緯度: ${lat.toFixed(6)}, 経度: ${lng.toFixed(6)}`;

            if (userMarker) userMarker.setMap(null);
            userMarker = new google.maps.Marker({
                position: {lat, lng},
                map: map,
                title: "現在地（GPS）"
            });

            map.setCenter({lat, lng});
            map.setZoom(16);

            // ストリートビューもこの位置に移動
            panorama.setPosition({lat, lng});
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
